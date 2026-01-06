<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_settings')) {
            return;
        }

        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->json('role_percents')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};
