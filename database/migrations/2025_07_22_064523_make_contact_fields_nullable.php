<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeContactFieldsNullable extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
}

