<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'finance',
            'admin_manager',
            'admin_staff',
            'factory_supervisor',
            'support',
            'sales_manager',
            'salesperson',
            'sales_agent',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Global permission keys for core modules
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
            'update.company',
            'delete.own',
            'reassign',
            'export', // optional
        ];

        foreach ($prefixes as $prefix) {
            foreach ($actions as $action) {
                $name = $prefix . '.' . $action;
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Additional granular report permissions referenced in matrix
        $extra = [
            'reports.view',
            'reports.sales.department',
            'reports.sales.own',
            'reports.finance.department',
        ];
        foreach ($extra as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $documentCategoryPerms = [
            'purchase_documents.view',
            'purchase_documents.download',
            'opportunity_documents.view',
            'opportunity_documents.download',
        ];
        foreach ($documentCategoryPerms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Lightweight feature toggles
        $featurePerms = [
            'chat.view',
            'online_meetings.view',
        ];
        foreach ($featurePerms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $sidebarGeneral = [
            'sidebar_dashboard.view',
            'sidebar_marketing.view',
            'sidebar_sales.view',
            'sidebar_projects.view',
            'sidebar_inventory.view',
            'sidebar_support.view',
            'sidebar_documents.view',
            'sidebar_calendar.view',
            'sidebar_employee_portal.view',
            'sidebar_calendar_index.view',
            'sidebar_sales_opportunities.view',
            'sidebar_sales_contacts.view',
            'sidebar_sales_organizations.view',
            'sidebar_sales_proformas.view',
            'sidebar_marketing_leads.view',
            'sidebar_projects_list.view',
            'sidebar_inventory_products.view',
            'sidebar_inventory_suppliers.view',
            'sidebar_inventory_purchase_orders.view',
            'sidebar_support_after_sales.view',
            'sidebar_support_phone_calls.view',
            'sidebar_documents_all.view',
            'sidebar_documents_sms.view',
            'sidebar_reports_dashboard.view',
            'sidebar_reports_all.view',
        ];

        $sidebarAdmin = [
            'sidebar_settings.view',
            'sidebar_calendar_holidays.view',
            'sidebar_settings_general.view',
            'sidebar_settings_users.view',
            'sidebar_settings_workflows.view',
            'sidebar_settings_automation.view',
            'sidebar_settings_notifications.view',
            'sidebar_settings_roles_matrix.view',
            'sidebar_settings_roles_report.view',
        ];

        foreach (array_merge($sidebarGeneral, $sidebarAdmin) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Helper to ensure a permission exists and return its model
        $ensure = function (string $name) {
            return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        };

        // Matrix: role => permissions
        $matrix = [
            'admin' => ['*'], // all

            'sales_manager' => [
                'leads.view.department', 'leads.create', 'leads.update.department', 'leads.reassign',
                'opportunities.view.department', 'opportunities.create', 'opportunities.update.department',
                'contacts.view.department', 'contacts.create', 'contacts.update.department',
                'organizations.view.department', 'organizations.create', 'organizations.update.department',
                'proformas.view.department', 'proformas.create', 'proformas.update.department',
                'approvals.view.department',
                // reports.view OR reports.sales.department
                'reports.view', 'reports.sales.department',
            ],

            'salesperson' => [
                'leads.view.own', 'leads.create', 'leads.update.own', 'leads.delete.own',
                'opportunities.view.team', 'opportunities.create', 'opportunities.update.own', 'opportunities.delete.own',
                'contacts.view.team', 'contacts.create', 'contacts.update.own',
                'organizations.view.team',
                'proformas.view.team', 'proformas.create', 'proformas.update.own',
                // optional split
                'reports.sales.own',
            ],

            'sales_agent' => [
                // same as salesperson but opportunities.view.own (stricter)
                'leads.view.own', 'leads.create', 'leads.update.own', 'leads.delete.own',
                'opportunities.view.own', 'opportunities.create', 'opportunities.update.own', 'opportunities.delete.own',
                'contacts.view.team', 'contacts.create', 'contacts.update.own',
                'organizations.view.team',
                'proformas.view.team', 'proformas.create', 'proformas.update.own',
                'reports.sales.own',
            ],

        'finance' => [
            'proformas.view.department', 'proformas.create', 'proformas.update.department',
            'approvals.view.department',
            'invoices.view.department', 'invoices.create', 'invoices.update.department',
            'payments.view.department', 'payments.create', 'payments.update.department',
            'reports.finance.department',
            // Purchase orders (inventory)
            'purchase_orders.view.department', 'purchase_orders.create', 'purchase_orders.update.department',
        ],

            'factory_supervisor' => [
                'workorders.view.department', 'workorders.create', 'workorders.update.department',
                'opportunities.view.department',
                'proformas.view.department',
                'documents.view.department',
                'purchase_documents.view', 'purchase_documents.download',
                'opportunity_documents.view', 'opportunity_documents.download',
            ],

            'support' => [
                'tickets.view.department', 'tickets.create', 'tickets.update.team', 'tickets.delete.own',
                'contacts.view.department',
                'documents.view.department',
                'purchase_documents.view', 'purchase_documents.download',
                'opportunity_documents.view', 'opportunity_documents.download',
            ],

            // Admin area roles (if present): users/roles/settings
            'admin_manager' => [
                'users.view.department', 'users.create', 'users.update.department',
                'roles.view.department', 'roles.create', 'roles.update.department',
                'settings.view.department', 'settings.update.department',
            ],
            'admin_staff' => [
                'users.view.team', 'users.create', 'users.update.team',
                'roles.view.team',
                'settings.view.team',
            ],
        ];

        foreach ($matrix as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            if (in_array('*', $perms, true)) {
                $role->syncPermissions(Permission::all());
                continue;
            }
            $perms = array_merge($perms, $sidebarGeneral, $featurePerms);
            $permModels = [];
            foreach ($perms as $p) {
                $permModels[] = $ensure($p);
            }
            $role->syncPermissions($permModels);
        }

        // Ensure current admin users get the 'admin' role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        User::where('is_admin', true)->get()->each(function (User $u) use ($adminRole) {
            if (!$u->hasRole($adminRole->name)) {
                $u->assignRole($adminRole);
            }
        });
    }
}
