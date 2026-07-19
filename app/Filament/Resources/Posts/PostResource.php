<?php

namespace App\Filament\Resources\Posts;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Observers\PostObserver;
use App\Support\PostSeoData;
use App\Support\PostWorkflow;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Post')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                            if (filled($get('slug')) && $get('slug') !== Str::slug($old ?? '')) {
                                return;
                            }

                            $set('slug', Str::slug($state ?? ''));
                        }),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Textarea::make('excerpt')
                        ->maxLength(500)
                        ->rows(4)
                        ->columnSpanFull(),
                    RichEditor::make('content')
                        ->required()
                        ->columnSpanFull(),
                    Select::make('language')
                        ->options([
                            'hi' => 'Hindi',
                            'pa' => 'Punjabi',
                            'en' => 'English',
                        ])
                        ->required(),
                    Select::make('author_id')
                        ->label('Author')
                        ->relationship(
                            name: 'author',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'),
                        )
                        ->searchable()
                        ->default(fn (): ?int => auth()->id())
                        ->required(),
                ]),
            Section::make('Publishing')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->options(static::statusOptions())
                        ->default(PostStatus::Draft->value)
                        ->required(),
                    DateTimePicker::make('published_at')
                        ->label('Published At')
                        ->seconds(false),
                    DateTimePicker::make('scheduled_at')
                        ->label('Scheduled At')
                        ->seconds(false),
                    Toggle::make('is_featured')
                        ->label('Is Featured')
                        ->default(false),
                    Toggle::make('is_breaking')
                        ->label('Is Breaking')
                        ->default(false),
                ]),
            Section::make('Taxonomy')
                ->columns(2)
                ->schema([
                    Select::make('categories')
                        ->relationship(
                            name: 'categories',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query->active()->orderBy('name'),
                        )
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->minItems(1),
                    Select::make('primary_category_id')
                        ->label('Primary Category')
                        ->options(fn (Get $get): array => Category::query()
                            ->active()
                            ->whereKey($get('categories') ?? [])
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required()
                        ->dehydrated(false),
                    Select::make('tags')
                        ->relationship(
                            name: 'tags',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('name'),
                        )
                        ->multiple()
                        ->searchable()
                        ->columnSpanFull(),
                ]),
            Section::make('Featured Image')
                ->schema([
                    Select::make('featured_media_id')
                        ->label('Select from Media Library')
                        ->relationship(
                            name: 'featuredMedia',
                            titleAttribute: 'original_filename',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->whereNull('missing_at')
                                ->where('mime_type', 'like', 'image/%')
                                ->orderByDesc('created_at'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Media $record): string => $record->original_filename ?: basename($record->path))
                        ->searchable(['original_filename', 'path', 'alt_text'])
                        ->preload(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, mixed $state): void {
                            $path = filled($state) ? Media::query()->whereKey($state)->value('path') : null;
                            $set('featured_image', $path);
                        })
                        ->helperText('Search existing media. Detaching does not delete the binary.'),
                    FileUpload::make('featured_image')
                        ->label('Featured Image')
                        ->image()
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('posts/featured')
                        ->visibility('public')
                        ->live()
                        ->afterStateUpdated(fn (Set $set): mixed => $set('featured_media_id', null))
                        ->deleteUploadedFileUsing(fn (string $file): bool => PostObserver::deleteManagedImage($file))
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeName = Str::slug($originalName) ?: 'featured-image';
                            $extension = strtolower($file->getClientOriginalExtension());

                            return Str::uuid().'-'.$safeName.'.'.$extension;
                        }),
                ]),
            Section::make('SEO')
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('meta_title')
                        ->label('Meta Title')
                        ->maxLength(255),
                    TextInput::make('focus_keyword')
                        ->label('Focus Keyword')
                        ->maxLength(255),
                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->maxLength(160)
                        ->helperText('Keep the description at or below the recommended 160 characters.')
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('canonical_url')
                        ->label('Canonical URL')
                        ->url()
                        ->helperText('Use only when search engines should treat another URL as the preferred version.')
                        ->columnSpanFull(),
                    Select::make('robots')
                        ->options(PostSeoData::robotsOptions())
                        ->placeholder('Use site default')
                        ->dehydrated(false),
                    TextInput::make('source_name')
                        ->label('Source Name')
                        ->maxLength(255),
                    TextInput::make('source_url')
                        ->label('Source URL')
                        ->url()
                        ->columnSpanFull(),
                    TextInput::make('old_url')
                        ->label('Historical URL')
                        ->helperText('For imported WordPress redirect mapping only; historical values may not be valid URLs.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->disk('public')
                    ->width(60)
                    ->height(40),
                TextColumn::make('title')
                    ->searchable(['title', 'slug'])
                    ->sortable(),
                TextColumn::make('author.name')->label('Author')->placeholder('—'),
                TextColumn::make('status')
                    ->formatStateUsing(fn (PostStatus $state): string => $state->value)
                    ->badge(),
                TextColumn::make('language'),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                IconColumn::make('is_breaking')
                    ->label('Breaking')
                    ->boolean(),
                TextColumn::make('primaryCategory.name')
                    ->label('Primary Category')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->limitList(3),
                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->limitList(3),
                TextColumn::make('published_at')
                    ->label('Published At')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label('Scheduled At')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('has_seo_metadata')
                    ->label('SEO')
                    ->state(fn (Post $record): bool => filled($record->meta_title)
                        || filled($record->meta_description)
                        || filled($record->focus_keyword)
                        || filled($record->canonical_url)
                        || filled(data_get($record->seo_data, 'robots')))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options(static::statusOptions()),
                Filter::make('published')
                    ->query(fn (Builder $query): Builder => $query->where('status', PostStatus::Published->value)),
                Filter::make('draft')
                    ->query(fn (Builder $query): Builder => $query->where('status', PostStatus::Draft->value)),
                Filter::make('pending_review')
                    ->label('Pending Review')
                    ->query(fn (Builder $query): Builder => $query->where('status', PostStatus::PendingReview->value)),
                Filter::make('scheduled')
                    ->query(fn (Builder $query): Builder => $query->where('status', PostStatus::Scheduled->value)),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('is_breaking')->label('Breaking'),
                SelectFilter::make('author_id')
                    ->label('Author')
                    ->relationship('author', 'name')
                    ->searchable(),
                SelectFilter::make('language')->options([
                    'hi' => 'Hindi',
                    'pa' => 'Punjabi',
                    'en' => 'English',
                ]),
                SelectFilter::make('category')
                    ->relationship(
                        name: 'categories',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->active()->orderBy('name'),
                    )
                    ->searchable(),
                SelectFilter::make('tag')
                    ->relationship(
                        name: 'tags',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('name'),
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->authorizeIndividualRecords('publish')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records): mixed => $records->each(
                            fn (Post $post) => PostWorkflow::transition(auth()->user(), $post, PostStatus::Published),
                        )),
                    BulkAction::make('archive')
                        ->label('Archive')
                        ->authorizeIndividualRecords('archive')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records): mixed => $records->each(
                            fn (Post $post) => PostWorkflow::transition(auth()->user(), $post, PostStatus::Archived),
                        )),
                ]),
            ]);
    }

    /** @return Builder<Post> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'author',
            'primaryCategory',
            'categories',
            'tags',
        ]);
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return collect(PostStatus::cases())
            ->mapWithKeys(fn (PostStatus $status): array => [
                $status->value => Str::of($status->value)->replace('_', ' ')->title()->toString(),
            ])
            ->all();
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
