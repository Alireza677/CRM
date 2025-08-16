<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proformas', function (Blueprint $table) {
            if (! Schema::hasColumn('proformas', 'approval_stage')) {
                $table->string('approval_stage')->nullable()->after('proforma_stage');
            }
        });
    }

    public function down()
    {
        Schema::table('proformas', function (Blueprint $table) {
            if (Schema::hasColumn('proformas', 'approval_stage')) {
                $table->dropColumn('approval_stage');
            }
        });
    }
};
