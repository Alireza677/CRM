<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_assignments')) {
            return;
        }

        Schema::create('role_assignments', function (Blueprint $table) {
            $table->id();
            $table->morphs('assignable'); // assignable_type, assignable_id
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_type', 50);
            $table->string('level', 1)->nullable(); // A / B / C
            $table->decimal('base_commission_percent', 5, 2)->nullable();
            $table->decimal('final_commission_amount', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['assignable_type', 'assignable_id', 'role_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
    }
};
