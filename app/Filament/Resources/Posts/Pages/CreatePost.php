<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Models\PostWorkflowEvent;
use App\Support\PostSeoData;
use App\Support\PostTaxonomy;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

class CreatePost extends CreateRecord
{
    private const CREATED_POST_SESSION_KEY = 'filament.posts.created_post_id';

    protected static string $resource = PostResource::class;

    #[Locked]
    public ?int $createdPostId = null;

    public function mount(): void
    {
        parent::mount();

        $createdPostId = session()->pull(self::CREATED_POST_SESSION_KEY);

        if (! $this->showsPostCreatedDialog() || ! is_numeric($createdPostId)) {
            return;
        }

        $post = Post::query()->findOrFail((int) $createdPostId);
        abort_unless(auth()->user()?->can('view', $post), 403);

        $this->createdPostId = $post->getKey();
        $this->mountAction('postCreated');
    }

    protected function getRedirectUrl(): string
    {
        if ($this->showsPostCreatedDialog()) {
            session()->flash(self::CREATED_POST_SESSION_KEY, $this->record->getKey());

            return PostResource::getUrl('create');
        }

        return PostResource::getUrl('index');
    }

    public function postCreatedAction(): Action
    {
        return Action::make('postCreated')
            ->modalHeading('Post Created Successfully')
            ->modalDescription('The post has been saved. Choose what you would like to do next.')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalFooterActions([
                Action::make('viewPost')
                    ->label('View Post')
                    ->color('success')
                    ->url(fn (): string => $this->createdPost()->publicUrl())
                    ->openUrlInNewTab(),
                Action::make('allPosts')
                    ->label('All Posts')
                    ->color('gray')
                    ->url(PostResource::getUrl('index')),
                Action::make('createAnother')
                    ->label('Create Another')
                    ->url(PostResource::getUrl('create')),
            ]);
    }

    protected function beforeCreate(): void
    {
        $this->validateTaxonomy();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['seo_data'] = PostSeoData::mergeRobots(null, $this->data['robots'] ?? null);

        $canPublish = auth()->user()?->can('publish posts') ?? false;
        $status = $canPublish && ($data['status'] ?? null) === PostStatus::Published->value
            ? PostStatus::Published
            : PostStatus::Draft;

        $data['status'] = $status->value;
        $data['published_at'] = $status === PostStatus::Published ? now() : null;
        unset($data['scheduled_at']);

        $data['author_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        PostTaxonomy::syncPrimaryCategory($this->record, (int) $this->data['primary_category_id']);
        PostTaxonomy::syncTagsByName($this->record, $this->data['tag_names'] ?? []);

        if ($this->record->status === PostStatus::Published) {
            $this->record->forceFill(['published_by' => auth()->id()])->save();

            PostWorkflowEvent::query()->create([
                'post_id' => $this->record->getKey(),
                'actor_id' => auth()->id(),
                'event' => 'published',
                'from_status' => null,
                'to_status' => PostStatus::Published->value,
                'metadata' => ['source' => 'direct_creation'],
            ]);
        }
    }

    private function validateTaxonomy(): void
    {
        $errors = PostTaxonomy::validate($this->data);

        if ($errors !== []) {
            throw ValidationException::withMessages(
                collect($errors)->mapWithKeys(fn (string $message, string $field): array => ["data.{$field}" => $message])->all(),
            );
        }
    }

    private function showsPostCreatedDialog(): bool
    {
        return auth()->user()?->hasAnyRole(['super-admin', 'admin', 'editor']) ?? false;
    }

    private function createdPost(): Post
    {
        return Post::query()->findOrFail($this->createdPostId);
    }
}
