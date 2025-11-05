<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_on_leave')) {
                $table->boolean('is_on_leave')->default(false)->after('is_admin');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_on_leave')) {
                $table->dropColumn('is_on_leave');
            }
        });
    }
};
