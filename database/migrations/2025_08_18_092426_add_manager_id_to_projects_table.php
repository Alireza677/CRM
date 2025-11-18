<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up()
    {
        // اگر درایور دیتابیس sqlite است (محیط تست)،
        // این مایگریشن را ساده و بدون دستورات MySQL اجرا کن
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // فقط مطمئن شو ستون manager_id وجود دارد و nullable است
            if (! Schema::hasColumn('projects', 'manager_id')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->unsignedBigInteger('manager_id')->nullable()->after('id');
                });
            }
            // روی SQLite فعلاً نیازی به FK واقعی و دستکاری اطلاعات نیست
            return;
        }

        // ---------------------------
        // از اینجا به بعد: منطق اصلی برای MySQL
        // ---------------------------

        // 0) Drop any existing FK on projects.manager_id (name-agnostic)
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'projects')
            ->where('COLUMN_NAME', 'manager_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement("ALTER TABLE `projects` DROP FOREIGN KEY `{$constraint}`");
        }

        // 1) Ensure the column exists and is BIGINT UNSIGNED NULLABLE (to match users.id = bigIncrements)
        if (! Schema::hasColumn('projects', 'manager_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('manager_id')->nullable()->after('id');
            });
        } else {
            // Avoid requiring doctrine/dbal by using raw SQL to modify type
            DB::statement("ALTER TABLE `projects` MODIFY `manager_id` BIGINT UNSIGNED NULL");
        }

        // 2) Clean existing bad data BEFORE adding FK:
        //    - remove zeros/blank-like values
        DB::statement("UPDATE `projects` SET `manager_id` = NULL WHERE `manager_id` = 0");

        //    - null any manager_id that doesn't exist in users
        DB::statement("
            UPDATE `projects` p
            LEFT JOIN `users` u ON u.id = p.manager_id
            SET p.manager_id = NULL
            WHERE p.manager_id IS NOT NULL AND u.id IS NULL
        ");

        // 3) Add the FK only if not already present
        $hasFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'projects')
            ->where('COLUMN_NAME', 'manager_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if (! $hasFk) {
            DB::statement("
                ALTER TABLE `projects`
                ADD CONSTRAINT `projects_manager_id_foreign`
                FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`)
                ON DELETE SET NULL
            ");
        }
    }

    public function down()
    {
        // در محیط تست (sqlite) نیازی به دستکاری نیست
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Drop FK if exists
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'projects')
            ->where('COLUMN_NAME', 'manager_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement("ALTER TABLE `projects` DROP FOREIGN KEY `{$constraint}`");
        }

        // (optional) drop column to fully revert
        if (Schema::hasColumn('projects', 'manager_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('manager_id');
            });
        }
    }
};
