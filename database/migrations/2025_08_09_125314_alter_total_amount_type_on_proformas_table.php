<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proformas', function (Blueprint $table) {
            // تبدیل نوع ستون به DECIMAL(20,2)
            $table->decimal('total_amount', 20, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('proformas', function (Blueprint $table) {
            // اگر خواستی برگردانی، نوع قبلی را جایگزین کن (مثلاً integer)
            // $table->integer('total_amount')->nullable()->change();
        });
    }
};
