<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages\CreateUser;
use App\Filament\Resources\AdminUsers\Pages\EditUser;
use App\Filament\Resources\AdminUsers\Pages\ListUsers;
use App\Filament\Resources\AdminUsers\Pages\ViewUser;
use App\Filament\Resources\AdminUsers\Schemas\UserForm;
use App\Filament\Resources\AdminUsers\Tables\UsersTable;
use App\Models\User;
use App\Support\AdminRoles;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Сотрудники';

    protected static ?string $modelLabel = 'Сотрудник';

    protected static ?string $pluralModelLabel = 'Сотрудники';

    protected static string|\UnitEnum|null $navigationGroup = 'Система';

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Роль')
                    ->icon(fn (User $record): Heroicon => AdminRoles::primaryIcon($record))
                    ->iconColor(fn (User $record): string => AdminRoles::primaryColor($record))
                    ->description(fn (User $record): string => AdminRoles::primaryDescription($record))
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Назначенные роли')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => AdminRoles::label($state))
                            ->icon(fn (string $state): Heroicon => AdminRoles::icon($state))
                            ->color(fn (string $state): string => AdminRoles::color($state))
                            ->placeholder('Роль не назначена'),
                    ])
                    ->columnSpanFull(),

                Section::make('Данные сотрудника')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Имя'),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('created_at')
                            ->label('Создан')
                            ->dateTime('d.m.Y H:i'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canManageAdminUsers();
    }

    public static function canAccess(): bool
    {
        return static::canManageAdminUsers();
    }

    public static function canViewAny(): bool
    {
        return static::canManageAdminUsers();
    }

    public static function canCreate(): bool
    {
        return static::canUseAdminPermission('users.create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canUseAdminPermission('users.update');
    }

    public static function canView(Model $record): bool
    {
        return static::canUseAdminPermission('users.view');
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->id() === $record->getKey()) {
            return false;
        }

        return static::canUseAdminPermission('users.delete');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canManageAdminUsers(): bool
    {
        return static::canUseAdminPermission('users.view_any');
    }

    public static function canUseAdminPermission(string $permission): bool
    {
        try {
            $user = auth()->user();

            return $user !== null
                && ($user->hasRole('super_admin') || $user->can($permission));
        } catch (Throwable) {
            return false;
        }
    }
}
