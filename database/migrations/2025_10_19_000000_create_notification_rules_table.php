<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('event');
            $table->boolean('enabled')->default(false);
            $table->json('channels');
            $table->json('conditions')->nullable();
            $table->text('subject_template');
            $table->longText('body_template');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['module','event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};

