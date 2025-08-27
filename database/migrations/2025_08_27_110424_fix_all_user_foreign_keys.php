<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // جدول‌ها و ستون‌هایی که باید اصلاح بشن
        $refs = [
            ['activities', 'user_id', 'activities_user_id_foreign'],
            ['approvals', 'approved_by', 'approvals_approved_by_foreign'],
            ['approvals', 'user_id', 'approvals_user_id_foreign'],
            ['automation_rules', 'emergency_approver_id', 'automation_rules_emergency_approver_id_foreign'],
            ['automation_rule_approvers', 'user_id', 'automation_rule_approvers_user_id_foreign'],
            ['automation_rule_user', 'user_id', 'automation_rule_user_user_id_foreign'],
            ['calls', 'user_id', 'calls_user_id_foreign'],
            ['contacts', 'assigned_to', 'contacts_assigned_to_foreign'],
            ['customers', 'created_by', 'customers_created_by_foreign'],
            ['documents', 'user_id', 'documents_user_id_foreign'],
            ['forms', 'assigned_to', 'forms_assigned_to_foreign'],
            ['leads', 'assigned_to', 'leads_assigned_to_foreign'],
            ['notes', 'user_id', 'notes_user_id_foreign'],
            ['opportunities', 'assigned_to', 'opportunities_assigned_to_foreign'],
            ['opportunity_notes', 'user_id', 'opportunity_notes_user_id_foreign'],
            ['organizations', 'assigned_to', 'organizations_assigned_to_foreign'],
            ['price_lists', 'user_id', 'price_lists_user_id_foreign'],
            ['proformas', 'assigned_to', 'proformas_assigned_to_foreign'],
            ['projects', 'manager_id', 'projects_manager_id_foreign'],
            ['project_user', 'user_id', 'project_user_user_id_foreign'],
            ['purchase_orders', 'assigned_to', 'purchase_orders_assigned_to_foreign'],
            ['quotations', 'assigned_to', 'quotations_assigned_to_foreign'],
            ['quotations', 'product_manager', 'quotations_product_manager_foreign'],
            ['sales_leads', 'assigned_to', 'sales_leads_assigned_to_foreign'],
            ['sales_leads', 'created_by', 'sales_leads_created_by_foreign'],
            ['sales_leads', 'referred_to', 'sales_leads_referred_to_foreign'],
            ['suppliers', 'assigned_to', 'suppliers_assigned_to_foreign'],
            ['tasks', 'assigned_to', 'tasks_assigned_to_foreign'],
        ];

        foreach ($refs as [$table, $column, $constraint]) {
            // حذف FK قدیمی
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");

            // nullable کردن ستون (فرض: BIGINT UNSIGNED)
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NULL");

            // افزودن FK جدید با رفتار امن
            DB::statement("
                ALTER TABLE `{$table}`
                ADD CONSTRAINT `{$constraint}`
                FOREIGN KEY (`{$column}`)
                REFERENCES `users`(`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE
            ");
        }
    }

    public function down(): void
    {
        // اینجا میشه برگردوند به حالت قبلی، ولی چون دقیقاً نمی‌دونیم همه قبلاً چطور بودن، خالی می‌ذاریم
    }
};
