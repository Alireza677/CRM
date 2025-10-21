<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('purchase_type')->default('official')->after('subject');
            $table->foreignId('requested_by')->nullable()->after('supplier_id')->constrained('users')->nullOnDelete();
            $table->date('request_date')->nullable()->after('requested_by');
            $table->date('needed_by_date')->nullable()->after('purchase_date');
            $table->decimal('previously_paid_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('remaining_payable_amount', 15, 2)->default(0)->after('previously_paid_amount');
        });
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_type',
                'requested_by',
                'request_date',
                'needed_by_date',
                'previously_paid_amount',
                'remaining_payable_amount',
            ]);
        });
    }
};

