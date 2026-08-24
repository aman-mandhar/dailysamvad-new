<?php

namespace App\Filament\Resources\PushNotifications;

use App\Enums\PushNotificationStatus;
use App\Filament\Resources\PushNotifications\Pages\CreatePushNotification;
use App\Filament\Resources\PushNotifications\Pages\EditPushNotification;
use App\Filament\Resources\PushNotifications\Pages\ListPushNotifications;
use App\Models\Post;
use App\Models\PushNotification;
use App\Services\Push\ManualPushNotificationService;
use App\Services\Push\PostPushMessageFactory;
use App\Services\Push\PushAnalyticsService;
use App\Services\Push\PushAudienceResolver;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use RuntimeException;
use UnitEnum;

class PushNotificationResource extends Resource
{
    protected static ?string $model = PushNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Push Notifications';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Notification draft')
                ->description('Saving only updates the draft. It never sends a notification.')
                ->columns(2)
                ->schema([
                    Select::make('post_id')
                        ->label('Pre-fill from published Post')
                        ->options(fn (): array => Post::query()->published()->latest('published_at')->limit(500)->pluck('title', 'id')->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            if (blank($state) || ! ($post = Post::query()->published()->find($state))) {
                                return;
                            }

                            foreach (static::prefillFromPost($post) as $field => $value) {
                                $set($field, $value);
                            }
                        })
                        ->helperText('Copies a snapshot that remains editable and survives later Post changes.')
                        ->columnSpanFull(),
                    Radio::make('target_type')
                        ->label('Target audience')
                        ->options(['all' => 'All Active Subscribers', 'topics' => 'Selected Topics'])
                        ->default('all')
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    CheckboxList::make('topics')
                        ->relationship('topics', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name'))
                        ->label('Topics')
                        ->requiredIf('target_type', 'topics')
                        ->visible(fn (Get $get): bool => $get('target_type') === 'topics')
                        ->columns(2)
                        ->columnSpanFull(),                    TextInput::make('title')->required()->maxLength(200)->live(onBlur: true),
                    TextInput::make('target_url')->label('Destination URL')->url()->maxLength(2048)->live(onBlur: true)->helperText('Optional HTTP or HTTPS URL.'),
                    Textarea::make('body')->required()->maxLength(1000)->rows(5)->live(onBlur: true)->columnSpanFull(),
                    TextInput::make('image_url')->label('Image URL')->url()->maxLength(2048)->live(onBlur: true)->helperText('Optional HTTP or HTTPS image URL.')->columnSpanFull(),
                ]),
            Section::make('Approximate preview')
                ->description('Actual appearance varies by browser and device.')
                ->schema([
                    Html::make(function (Get $get): HtmlString {
                        $title = e(trim((string) $get('title')) ?: 'Notification title');
                        $body = nl2br(e(trim((string) $get('body')) ?: 'Notification message'));
                        $target = e(trim((string) $get('target_url')) ?: 'No destination URL');
                        $imageUrl = trim((string) $get('image_url'));
                        $image = filter_var($imageUrl, FILTER_VALIDATE_URL) && in_array(parse_url($imageUrl, PHP_URL_SCHEME), ['http', 'https'], true)
                            ? '<img src="'.e($imageUrl).'" alt="Notification preview" class="mt-3 max-h-40 w-full rounded-lg object-cover">'
                            : '';

                        return new HtmlString('<div class="mx-auto max-w-lg rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"><div class="text-xs font-semibold text-gray-500">Rzana Punjab</div><div class="mt-1 font-bold text-gray-950 dark:text-white">'.$title.'</div><div class="mt-1 text-sm text-gray-700 dark:text-gray-300">'.$body.'</div>'.$image.'<div class="mt-3 truncate text-xs text-gray-500">'.$target.'</div></div>');
                    }),
                    Text::make(function (Get $get): string {
                        $type = $get('target_type') ?: 'all';
                        $ids = array_values(array_filter((array) $get('topics')));
                        $count = $type === 'topics'
                            ? app(PushAudienceResolver::class)->forTopics($ids)->count()
                            : app(PushAudienceResolver::class)->allActive()->count();

                        return 'Target: '.($type === 'topics' ? 'Selected Topics' : 'All Active Subscribers').' · Estimated unique recipients: '.number_format($count);
                    })->badge(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(60),
                TextColumn::make('source_type')->label('Source')->badge()->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'manual')),

                TextColumn::make('status')->badge()->formatStateUsing(fn (PushNotificationStatus $state): string => ucfirst($state->value))->color(fn (PushNotificationStatus $state): string => match ($state) {
                    PushNotificationStatus::Draft => 'gray',
                    PushNotificationStatus::Queued => 'warning',
                    PushNotificationStatus::Sent => 'success',
                    PushNotificationStatus::Failed => 'danger',
                }),
                TextColumn::make('creator.name')->label('Created by')->placeholder('System'),
                TextColumn::make('recipient_count')->label('Recipients')->numeric()->placeholder('—'),
                TextColumn::make('accepted_count')->label('FCM Accepted')->numeric()->visible(fn (): bool => auth()->user()?->can('view push analytics') ?? false),
                TextColumn::make('failed_delivery_count')->label('Failed')->numeric()->visible(fn (): bool => auth()->user()?->can('view push analytics') ?? false),
                TextColumn::make('unique_clicks_count')->label('Unique Clicks')->numeric()->visible(fn (): bool => auth()->user()?->can('view push analytics') ?? false),
                TextColumn::make('ctr')->label('CTR')->state(fn (PushNotification $record): string => ($record->accepted_count ?? 0) > 0 ? number_format((($record->unique_clicks_count ?? 0) / $record->accepted_count) * 100, 2).'%' : '0.00%')->visible(fn (): bool => auth()->user()?->can('view push analytics') ?? false),
                TextColumn::make('queued_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('sent_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(PushNotificationStatus::options())])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()->visible(fn (PushNotification $record): bool => Gate::allows('update', $record)),
                static::sendAction(),
                static::analyticsAction(),
                DeleteAction::make()->visible(fn (PushNotification $record): bool => Gate::allows('delete', $record))->requiresConfirmation(),
            ]);
    }

    /** @return array{title:string,body:string,image_url:?string,target_url:?string} */
    public static function prefillFromPost(Post $post): array
    {
        $message = app(PostPushMessageFactory::class)->make($post);

        return [
            'title' => $message->title,
            'body' => $message->body,
            'image_url' => $message->image,
            'target_url' => $message->url,
        ];
    }

    public static function analyticsAction(): Action
    {
        return Action::make('analytics')
            ->label('Analytics')
            ->icon(Heroicon::OutlinedChartBar)
            ->visible(fn (PushNotification $record): bool => Gate::allows('viewAnalytics', $record))
            ->modalHeading('Push notification analytics')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (PushNotification $record): HtmlString {
                $metrics = app(PushAnalyticsService::class)->summary($record);
                $failures = $metrics['failure_categories'] === [] ? 'None' : collect($metrics['failure_categories'])->map(fn ($count, $category): string => e($category).': '.number_format($count))->implode(' · ');

                return new HtmlString('<div class="grid grid-cols-2 gap-3 sm:grid-cols-4"><div><strong>Recipients</strong><br>'.number_format($metrics['recipients']).'</div><div><strong>Attempted</strong><br>'.number_format($metrics['attempted']).'</div><div><strong>FCM Accepted</strong><br>'.number_format($metrics['accepted']).'</div><div><strong>Failed</strong><br>'.number_format($metrics['failed']).'</div><div><strong>Unique Clicks</strong><br>'.number_format($metrics['unique_clicks']).'</div><div><strong>Total Clicks</strong><br>'.number_format($metrics['total_clicks']).'</div><div><strong>CTR</strong><br>'.number_format($metrics['ctr'], 2).'%</div><div><strong>Source</strong><br>'.e($record->source_type ?? 'manual').'</div></div><p class="mt-4 text-sm"><strong>Failure categories:</strong> '.$failures.'</p><p class="mt-3 text-xs text-gray-500">FCM Accepted means Firebase accepted the HTTP v1 request; it does not guarantee display on a browser or device.</p>');
            });
    }

    public static function sendAction(): Action
    {
        return Action::make('send')
            ->label('Send Notification')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('danger')
            ->visible(fn (PushNotification $record): bool => Gate::allows('send', $record))
            ->requiresConfirmation()
            ->modalHeading(fn (PushNotification $record): string => $record->target_type === 'topics' ? 'Send to selected topics?' : 'Send to all active subscribers?')
            ->modalDescription(fn (PushNotification $record): string => 'This notification will be queued for approximately '.number_format(app(ManualPushNotificationService::class)->recipientCount($record)).' unique active subscribers. The send-time count is authoritative and fan-out cannot be recalled.')
            ->modalSubmitActionLabel('Queue Notification')
            ->action(function (PushNotification $record): void {
                Gate::authorize('send', $record);

                try {
                    $count = app(ManualPushNotificationService::class)->send($record);
                    Notification::make()->success()->title('Push notification queued')->body(number_format($count).' active subscriptions were selected for asynchronous fan-out.')->send();
                } catch (RuntimeException $exception) {
                    Notification::make()->danger()->title('Push notification was not queued')->body($exception->getMessage())->send();
                }
            });
    }

    /** @return Builder<PushNotification> */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('creator');
        if (auth()->user()?->can('view push analytics')) {
            $query->withCount([
                'deliveries as accepted_count' => fn (Builder $deliveries): Builder => $deliveries->where('status', 'accepted'),
                'deliveries as failed_delivery_count' => fn (Builder $deliveries): Builder => $deliveries->where('status', 'failed'),
                'deliveries as unique_clicks_count' => fn (Builder $deliveries): Builder => $deliveries->whereNotNull('first_clicked_at'),
            ]);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushNotifications::route('/'),
            'create' => CreatePushNotification::route('/create'),
            'edit' => EditPushNotification::route('/{record}/edit'),
        ];
    }
}
