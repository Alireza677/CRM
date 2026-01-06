<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->date('date')->index();
                $table->string('jalali_date')->nullable();
                $table->string('title')->nullable();
                $table->boolean('is_holiday')->default(false);
                $table->string('source', 64)->default('shamsi-holidays');
                $table->string('external_id', 128)->nullable();
                $table->boolean('notify')->default(false);
                $table->timestamp('notify_sent_at')->nullable();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['date', 'title', 'source'], 'holidays_date_title_source_unique');
            });

            return;
        }

        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'date')) {
                $table->dropUnique('holidays_date_unique');
            }

            if (!Schema::hasColumn('holidays', 'jalali_date')) {
                $table->string('jalali_date')->nullable()->after('date');
            }

            if (!Schema::hasColumn('holidays', 'is_holiday')) {
                $table->boolean('is_holiday')->default(false)->after('title');
            }

            if (!Schema::hasColumn('holidays', 'source')) {
                $table->string('source', 64)->default('shamsi-holidays')->after('is_holiday');
            }

            if (!Schema::hasColumn('holidays', 'external_id')) {
                $table->string('external_id', 128)->nullable()->after('source');
            }
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->index('date');
            $table->unique(['date', 'title', 'source'], 'holidays_date_title_source_unique');
        });

        DB::table('holidays')
            ->whereNull('source')
            ->update(['source' => 'manual']);

        DB::table('holidays')
            ->whereNull('is_holiday')
            ->update(['is_holiday' => false]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('holidays')) {
            return;
        }

        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'jalali_date')) {
                $table->dropColumn('jalali_date');
            }
            if (Schema::hasColumn('holidays', 'is_holiday')) {
                $table->dropColumn('is_holiday');
            }
            if (Schema::hasColumn('holidays', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('holidays', 'external_id')) {
                $table->dropColumn('external_id');
            }

            $table->dropIndex(['date']);
            $table->dropUnique('holidays_date_title_source_unique');
            $table->unique('date');
        });
    }
};
