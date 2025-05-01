<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->renameColumn('contact_id', 'contact');
            $table->renameColumn('organization_id', 'organization');
            $table->renameColumn('user_id', 'assigned_to');
            $table->date('next_follow_up')->nullable();
        });
    }

    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->renameColumn('contact', 'contact_id');
            $table->renameColumn('organization', 'organization_id');
            $table->renameColumn('assigned_to', 'user_id');
            $table->dropColumn('next_follow_up');
        });
    }
}; 