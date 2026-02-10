<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_leads', 'referrer_contact_id')) {
                $table->foreignId('referrer_contact_id')
                    ->nullable()
                    ->after('contact_id')
                    ->constrained('contacts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (Schema::hasColumn('sales_leads', 'referrer_contact_id')) {
                $table->dropForeign(['referrer_contact_id']);
                $table->dropColumn('referrer_contact_id');
            }
        });
    }
};
