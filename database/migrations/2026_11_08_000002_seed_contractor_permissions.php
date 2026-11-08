<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const ABILITIES = ['view_any', 'view', 'create', 'update', 'delete'];

    public function up(): void
    {
        if (! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::ABILITIES as $ability) {
            Permission::findOrCreate('contractors.'.$ability, 'web');
        }

        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('admin', 'web');
        foreach (Role::where('guard_name', 'web')->with('permissions')->get() as $role) {
            foreach (self::ABILITIES as $ability) {
                $implicitAccess = $role->name === 'super_admin'
                    || ($role->name === 'admin' && in_array($ability, ['view_any', 'view'], true));
                if ($implicitAccess || $role->permissions->contains('name', 'customers.'.$ability)) {
                    $role->givePermissionTo('contractors.'.$ability);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            return;
        }

        Permission::where('guard_name', 'web')->whereIn('name', array_map(
            fn (string $ability): string => 'contractors.'.$ability,
            self::ABILITIES,
        ))->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
