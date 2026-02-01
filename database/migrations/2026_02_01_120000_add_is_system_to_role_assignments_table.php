<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('role_assignments', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('role_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('role_assignments', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};
