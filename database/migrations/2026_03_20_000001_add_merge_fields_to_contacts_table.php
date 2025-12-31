<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'merged_into_id')) {
                $column = $table->foreignId('merged_into_id')
                    ->nullable()
                    ->constrained('contacts')
                    ->nullOnDelete();
                if (Schema::hasColumn('contacts', 'visibility')) {
                    $column->after('visibility');
                }
                $table->index('merged_into_id', 'contacts_merged_into_id_index');
            }
            if (!Schema::hasColumn('contacts', 'merged_at')) {
                $table->timestamp('merged_at')->nullable();
            }
            if (!Schema::hasColumn('contacts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'merged_at')) {
                $table->dropColumn('merged_at');
            }
            if (Schema::hasColumn('contacts', 'merged_into_id')) {
                $table->dropForeign(['merged_into_id']);
                $table->dropIndex('contacts_merged_into_id_index');
                $table->dropColumn('merged_into_id');
            }
            if (Schema::hasColumn('contacts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
