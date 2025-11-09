<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'operational_expense_type')) {
                $after = Schema::hasColumn('purchase_orders', 'project_name') ? 'project_name' : (Schema::hasColumn('purchase_orders', 'usage_type') ? 'usage_type' : null);
                if ($after) {
                    $table->string('operational_expense_type')->nullable()->after($after);
                } else {
                    $table->string('operational_expense_type')->nullable();
                }
            }
        });

        // Make supplier_id nullable and set foreign key to SET NULL on delete
        // Drop existing foreign key if present, alter column to nullable, then recreate FK
        try {
            Schema::table('purchase_orders', function (Blueprint $table) {
                try { $table->dropForeign('purchase_orders_supplier_id_foreign'); } catch (\Throwable $e) {}
                try { $table->dropForeign(['supplier_id']); } catch (\Throwable $e) {}
            });
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE purchase_orders MODIFY supplier_id BIGINT UNSIGNED NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE purchase_orders ALTER COLUMN supplier_id DROP NOT NULL');
            } elseif ($driver === 'sqlite') {
                // SQLite cannot easily alter column nullability; leave as-is if already nullable
                // Projects using SQLite for tests may already have supplier_id nullable in schema snapshots
            }
        } catch (\Throwable $e) {
            // As a fallback, attempt Laravel change() if doctrine/dbal is installed
            try {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $table->unsignedBigInteger('supplier_id')->nullable()->change();
                });
            } catch (\Throwable $ignored) {}
        }

        try {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down()
    {
        // Drop the operational_expense_type column if exists
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'operational_expense_type')) {
                $table->dropColumn('operational_expense_type');
            }
        });

        // Revert supplier_id to NOT NULL and cascade on delete
        try {
            Schema::table('purchase_orders', function (Blueprint $table) {
                try { $table->dropForeign('purchase_orders_supplier_id_foreign'); } catch (\Throwable $e) {}
                try { $table->dropForeign(['supplier_id']); } catch (\Throwable $e) {}
            });
        } catch (\Throwable $e) {}

        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE purchase_orders MODIFY supplier_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE purchase_orders ALTER COLUMN supplier_id SET NOT NULL');
            }
        } catch (\Throwable $e) {
            try {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
                });
            } catch (\Throwable $ignored) {}
        }

        try {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            });
        } catch (\Throwable $e) {}
    }
};

