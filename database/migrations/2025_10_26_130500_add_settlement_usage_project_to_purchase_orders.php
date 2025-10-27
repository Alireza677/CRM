<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // نوع تسویه حساب: cash, credit, cheque
            if (!Schema::hasColumn('purchase_orders', 'settlement_type')) {
                $table->string('settlement_type')->nullable()->after('status');
            }

            // مورد استفاده: inventory, project, both
            if (!Schema::hasColumn('purchase_orders', 'usage_type')) {
                $table->string('usage_type')->nullable()->after('settlement_type');
            }

            // نام پروژه (وقتی usage_type = project|both)
            if (!Schema::hasColumn('purchase_orders', 'project_name')) {
                $table->string('project_name')->nullable()->after('usage_type');
            }
        });
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'project_name')) {
                $table->dropColumn('project_name');
            }
            if (Schema::hasColumn('purchase_orders', 'usage_type')) {
                $table->dropColumn('usage_type');
            }
            if (Schema::hasColumn('purchase_orders', 'settlement_type')) {
                $table->dropColumn('settlement_type');
            }
        });
    }
};

