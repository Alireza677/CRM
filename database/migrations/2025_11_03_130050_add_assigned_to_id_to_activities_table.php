<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('activities', function (Blueprint $table) {
        if (!Schema::hasColumn('activities', 'assigned_to_id')) {
            $table->unsignedBigInteger('assigned_to_id')->nullable()->after('status');
            $table->index('assigned_to_id', 'idx_activities_assigned_to_id');
            $table->foreign('assigned_to_id')
                  ->references('id')->on('users')->nullOnDelete();
        }
        if (!Schema::hasColumn('activities', 'due_at')) {
            $table->dateTime('due_at')->nullable();
        }
        if (!Schema::hasColumn('activities', 'deleted_at')) {
            $table->softDeletes();
        }
    });

    // اگر ستون قدیمی assigned_to وجود دارد، داده را منتقل کن
    if (Schema::hasColumn('activities', 'assigned_to') && Schema::hasColumn('activities', 'assigned_to_id')) {
        DB::statement('UPDATE activities SET assigned_to_id = assigned_to WHERE assigned_to_id IS NULL');
    }
}

public function down(): void
{
    Schema::table('activities', function (Blueprint $table) {
        if (Schema::hasColumn('activities', 'assigned_to_id')) {
            $table->dropForeign(['assigned_to_id']);
            $table->dropIndex('idx_activities_assigned_to_id');
            $table->dropColumn('assigned_to_id');
        }
        // حذف due_at و deleted_at فقط اگر خودت اضافه‌شان کرده‌ای
        if (Schema::hasColumn('activities', 'due_at')) {
            $table->dropColumn('due_at');
        }
        if (Schema::hasColumn('activities', 'deleted_at')) {
            $table->dropSoftDeletes();
        }
    });
}

};
