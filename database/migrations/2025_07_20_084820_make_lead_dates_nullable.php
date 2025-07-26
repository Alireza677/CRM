<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->date('lead_date')->nullable()->change();
            $table->date('next_follow_up_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->date('lead_date')->nullable(false)->change();
            $table->date('next_follow_up_date')->nullable(false)->change();
        });
    }
};
