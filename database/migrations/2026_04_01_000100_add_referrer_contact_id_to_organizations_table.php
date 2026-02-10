<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('organizations', 'referrer_contact_id')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->foreignId('referrer_contact_id')
                    ->nullable()
                    ->constrained('contacts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('organizations', 'referrer_contact_id')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropForeign(['referrer_contact_id']);
                $table->dropColumn('referrer_contact_id');
            });
        }
    }
};
