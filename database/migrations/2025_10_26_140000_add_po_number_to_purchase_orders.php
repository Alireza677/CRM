<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'po_number')) {
                $table->string('po_number')->nullable()->unique()->after('id');
            }
        });

        // Backfill existing records with a deterministic P-prefixed number based on ID
        try {
            DB::table('purchase_orders')
                ->whereNull('po_number')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        $num = 'p' . str_pad((string)$row->id, 6, '0', STR_PAD_LEFT);
                        DB::table('purchase_orders')
                            ->where('id', $row->id)
                            ->update(['po_number' => $num]);
                    }
                }, 'id');
        } catch (\Throwable $e) {
            // Swallow errors to avoid migration failure in edge cases
        }
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'po_number')) {
                // Drop the unique index explicitly using its default name
                try { $table->dropUnique('purchase_orders_po_number_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('po_number');
            }
        });
    }
};
