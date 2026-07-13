<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const GUARD = 'web';

    /**
     * @return list<string>
     */
    private function permissions(): array
    {
        return [
            'tasks.view_any',
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'tasks.archive',
            'tasks.assign',
            'tasks.assign_to_subordinates',
            'tasks.manage_boards',
            'tasks.comment',
            'tasks.hold',
            'tasks.resume',
            'tasks.complete',
            'tasks.view_department',
            'tasks.view_all',
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

        Role::findOrCreate('super_admin', self::GUARD)->givePermissionTo($this->permissions());

        Role::findOrCreate('admin', self::GUARD)->givePermissionTo([
            'tasks.view_any',
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.archive',
            'tasks.assign',
            'tasks.assign_to_subordinates',
            'tasks.manage_boards',
            'tasks.comment',
            'tasks.hold',
            'tasks.resume',
            'tasks.complete',
            'tasks.view_department',
        ]);

        Role::findOrCreate('hr', self::GUARD)->givePermissionTo([
            'tasks.view_any',
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.assign_to_subordinates',
            'tasks.comment',
            'tasks.hold',
            'tasks.resume',
            'tasks.complete',
        ]);

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
