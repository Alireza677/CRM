<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        // فقط اگر جدول activities وجود ندارد، بسازش
        if (! Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->id();
                $table->string('subject');
                $table->dateTime('start_at');
                $table->dateTime('due_at')->nullable();

                $table->foreignId('assigned_to_id')->constrained('users')->cascadeOnDelete();

                // مربوط به: مخاطب/سازمان با رابطه پلی‌مورفیک
                $table->nullableMorphs('related'); // related_type, related_id

                // وضعیت و اولویت
                $table->enum('status', ['not_started','in_progress','completed','scheduled'])->default('not_started');
                $table->enum('priority', ['normal','medium','high'])->default('normal');

                $table->text('description')->nullable();
                $table->boolean('is_private')->default(false); // خصوصی/عمومی

                $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index(['start_at','due_at']);
                $table->index(['assigned_to_id','is_private']);
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('activities')) {
            Schema::dropIfExists('activities');
        }
    }
};
