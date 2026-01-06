<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('activities')) {
            return;
        }

        if (!Schema::hasColumn('activities', 'source')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->string('source', 32)->default('calendar')->index();
            });
        }

        DB::table('activities')
            ->whereNull('source')
            ->update(['source' => 'calendar']);

        DB::table('activities')
            ->where('subject', 'proforma_created')
            ->update(['source' => 'system']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('activities')) {
            return;
        }

        if (Schema::hasColumn('activities', 'source')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropIndex(['source']);
                $table->dropColumn('source');
            });
        }
    }
};
