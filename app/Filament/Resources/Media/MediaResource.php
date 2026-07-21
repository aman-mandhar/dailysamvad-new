<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Tables\Columns\MediaImageColumn;
use App\Models\Media;
use App\Support\Authorization\ContentAccess;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Media';

    protected static ?string $recordTitleAttribute = 'original_filename';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Image')->schema([
                FileUpload::make('upload')->image()->storeFiles(false)->requiredOn('create')
                    ->acceptedFileTypes(config('media.allowed_mime_types'))->maxSize(config('media.max_upload_kilobytes'))
                    ->helperText('JPEG, PNG, GIF, or WebP. SVG and executable files are rejected.')
                    ->visibleOn('create'),
                TextInput::make('path')->disabled()->dehydrated(false)->visibleOn('edit'),
            ]),
            Section::make('Metadata')->columns(2)->schema([
                TextInput::make('alt_text')->maxLength(500),
                TextInput::make('credit')->maxLength(255),
                Textarea::make('caption')->maxLength(2000)->columnSpanFull(),
                TextInput::make('copyright')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultPaginationPageOption(25)->columns([
            MediaImageColumn::make('path')->label('Preview')->disk(fn (Media $record): string => $record->disk)->square(),
            TextColumn::make('original_filename')->label('Filename')->searchable()->placeholder(fn (Media $record): string => basename($record->path))->limit(40),
            TextColumn::make('alt_text')->searchable()->limit(40)->placeholder('—'),
            TextColumn::make('mime_type')->label('MIME')->sortable(),
            TextColumn::make('dimensions')->state(fn (Media $record): string => $record->width && $record->height ? "{$record->width}×{$record->height}" : '—'),
            TextColumn::make('size')->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1).' KB' : '—')->sortable(),
            TextColumn::make('origin')->state(fn (Media $record): string => $record->old_wp_id ? 'WordPress' : 'Laravel')->badge(),
            TextColumn::make('old_wp_id')->label('WP ID')->placeholder('—')->sortable(),
            TextColumn::make('uploader.name')->label('Uploader')->placeholder('—'),
            TextColumn::make('featured_posts_count')->label('References')->numeric()->sortable(),
            IconColumn::make('missing_at')->label('Missing')->state(fn (Media $record): bool => $record->missing_at !== null)->boolean(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('type')->options(['image/jpeg' => 'JPEG', 'image/png' => 'PNG', 'image/gif' => 'GIF', 'image/webp' => 'WebP'])->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, string $mime): Builder => $query->where('mime_type', $mime))),
            TernaryFilter::make('wordpress')->queries(true: fn (Builder $query) => $query->whereNotNull('old_wp_id'), false: fn (Builder $query) => $query->whereNull('old_wp_id')),
            TernaryFilter::make('missing')->queries(true: fn (Builder $query) => $query->whereNotNull('missing_at'), false: fn (Builder $query) => $query->whereNull('missing_at')),
            TernaryFilter::make('referenced')->queries(true: fn (Builder $query) => $query->has('featuredPosts'), false: fn (Builder $query) => $query->doesntHave('featuredPosts')),
            Filter::make('created_at')->label('Uploaded this month')->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->startOfMonth())),
            TernaryFilter::make('trashed')->label('Deleted')->queries(true: fn (Builder $query) => $query->onlyTrashed(), false: fn (Builder $query) => $query->withoutTrashed(), blank: fn (Builder $query) => $query->withTrashed()),
        ])->recordActions([EditAction::make(), DeleteAction::make()->requiresConfirmation(), RestoreAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return ContentAccess::scopeMedia(
            parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]),
            auth()->user(),
        )->with('uploader')->withCount('featuredPosts');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class])->with('uploader')->withCount('featuredPosts');
    }

    public static function getPages(): array
    {
        return ['index' => ListMedia::route('/'), 'create' => CreateMedia::route('/create'), 'edit' => EditMedia::route('/{record}/edit')];
    }
}
