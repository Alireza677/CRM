<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_lead_id');
            $table->unsignedBigInteger('contact_id');
            $table->timestamps();

            $table->unique(['sales_lead_id', 'contact_id']);

            $table->foreign('sales_lead_id')
                ->references('id')
                ->on('sales_leads')
                ->onDelete('cascade');

            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('cascade');
        });

        DB::table('lead_contacts')->insertUsing(
            ['sales_lead_id', 'contact_id', 'created_at', 'updated_at'],
            DB::table('sales_leads')
                ->select('id', 'contact_id', DB::raw('NOW()'), DB::raw('NOW()'))
                ->whereNotNull('contact_id')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_contacts');
    }
};
