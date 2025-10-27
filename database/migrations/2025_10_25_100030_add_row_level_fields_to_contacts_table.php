<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'owner_user_id')) {
                $table->unsignedBigInteger('owner_user_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('contacts', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
            }
            if (!Schema::hasColumn('contacts', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->index();
            }
            if (!Schema::hasColumn('contacts', 'department')) {
                $table->string('department', 32)->nullable()->index();
            }
            if (!Schema::hasColumn('contacts', 'visibility')) {
                $table->enum('visibility', ['private','team','department','company'])->default('department');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'visibility')) {
                $table->dropColumn('visibility');
            }
            if (Schema::hasColumn('contacts', 'department')) {
                $table->dropIndex(['department']);
                $table->dropColumn('department');
            }
            if (Schema::hasColumn('contacts', 'team_id')) {
                $table->dropIndex(['team_id']);
                $table->dropColumn('team_id');
            }
            if (Schema::hasColumn('contacts', 'assigned_to')) {
                // do not drop assigned_to if it existed before this migration
            }
            if (Schema::hasColumn('contacts', 'owner_user_id')) {
                $table->dropIndex(['owner_user_id']);
                $table->dropColumn('owner_user_id');
            }
        });
    }
};

