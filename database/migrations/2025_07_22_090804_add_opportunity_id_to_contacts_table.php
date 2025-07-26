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
            $table->foreignId('opportunity_id')->nullable()->constrained()->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['opportunity_id']);
            $table->dropColumn('opportunity_id');
        });
    }
    
};
