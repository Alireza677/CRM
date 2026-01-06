<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('opportunities', 'primary_proforma_id')) {
                $table->foreignId('primary_proforma_id')
                    ->nullable()
                    ->constrained('proformas')
                    ->nullOnDelete()
                    ->after('contact_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'primary_proforma_id')) {
                $table->dropConstrainedForeignId('primary_proforma_id');
            }
        });
    }
};
