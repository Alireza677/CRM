<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->timestamp('converted_at')->nullable()->after('notes');
            $table->foreignId('converted_opportunity_id')->nullable()->after('converted_at')
                ->constrained('opportunities')->nullOnDelete();
            $table->foreignId('converted_by')->nullable()->after('converted_opportunity_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (Schema::hasColumn('sales_leads', 'converted_by')) {
                $table->dropForeign(['converted_by']);
                $table->dropColumn('converted_by');
            }
            if (Schema::hasColumn('sales_leads', 'converted_opportunity_id')) {
                $table->dropForeign(['converted_opportunity_id']);
                $table->dropColumn('converted_opportunity_id');
            }
            if (Schema::hasColumn('sales_leads', 'converted_at')) {
                $table->dropColumn('converted_at');
            }
        });
    }
};

