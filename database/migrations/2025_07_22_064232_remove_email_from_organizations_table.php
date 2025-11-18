<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // اگر روی SQLite هستیم (محیط تست)، اصلاً این مایگریشن را اجرا نکن
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // فقط اگر جدول و ستون email وجود داشته باشد، حذفش کن (برای MySQL و بقیه)
        if (Schema::hasTable('organizations') && Schema::hasColumn('organizations', 'email')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }

    public function down()
    {
        // اگر روی SQLite هستیم، کاری نکن
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // فقط اگر جدول وجود دارد و ستون email وجود ندارد، دوباره اضافه‌اش کن
        if (Schema::hasTable('organizations') && ! Schema::hasColumn('organizations', 'email')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->string('email')->nullable();
            });
        }
    }
};
