<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique('contacts_email_unique');
        });
    }
    
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->unique('email');
        });
    }
    
};
