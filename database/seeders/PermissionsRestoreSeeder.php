<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsRestoreSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // Derived from RolePermissionSeeder and code usage
        $prefixes = [
            'leads',
            'opportunities',
            'contacts',
            'organizations',
            'proformas',
            'purchase_orders',
            'approvals',
            'invoices',
            'payments',
            'documents',
            'tickets',
            'workorders',
            'reports',
            'users',
            'roles',
            'settings',
        ];

        $actions = [
            'view.own',
            'view.team',
            'view.department',
            'view.company',
            'create',
            'update.own',
            'update.team',
            'update.department',
            'delete.own',
            'reassign',
            'export',
        ];

        foreach ($prefixes as $prefix) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => $prefix . '.' . $action,
                    'guard_name' => $guard,
                ]);
            }
        }

        $extra = [
            'reports.view',
            'reports.sales.department',
            'reports.sales.own',
            'reports.finance.department',
        ];
        foreach ($extra as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $documentCategoryPerms = [
            'purchase_documents.view',
            'purchase_documents.download',
            'opportunity_documents.view',
            'opportunity_documents.download',
        ];
        foreach ($documentCategoryPerms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        // Assign all permissions to the top manager role if present
        $managerRole = Role::where('name', 'مدیر کل')->first() ?? Role::where('name', 'admin')->first();
        if ($managerRole) {
            $managerRole->syncPermissions(Permission::all());
        }
    }
}
