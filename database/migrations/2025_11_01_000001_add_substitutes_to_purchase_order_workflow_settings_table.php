<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_order_workflow_settings')) {
            return;
        }

        Schema::table('purchase_order_workflow_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_workflow_settings', 'first_approver_substitute_id')) {
                $table->unsignedBigInteger('first_approver_substitute_id')->nullable()->after('first_approver_id');
            }
            if (! Schema::hasColumn('purchase_order_workflow_settings', 'second_approver_substitute_id')) {
                $table->unsignedBigInteger('second_approver_substitute_id')->nullable()->after('second_approver_id');
            }
            if (! Schema::hasColumn('purchase_order_workflow_settings', 'accounting_approver_substitute_id')) {
                $table->unsignedBigInteger('accounting_approver_substitute_id')->nullable()->after('accounting_user_id');
            }
        });

        Schema::table('purchase_order_workflow_settings', function (Blueprint $table) {
            // Add foreign keys in a separate call to avoid issues when columns already exist without FKs
            try {
                $table->foreign('first_approver_substitute_id', 'po_ws_first_sub_fk')->references('id')->on('users')->nullOnDelete();
            } catch (\Throwable $e) { /* ignore if already exists */
            }
            try {
                $table->foreign('second_approver_substitute_id', 'po_ws_second_sub_fk')->references('id')->on('users')->nullOnDelete();
            } catch (\Throwable $e) { /* ignore if already exists */
            }
            try {
                $table->foreign('accounting_approver_substitute_id', 'po_ws_acc_sub_fk')->references('id')->on('users')->nullOnDelete();
            } catch (\Throwable $e) { /* ignore if already exists */
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_order_workflow_settings')) {
            return;
        }

        Schema::table('purchase_order_workflow_settings', function (Blueprint $table) {
            // Drop FKs if exist
            foreach ([
                'po_ws_first_sub_fk',
                'po_ws_second_sub_fk',
                'po_ws_acc_sub_fk',
            ] as $fk) {
                try { $table->dropForeign($fk); } catch (\Throwable $e) { /* ignore */ }
            }
        });

        Schema::table('purchase_order_workflow_settings', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_workflow_settings', 'first_approver_substitute_id')) {
                $table->dropColumn('first_approver_substitute_id');
            }
            if (Schema::hasColumn('purchase_order_workflow_settings', 'second_approver_substitute_id')) {
                $table->dropColumn('second_approver_substitute_id');
            }
            if (Schema::hasColumn('purchase_order_workflow_settings', 'accounting_approver_substitute_id')) {
                $table->dropColumn('accounting_approver_substitute_id');
            }
        });
    }
};
