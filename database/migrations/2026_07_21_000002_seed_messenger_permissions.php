<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const GUARD = 'web';

    private function permissions(): array
    {
        return [
            'messenger.view',
            'messenger.create_direct',
            'messenger.create_group',
            'messenger.create_department',
            'messenger.send',
            'messenger.attach_files',
            'messenger.manage_conversation',
            'messenger.archive',
            'messenger.view_all',
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        $employeePermissions = [
            'messenger.view',
            'messenger.create_direct',
            'messenger.send',
            'messenger.attach_files',
        ];

        Role::findOrCreate('super_admin', self::GUARD)->givePermissionTo($this->permissions());
        Role::findOrCreate('admin', self::GUARD)->givePermissionTo(array_merge($employeePermissions, [
            'messenger.create_group',
            'messenger.create_department',
            'messenger.manage_conversation',
            'messenger.archive',
        ]));
        Role::findOrCreate('hr', self::GUARD)->givePermissionTo(array_merge($employeePermissions, [
            'messenger.create_group',
            'messenger.create_department',
        ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $permission) {
            Permission::query()
                ->where('name', $permission)
                ->where('guard_name', self::GUARD)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
