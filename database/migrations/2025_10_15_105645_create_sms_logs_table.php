<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('to', 20)->index();
            $table->string('type', 20)->default('text');

            $table->text('message');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('status_text', 50)->nullable();

            $table->string('provider_message_id', 64)->nullable()->index();
            // برای سازگاری کامل، longText می‌گذاریم (بدون نیاز به DBAL/نسخه خاص MySQL)
            $table->longText('provider_response')->nullable();

            $table->timestamps();
            $table->index(['to','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};

