<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminRolePermissionSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->permissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        foreach ($this->roles($permissions) as $roleName => $rolePermissions) {
            Role::findOrCreate($roleName, self::GUARD)
                ->syncPermissions($rolePermissions);
        }

        User::query()
            ->orderBy('id')
            ->first()
            ?->assignRole('super_admin');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissions(): array
    {
        return [
            'orders.view_any',
            'orders.view',
            'orders.update',
            'orders.delete',
            'orders.status.update',
            'orders.status.cancel',
            'orders.status.complete',
            'orders.archive',
            'orders.unarchive',
            'orders.kanban.view',
            'orders.payment.update',
            'orders.fulfillment.update',
            'customers.view_any',
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'customers.addresses.manage',
            'products.view_any',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'brands.view_any',
            'brands.view',
            'brands.create',
            'brands.update',
            'brands.delete',
            'categories.view_any',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'product_lines.view_any',
            'product_lines.view',
            'product_lines.create',
            'product_lines.update',
            'product_lines.delete',
            'product_types.view_any',
            'product_types.view',
            'product_types.create',
            'product_types.update',
            'product_types.delete',
            'certificates.view_any',
            'certificates.view',
            'certificates.create',
            'certificates.update',
            'certificates.delete',
            'warehouses.view_any',
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.delete',
            'dashboard.view',
            'widgets.sales.view',
            'widgets.products.view',
            'widgets.orders_sla.view',
            'users.view_any',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'employees.view_any',
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.archive',
            'employees.hr.view',
            'employees.hr.update',
            'employees.salary.view',
            'employees.salary.update',
            'employees.probation.manage',
            'roles.view_any',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
        ];
    }

    private function roles(array $permissions): array
    {
        return [
            'super_admin' => $permissions,
            'admin' => array_values(array_diff($permissions, [
                'users.delete',
                'employees.hr.view',
                'employees.hr.update',
                'employees.salary.view',
                'employees.salary.update',
                'employees.probation.manage',
                'roles.create',
                'roles.update',
                'roles.delete',
            ])),
            'order_manager' => [
                'orders.view_any',
                'orders.view',
                'orders.update',
                'orders.status.update',
                'orders.status.cancel',
                'orders.status.complete',
                'orders.archive',
                'orders.unarchive',
                'orders.kanban.view',
                'orders.fulfillment.update',
                'customers.view_any',
                'customers.view',
                'warehouses.view_any',
                'warehouses.view',
                'dashboard.view',
                'widgets.orders_sla.view',
            ],
            'picker' => [
                'orders.view_any',
                'orders.view',
                'orders.kanban.view',
                'orders.status.update',
                'dashboard.view',
            ],
            'courier' => [
                'orders.view_any',
                'orders.view',
                'orders.fulfillment.update',
                'dashboard.view',
            ],
            'pickup_operator' => [
                'orders.view_any',
                'orders.view',
                'orders.fulfillment.update',
                'warehouses.view_any',
                'warehouses.view',
                'dashboard.view',
            ],
            'content_manager' => array_merge(
                $this->matching($permissions, [
                    'products.',
                    'brands.',
                    'categories.',
                    'product_lines.',
                    'product_types.',
                    'certificates.',
                ]),
                [
                    'dashboard.view',
                    'widgets.products.view',
                ],
            ),
            'accountant' => [
                'orders.view_any',
                'orders.view',
                'orders.payment.update',
                'customers.view_any',
                'customers.view',
                'dashboard.view',
                'widgets.sales.view',
            ],
            'hr' => [
                'employees.view_any',
                'employees.view',
                'employees.create',
                'employees.update',
                'employees.archive',
                'employees.hr.view',
                'employees.hr.update',
                'employees.salary.view',
                'employees.salary.update',
                'employees.probation.manage',
                'users.view_any',
                'users.view',
                'users.create',
                'users.update',
                'dashboard.view',
            ],
        ];
    }

    private function matching(array $permissions, array $prefixes): array
    {
        return array_values(array_filter(
            $permissions,
            fn (string $permission): bool => collect($prefixes)->contains(
                fn (string $prefix): bool => str_starts_with($permission, $prefix),
            ),
        ));
    }
}
