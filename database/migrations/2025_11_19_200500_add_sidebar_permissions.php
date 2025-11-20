<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $general = [
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

        $adminOnly = [
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

        $all = array_merge($general, $adminOnly);

        foreach ($all as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roles = Role::all();
        foreach ($roles as $role) {
            if ($role->name === 'admin') {
                $role->givePermissionTo($all);
                continue;
            }

            $role->givePermissionTo($general);
        }
    }

    public function down(): void
    {
        $names = [
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
            'sidebar_calendar_holidays.view',
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
            'sidebar_settings.view',
            'sidebar_settings_general.view',
            'sidebar_settings_users.view',
            'sidebar_settings_workflows.view',
            'sidebar_settings_automation.view',
            'sidebar_settings_notifications.view',
            'sidebar_settings_roles_matrix.view',
            'sidebar_settings_roles_report.view',
            'sidebar_documents_all.view',
            'sidebar_documents_sms.view',
            'sidebar_reports_dashboard.view',
            'sidebar_reports_all.view',
        ];

        Permission::whereIn('name', $names)->delete();
    }
};
