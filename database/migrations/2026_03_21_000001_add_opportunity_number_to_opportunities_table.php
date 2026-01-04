<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('opportunities')) {
            return;
        }

        Schema::table('opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('opportunities', 'opportunity_number')) {
                $table->string('opportunity_number', 20)->nullable()->unique()->after('id');
            }
        });

        $maxExisting = DB::table('opportunities')
            ->whereNotNull('opportunity_number')
            ->selectRaw("MAX(CAST(SUBSTRING(opportunity_number, 3) AS UNSIGNED)) as max_seq")
            ->value('max_seq');

        $sequence = ((int) $maxExisting) + 1;

        $opportunities = DB::table('opportunities')
            ->select('id')
            ->whereNull('opportunity_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($opportunities as $opportunity) {
            $code = 'OP' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            DB::table('opportunities')
                ->where('id', $opportunity->id)
                ->update(['opportunity_number' => $code]);
            $sequence++;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('opportunities')) {
            return;
        }

        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'opportunity_number')) {
                $table->dropUnique(['opportunity_number']);
                $table->dropColumn('opportunity_number');
            }
        });
    }
};
