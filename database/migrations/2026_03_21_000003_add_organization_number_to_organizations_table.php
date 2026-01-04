<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('organizations')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'organization_number')) {
                $table->string('organization_number', 20)->nullable()->unique()->after('id');
            }
        });

        $maxExisting = DB::table('organizations')
            ->whereNotNull('organization_number')
            ->selectRaw("MAX(CAST(SUBSTRING(organization_number, 3) AS UNSIGNED)) as max_seq")
            ->value('max_seq');

        $sequence = ((int) $maxExisting) + 1;

        $organizations = DB::table('organizations')
            ->select('id')
            ->whereNull('organization_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($organizations as $organization) {
            $code = 'OR' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            DB::table('organizations')
                ->where('id', $organization->id)
                ->update(['organization_number' => $code]);
            $sequence++;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('organizations')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'organization_number')) {
                $table->dropUnique(['organization_number']);
                $table->dropColumn('organization_number');
            }
        });
    }
};
