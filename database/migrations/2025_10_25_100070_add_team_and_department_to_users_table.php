<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 32)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department')) {
                $table->dropIndex(['department']);
                $table->dropColumn('department');
            }
            if (Schema::hasColumn('users', 'team_id')) {
                $table->dropIndex(['team_id']);
                $table->dropColumn('team_id');
            }
        });
    }
};

