<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'is_voided')) {
                $table->boolean('is_voided')->default(false)->index()->after('file_path');
            }
            if (!Schema::hasColumn('documents', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('is_voided');
            }
            if (!Schema::hasColumn('documents', 'voided_by')) {
                $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'voided_by')) {
                $table->dropForeign(['voided_by']);
                $table->dropColumn('voided_by');
            }
            if (Schema::hasColumn('documents', 'voided_at')) {
                $table->dropColumn('voided_at');
            }
            if (Schema::hasColumn('documents', 'is_voided')) {
                $table->dropIndex(['is_voided']);
                $table->dropColumn('is_voided');
            }
        });
    }
};
