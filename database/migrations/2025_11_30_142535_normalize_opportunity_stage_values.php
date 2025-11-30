<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE opportunities SET stage = TRIM(stage)");
        DB::statement("UPDATE opportunities SET stage = REPLACE(stage, '‌', ' ')");
        DB::statement("UPDATE opportunities SET stage = REPLACE(stage, '  ', ' ')");
        DB::statement("UPDATE opportunities SET stage = 'ارسال پیش فاکتور' WHERE stage IN ('ارسال پیش فاکتور ', ' ارسال پیش فاکتور', 'ارسال پیش‌فاکتور')");
        DB::statement("UPDATE opportunities SET stage = 'برنده' WHERE HEX(stage) = 'D8A8D8B1D986D987'");
        DB::statement("UPDATE opportunities SET stage = 'won' WHERE stage = 'برنده'");
    }

    public function down(): void
    {
    }
};
