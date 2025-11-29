<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
        });

        Schema::table('sales_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_leads', 'contact_id')) {
                $table->foreignId('contact_id')
                    ->nullable()
                    ->after('assigned_to')
                    ->constrained('contacts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (Schema::hasColumn('sales_leads', 'contact_id')) {
                $table->dropForeign(['contact_id']);
                $table->dropColumn('contact_id');
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'website')) {
                $table->dropColumn('website');
            }
        });
    }
};
