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
            $table->unsignedInteger('auto_reassign_count')->default(0)->after('pool_status');
        });

        \DB::table('sales_leads')->whereNull('auto_reassign_count')->update(['auto_reassign_count' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropColumn('auto_reassign_count');
        });
    }
};
