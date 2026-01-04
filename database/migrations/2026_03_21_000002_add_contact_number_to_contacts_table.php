<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contacts')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'contact_number')) {
                $table->string('contact_number', 20)->nullable()->unique()->after('id');
            }
        });

        $maxExisting = DB::table('contacts')
            ->whereNotNull('contact_number')
            ->selectRaw("MAX(CAST(SUBSTRING(contact_number, 3) AS UNSIGNED)) as max_seq")
            ->value('max_seq');

        $sequence = ((int) $maxExisting) + 1;

        $contacts = DB::table('contacts')
            ->select('id')
            ->whereNull('contact_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($contacts as $contact) {
            $code = 'CO' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            DB::table('contacts')
                ->where('id', $contact->id)
                ->update(['contact_number' => $code]);
            $sequence++;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('contacts')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'contact_number')) {
                $table->dropUnique(['contact_number']);
                $table->dropColumn('contact_number');
            }
        });
    }
};
