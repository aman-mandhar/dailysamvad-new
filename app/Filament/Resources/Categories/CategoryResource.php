<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Tables\Columns\MediaImageColumn;
use App\Models\Category;
use App\Rules\ValidCategoryParent;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic')
                ->columns(2)
                ->schema([
                    Select::make('parent_id')
                        ->label('Parent Category')
                        ->options(fn (?Category $record): array => static::parentOptions($record))
                        ->searchable()
                        ->preload()
                        ->rules(fn (?Category $record): array => [new ValidCategoryParent($record)]),
                    TextInput::make('name')
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
                    Textarea::make('description')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
            Section::make('Display')
                ->columns(2)
                ->schema([
                    FileUpload::make('image_path')
                        ->label('Image')
                        ->image()
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('categories')
                        ->visibility('public'),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Toggle::make('is_active')->label('Active')->default(true),
                    Toggle::make('show_in_menu')->label('Show In Menu')->default(true),
                ]),
            Section::make('SEO')
                ->columns(2)
                ->schema([
                    TextInput::make('meta_title')->maxLength(255),
                    Textarea::make('meta_description')->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                MediaImageColumn::make('image_path')->label('Image')->disk('public'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('parent.name')->label('Parent')->placeholder('Root')->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('sort_order')->label('Sort Order')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                IconColumn::make('show_in_menu')->label('Menu')->boolean()->sortable(),
                TextColumn::make('posts_count')->label('Posts Count')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Updated At')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                SelectFilter::make('parent_id')
                    ->label('Parent Category')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('show_in_menu')->label('Menu Visibility'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    /** @return Builder<Category> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('parent')->withCount('posts');
    }

    /** @return array<int, string> */
    public static function parentOptions(?Category $record = null): array
    {
        $categories = Category::query()->select(['id', 'parent_id', 'name'])->orderBy('name')->get();
        $excluded = $record?->exists ? static::descendantIds($record, $categories) : [];
        $grouped = $categories->groupBy(fn (Category $category): string => (string) ($category->parent_id ?? 'root'));
        $options = [];

        $append = function (string $parent, string $prefix = '') use (&$append, &$options, $excluded, $grouped): void {
            foreach ($grouped->get($parent, collect()) as $category) {
                if (in_array($category->id, $excluded, true)) {
                    continue;
                }

                $options[$category->id] = $prefix.$category->name;
                $append((string) $category->id, $prefix.'— ');
            }
        };

        $append('root');

        return $options;
    }

    /** @param Collection<int, Category> $categories
     * @return array<int, int>
     */
    private static function descendantIds(Category $record, Collection $categories): array
    {
        $excluded = [$record->getKey()];

        do {
            $children = $categories
                ->whereIn('parent_id', $excluded)
                ->pluck('id')
                ->diff($excluded)
                ->values()
                ->all();
            $excluded = array_values(array_unique([...$excluded, ...$children]));
        } while ($children !== []);

        return $excluded;
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
