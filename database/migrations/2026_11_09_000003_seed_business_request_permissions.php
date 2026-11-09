<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const ABILITIES = ['view_any', 'view', 'update'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::ABILITIES as $ability) {
            Permission::findOrCreate('business_requests.'.$ability, 'web');
        }
        foreach (Role::where('guard_name', 'web')->with('permissions')->get() as $role) {
            foreach (self::ABILITIES as $ability) {
                if ($role->name === 'super_admin' || ($role->name === 'admin' && $ability !== 'update')
                    || $role->permissions->contains('name', 'contractors.'.$ability)) {
                    $role->givePermissionTo('business_requests.'.$ability);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'web')->whereIn('name', array_map(fn (string $ability): string => 'business_requests.'.$ability, self::ABILITIES))->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
