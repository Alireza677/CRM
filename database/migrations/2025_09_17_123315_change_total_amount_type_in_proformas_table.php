<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proformas', function (Blueprint $table) {
            // تغییر نوع ستون
            $table->unsignedBigInteger('total_amount')->change();
        });
    }

    public function down(): void
    {
        Schema::table('proformas', function (Blueprint $table) {
            // برگردوندن به decimal(15,2) در صورت رول‌بک
            $table->decimal('total_amount', 15, 2)->change();
        });
    }
};

