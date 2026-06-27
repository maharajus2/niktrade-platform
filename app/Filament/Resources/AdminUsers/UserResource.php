<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages\CreateUser;
use App\Filament\Resources\AdminUsers\Pages\EditUser;
use App\Filament\Resources\AdminUsers\Pages\ListUsers;
use App\Filament\Resources\AdminUsers\Schemas\UserForm;
use App\Filament\Resources\AdminUsers\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Пользователи админки';

    protected static ?string $modelLabel = 'Пользователь админки';

    protected static ?string $pluralModelLabel = 'Пользователи админки';

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
