<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_leads')) {
            Schema::table('sales_leads', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_leads', 'status')) {
                    $table->string('status')->default('new')->after('lead_status');
                }
                if (!Schema::hasColumn('sales_leads', 'disqualify_reason')) {
                    $table->string('disqualify_reason')->nullable()->after('status');
                }
            });

            if (Schema::hasColumn('sales_leads', 'status') && Schema::hasColumn('sales_leads', 'lead_status')) {
                DB::statement("UPDATE sales_leads SET status = lead_status WHERE status IS NULL OR status = ''");
            }
        }

        if (Schema::hasTable('opportunities')) {
            Schema::table('opportunities', function (Blueprint $table) {
                if (!Schema::hasColumn('opportunities', 'lost_reason')) {
                    $table->string('lost_reason')->nullable()->after('stage');
                }
                if (!Schema::hasColumn('opportunities', 'stage')) {
                    $table->string('stage')->nullable()->after('contact_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_leads')) {
            Schema::table('sales_leads', function (Blueprint $table) {
                if (Schema::hasColumn('sales_leads', 'disqualify_reason')) {
                    $table->dropColumn('disqualify_reason');
                }
                if (Schema::hasColumn('sales_leads', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('opportunities')) {
            Schema::table('opportunities', function (Blueprint $table) {
                if (Schema::hasColumn('opportunities', 'lost_reason')) {
                    $table->dropColumn('lost_reason');
                }
                // do not drop stage if it existed before
            });
        }
    }
};
