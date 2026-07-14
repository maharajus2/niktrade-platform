<?php

namespace App\Filament\Resources\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

trait UsesResourcePermissions
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::canUseResourcePermission('view_any');
    }

    public static function canView(Model $record): bool
    {
        return static::canUseResourcePermission('view');
    }

    public static function canCreate(): bool
    {
        return static::canUseResourcePermission('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canUseResourcePermission('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canUseResourcePermission('delete');
    }

    protected static function canUseResourcePermission(string $ability): bool
    {
        try {
            $user = auth()->user();

            if (! $user instanceof User) {
                return false;
            }

            if ($user->hasRole('super_admin')) {
                return true;
            }

            if ($user->hasRole('admin') && in_array($ability, ['view_any', 'view'], true)) {
                return true;
            }

            return $user->can(static::permissionPrefix().'.'.$ability);
        } catch (Throwable) {
            return false;
        }
    }

    protected static function permissionPrefix(): string
    {
        return static::$permissionPrefix;
    }
}
