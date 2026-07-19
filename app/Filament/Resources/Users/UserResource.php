<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\UserAdministration;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static string|UnitEnum|null $navigationGroup = 'Users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('username')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('slug')
                            ->label('Public author slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Preserved for WordPress compatibility; public URLs currently use username.'),
                        TextInput::make('mobile_number')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rule(Password::min(12)->mixedCase()->numbers()->symbols())
                            ->same('password_confirmation')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Toggle::make('is_public')
                            ->label('Public author profile')
                            ->default(true),
                    ]),
                Section::make('Profile')
                    ->columns(2)
                    ->schema([
                        TextInput::make('designation')
                            ->maxLength(255),
                        FileUpload::make('avatar_path')
                            ->label('Avatar')
                            ->image()
                            ->avatar()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public'),
                        Textarea::make('bio')
                            ->rows(5)
                            ->maxLength(2000)
                            ->helperText('Plain text only, up to 2,000 characters.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Social Links')
                    ->columns(2)
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('x_url')
                            ->label('X')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('youtube_url')
                            ->label('YouTube')
                            ->url()
                            ->maxLength(255),
                    ]),
                Section::make('Roles')
                    ->schema([
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->options(fn (): array => UserAdministration::assignableRoles(auth()->user())
                                ->pluck('name', 'id')
                                ->all())
                            ->visible(fn (?User $record): bool => Gate::allows('manageRoles', $record ?? User::class))
                            ->dehydrated(fn (?User $record): bool => Gate::allows('manageRoles', $record ?? User::class))
                            ->columns(2)
                            ->searchable()
                            ->bulkToggleable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Avatar')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('username')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mobile_number')
                    ->label('Mobile')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                SelectFilter::make('role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('verified')
                    ->label('Verified')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('email_verified_at'),
                    ),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label('Created from'),
                        DatePicker::make('created_until')->label('Created until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
