<?php

namespace App\Filament\Resources\Posts;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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
                        ->relationship('author', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('status')
                        ->options(static::statusOptions())
                        ->default(PostStatus::Draft->value)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(['title', 'slug'])
                    ->sortable(),
                TextColumn::make('author.name')->label('Author')->placeholder('—'),
                TextColumn::make('status')
                    ->formatStateUsing(fn (PostStatus $state): string => $state->value)
                    ->badge(),
                TextColumn::make('language'),
                TextColumn::make('published_at')
                    ->label('Published At')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(static::statusOptions()),
                SelectFilter::make('author_id')
                    ->label('Author')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('language')->options([
                    'hi' => 'Hindi',
                    'pa' => 'Punjabi',
                    'en' => 'English',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /** @return Builder<Post> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('author');
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
