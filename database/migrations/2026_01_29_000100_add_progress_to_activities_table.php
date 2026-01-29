<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('activities')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'progress')) {
                $table->unsignedTinyInteger('progress')->default(0)->after('priority');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('activities')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'progress')) {
                $table->dropColumn('progress');
            }
        });
    }
};