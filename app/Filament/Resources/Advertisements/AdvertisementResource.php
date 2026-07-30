<?php

namespace App\Filament\Resources\Advertisements;

use App\Enums\AdvertisementPosition;
use App\Enums\AdvertisementStatus;
use App\Filament\Resources\Advertisements\Pages\CreateAdvertisement;
use App\Filament\Resources\Advertisements\Pages\EditAdvertisement;
use App\Filament\Resources\Advertisements\Pages\ListAdvertisements;
use App\Filament\Resources\Advertisements\Pages\ViewAdvertisement;
use App\Models\Advertisement;
use App\Models\Media;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Monetization';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campaign')->columns(2)->schema([
                TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('advertiser_name')->maxLength(255), Textarea::make('description')->columnSpanFull(),
                Select::make('status')->options(AdvertisementStatus::options())->required()->default('draft'),
                TextInput::make('priority')->numeric()->integer()->default(0)->required(),
                TextInput::make('rotation_weight')->numeric()->integer()->minValue(1)->default(1)->required(),
            ]),
            Section::make('Destination and schedule')->columns(2)->schema([
                TextInput::make('target_url')->url()->maxLength(2048)->helperText('HTTP/HTTPS destination. Internal relative URLs can be set using frontend quick edit.'),
                DateTimePicker::make('start_at'), DateTimePicker::make('end_at')->after('start_at'),
                Checkbox::make('open_in_new_tab')->default(true), Checkbox::make('sponsored')->default(true), Checkbox::make('nofollow')->default(true),
            ]),
            Section::make('Creative')->schema([
                Repeater::make('creatives')->relationship()->maxItems(1)->defaultItems(1)->required()->schema([
                    Select::make('type')->options(['image' => 'Image', 'video' => 'Video', 'html' => 'Safe HTML', 'provider_code' => 'Trusted provider code'])->required()->live(),
                    Select::make('media_id')->label('Existing Media Library item')->options(fn () => Media::query()
                        ->whereNull('missing_at')
                        ->orderByDesc('id')
                        ->limit(500)
                        ->get(['id', 'original_filename', 'path'])
                        ->mapWithKeys(fn (Media $media): array => [
                            $media->getKey() => (string) ($media->original_filename ?: $media->path ?: "Media #{$media->getKey()}"),
                        ]))->searchable(),
                    FileUpload::make('image_path')->disk(config('media.disk', 'public'))->directory('advertisements/images')->image()->acceptedFileTypes(config('media.allowed_mime_types'))->maxSize(config('media.max_upload_kilobytes'))->visible(fn ($get) => $get('type') === 'image'),
                    FileUpload::make('video_path')->disk(config('media.disk', 'public'))->directory('advertisements/videos')->acceptedFileTypes(['video/mp4', 'video/webm'])->maxSize(51200)->visible(fn ($get) => $get('type') === 'video'),
                    FileUpload::make('poster_path')->disk(config('media.disk', 'public'))->directory('advertisements/posters')->image()->visible(fn ($get) => $get('type') === 'video'),
                    Textarea::make('html_code')->rows(8)->visible(fn ($get) => in_array($get('type'), ['html', 'provider_code'], true))->disabled(fn ($get) => $get('type') === 'provider_code' && ! (auth()->user()?->can('manage advertisement provider code') ?? false))->dehydrated(fn ($get) => $get('type') !== 'provider_code' || (auth()->user()?->can('manage advertisement provider code') ?? false)),
                    TextInput::make('alt_text')->maxLength(500), Textarea::make('caption')->maxLength(2000),
                    TextInput::make('width')->numeric()->integer()->minValue(1), TextInput::make('height')->numeric()->integer()->minValue(1),
                    Checkbox::make('autoplay'), Checkbox::make('muted')->default(true), Checkbox::make('loop'), Checkbox::make('controls')->default(true),
                ])->columns(2),
            ]),
            Section::make('Placements and targeting')->schema([
                Repeater::make('placements')->relationship()->minItems(1)->defaultItems(1)->schema([
                    Select::make('position')->options(AdvertisementPosition::options())->required()->searchable(),
                    Select::make('page_type')->options(['home' => 'Home', 'article' => 'Article', 'category' => 'Category', 'tag' => 'Tag', 'search' => 'Search', 'date' => 'Date archive', 'author' => 'Author', 'footer' => 'Footer'])->placeholder('All page types'),
                    Select::make('device')->options(['all' => 'All', 'desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'])->default('all')->required(),
                    Select::make('category_id')->relationship('category', 'name')->searchable(), Select::make('tag_id')->relationship('tag', 'name')->searchable(), Select::make('post_id')->relationship('post', 'title')->searchable(),
                ])->columns(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable(), TextColumn::make('advertiser_name')->searchable()->placeholder('—'),
            TextColumn::make('placements.position')->badge()->limitList(3), TextColumn::make('creative.type')->badge(), TextColumn::make('status')->badge(),
            TextColumn::make('priority')->sortable(), TextColumn::make('start_at')->dateTime()->sortable()->placeholder('—'), TextColumn::make('end_at')->dateTime()->sortable()->placeholder('—'),
            TextColumn::make('impressions')->numeric(), TextColumn::make('clicks')->numeric(), TextColumn::make('ctr')->suffix('%'),
            TextColumn::make('updater.name')->label('Updated by')->placeholder('—'), TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(AdvertisementStatus::options()),
            SelectFilter::make('creative_type')->options(['image' => 'Image', 'video' => 'Video', 'html' => 'HTML', 'provider_code' => 'Provider code'])->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $q, string $value) => $q->whereHas('creatives', fn (Builder $c) => $c->where('type', $value)))),
            SelectFilter::make('position')->options(AdvertisementPosition::options())->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $q, string $value) => $q->whereHas('placements', fn (Builder $p) => $p->where('position', $value)))),
            TernaryFilter::make('expired')->queries(true: fn (Builder $q) => $q->where('end_at', '<=', now()), false: fn (Builder $q) => $q->where(fn (Builder $q) => $q->whereNull('end_at')->orWhere('end_at', '>', now()))),
            TernaryFilter::make('trashed')->queries(true: fn (Builder $q) => $q->onlyTrashed(), false: fn (Builder $q) => $q->withoutTrashed(), blank: fn (Builder $q) => $q->withTrashed()),
        ])->recordActions([
            ViewAction::make(), EditAction::make(),
            Action::make('duplicate')->visible(fn (Advertisement $record) => auth()->user()?->can('create', Advertisement::class) ?? false)->action(function (Advertisement $record) {
                $copy = $record->replicate(['uuid', 'slug', 'published_at']);
                $copy->title .= ' (Copy)';
                $copy->slug .= '-copy-'.Str::lower(Str::random(5));
                $copy->status = AdvertisementStatus::Draft;
                $copy->created_by = auth()->id();
                $copy->save();
                foreach ($record->creatives as $creative) {
                    $copy->creatives()->create($creative->only($creative->getFillable()));
                } foreach ($record->placements as $placement) {
                    $copy->placements()->create($placement->only($placement->getFillable()));
                }
            }),
            Action::make('publish')->visible(fn (Advertisement $record) => auth()->user()?->can('publish', $record) ?? false)->requiresConfirmation()->action(fn (Advertisement $record) => $record->update(['status' => 'active', 'published_by' => auth()->id(), 'published_at' => now()])),
            Action::make('pause')->visible(fn (Advertisement $record) => auth()->user()?->can('pause', $record) ?? false)->action(fn (Advertisement $record) => $record->update(['status' => 'paused'])),
            DeleteAction::make()->requiresConfirmation(), RestoreAction::make(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class])->with(['creative', 'creatives', 'placements', 'updater']);
    }

    public static function getPages(): array
    {
        return ['index' => ListAdvertisements::route('/'), 'create' => CreateAdvertisement::route('/create'), 'view' => ViewAdvertisement::route('/{record}'), 'edit' => EditAdvertisement::route('/{record}/edit')];
    }
}
