<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'merged_into_id')) {
                $table->foreignId('merged_into_id')
                    ->nullable()
                    ->constrained('organizations')
                    ->nullOnDelete();
                $table->index('merged_into_id', 'organizations_merged_into_id_index');
            }
            if (!Schema::hasColumn('organizations', 'merged_at')) {
                $table->timestamp('merged_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'merged_at')) {
                $table->dropColumn('merged_at');
            }
            if (Schema::hasColumn('organizations', 'merged_into_id')) {
                $table->dropForeign(['merged_into_id']);
                $table->dropIndex('organizations_merged_into_id_index');
                $table->dropColumn('merged_into_id');
            }
        });
    }
};
