<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'ready_for_delivery_notified_at')) {
                $table->timestamp('ready_for_delivery_notified_at')->nullable()->after('assigned_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'ready_for_delivery_notified_at')) {
                $table->dropColumn('ready_for_delivery_notified_at');
            }
        });
    }
};

