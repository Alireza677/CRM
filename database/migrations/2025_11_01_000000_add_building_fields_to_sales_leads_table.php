<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_leads', 'building_usage')) {
                $table->string('building_usage')->nullable()->after('city');
            }
            if (!Schema::hasColumn('sales_leads', 'internal_temperature')) {
                $table->decimal('internal_temperature', 6, 2)->nullable()->after('building_usage');
            }
            if (!Schema::hasColumn('sales_leads', 'external_temperature')) {
                $table->decimal('external_temperature', 6, 2)->nullable()->after('internal_temperature');
            }
            if (!Schema::hasColumn('sales_leads', 'building_length')) {
                $table->decimal('building_length', 8, 2)->nullable()->after('external_temperature');
            }
            if (!Schema::hasColumn('sales_leads', 'building_width')) {
                $table->decimal('building_width', 8, 2)->nullable()->after('building_length');
            }
            if (!Schema::hasColumn('sales_leads', 'eave_height')) {
                $table->decimal('eave_height', 8, 2)->nullable()->after('building_width');
            }
            if (!Schema::hasColumn('sales_leads', 'ridge_height')) {
                $table->decimal('ridge_height', 8, 2)->nullable()->after('eave_height');
            }
            if (!Schema::hasColumn('sales_leads', 'wall_material')) {
                $table->string('wall_material')->nullable()->after('ridge_height');
            }
            if (!Schema::hasColumn('sales_leads', 'insulation_status')) {
                $table->string('insulation_status', 20)->nullable()->after('wall_material');
            }
            if (!Schema::hasColumn('sales_leads', 'spot_heating_systems')) {
                $table->unsignedInteger('spot_heating_systems')->nullable()->after('insulation_status');
            }
            if (!Schema::hasColumn('sales_leads', 'central_200_systems')) {
                $table->unsignedInteger('central_200_systems')->nullable()->after('spot_heating_systems');
            }
            if (!Schema::hasColumn('sales_leads', 'central_300_systems')) {
                $table->unsignedInteger('central_300_systems')->nullable()->after('central_200_systems');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $columns = [
                'building_usage',
                'internal_temperature',
                'external_temperature',
                'building_length',
                'building_width',
                'eave_height',
                'ridge_height',
                'wall_material',
                'insulation_status',
                'spot_heating_systems',
                'central_200_systems',
                'central_300_systems',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sales_leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
