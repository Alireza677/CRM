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
        Schema::create('lead_round_robin_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sla_duration_value')->default(24);
            $table->enum('sla_duration_unit', ['minutes', 'hours'])->default('hours');
            $table->unsignedInteger('max_reassign_count')->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_round_robin_settings');
    }
};
