<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
        
            // polymorphic relation to proforma OR opportunity
            $table->unsignedBigInteger('approvable_id');
            $table->string('approvable_type');
        
            // user who must approve
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
            // ✅ اضافه کردن ستونی که کم بود
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
        
            // approval details
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('approved_at')->nullable();
        
            $table->timestamps();
        
            // index for performance
            $table->index(['approvable_type', 'approvable_id']);
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
