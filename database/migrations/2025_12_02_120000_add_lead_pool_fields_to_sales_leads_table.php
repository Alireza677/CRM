<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_leads')) {
            return;
        }

        Schema::table('sales_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_leads', 'assigned_at')) {
                $table->dateTime('assigned_at')->nullable()->after('assigned_to')->index();
            }
            if (!Schema::hasColumn('sales_leads', 'first_activity_at')) {
                $table->dateTime('first_activity_at')->nullable()->after('assigned_at')->index();
            }
            if (!Schema::hasColumn('sales_leads', 'pool_status')) {
                $table->string('pool_status', 32)->default('in_pool')->after('first_activity_at')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_leads')) {
            return;
        }

        Schema::table('sales_leads', function (Blueprint $table) {
            if (Schema::hasColumn('sales_leads', 'pool_status')) {
                $table->dropIndex(['pool_status']);
                $table->dropColumn('pool_status');
            }
            if (Schema::hasColumn('sales_leads', 'first_activity_at')) {
                $table->dropIndex(['first_activity_at']);
                $table->dropColumn('first_activity_at');
            }
            if (Schema::hasColumn('sales_leads', 'assigned_at')) {
                $table->dropIndex(['assigned_at']);
                $table->dropColumn('assigned_at');
            }
        });
    }
};
