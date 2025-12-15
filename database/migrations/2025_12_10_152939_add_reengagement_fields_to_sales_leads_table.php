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
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->boolean('is_reengaged')
                ->default(false)
                ->after('pool_status');
            $table->timestamp('reengaged_at')
                ->nullable()
                ->after('is_reengaged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropColumn(['is_reengaged', 'reengaged_at']);
        });
    }
};
