<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_blacklists', function (Blueprint $table) {
            $table->id();
            // شماره‌ها را نرمالایز کن (E.164)؛ طول 20 کافی است: +98912...
            $table->string('mobile', 20)->unique();
            $table->string('reason', 100)->nullable();   // مثلا: user_unsubscribed, operator_block, hard_bounce
            $table->string('source', 50)->nullable();    // مثلا: manual, webhook, import
            $table->timestamps();
            $table->index('mobile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_blacklists');
    }
};

