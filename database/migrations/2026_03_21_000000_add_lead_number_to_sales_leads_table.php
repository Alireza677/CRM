<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_leads')) {
            return;
        }

        Schema::table('sales_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_leads', 'lead_number')) {
                $table->string('lead_number', 20)->nullable()->unique()->after('id');
            }
        });

        $maxExisting = DB::table('sales_leads')
            ->whereNotNull('lead_number')
            ->selectRaw("MAX(CAST(SUBSTRING(lead_number, 2) AS UNSIGNED)) as max_seq")
            ->value('max_seq');

        $sequence = ((int) $maxExisting) + 1;

        $leads = DB::table('sales_leads')
            ->select('id')
            ->whereNull('lead_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($leads as $lead) {
            $code = 'L' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            DB::table('sales_leads')
                ->where('id', $lead->id)
                ->update(['lead_number' => $code]);
            $sequence++;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_leads')) {
            return;
        }

        Schema::table('sales_leads', function (Blueprint $table) {
            if (Schema::hasColumn('sales_leads', 'lead_number')) {
                $table->dropUnique(['lead_number']);
                $table->dropColumn('lead_number');
            }
        });
    }
};
