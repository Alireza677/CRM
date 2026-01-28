<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_event_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('event');
            $table->string('sound_path')->nullable();
            $table->string('icon_path')->nullable();
            $table->boolean('sound_enabled')->default(true);
            $table->timestamps();

            $table->unique(['module', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_event_settings');
    }
};
