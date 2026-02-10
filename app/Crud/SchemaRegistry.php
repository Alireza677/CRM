<?php

namespace App\Crud;

use App\Crud\Schemas\OpportunitySchema;
use App\Crud\Schemas\LeadSchema;
use App\Crud\Schemas\ProformaSchema;
use App\Crud\Schemas\ContactSchema;
use App\Crud\Schemas\OrganizationSchema;
use App\Crud\Schemas\ProjectSchema;
use App\Crud\Schemas\ProjectArchiveSchema;
use App\Crud\Schemas\ActivitySchema;
use App\Crud\Schemas\SupplierSchema;
use App\Crud\Schemas\ProductSchema;
use App\Crud\Schemas\AfterSalesServiceSchema;

class SchemaRegistry
{
    protected static array $map = [
        'opportunities' => OpportunitySchema::class,
        'leads' => LeadSchema::class,
        'proformas' => ProformaSchema::class,
        'contacts' => ContactSchema::class,
        'organizations' => OrganizationSchema::class,
        'projects' => ProjectSchema::class,
        'projects_archive' => ProjectArchiveSchema::class,
        'activities' => ActivitySchema::class,
        'suppliers' => SupplierSchema::class,
        'products' => ProductSchema::class,
        'after_sales_services' => AfterSalesServiceSchema::class,
    ];

    public static function get(string $key): array
    {
        if (!array_key_exists($key, self::$map)) {
            abort(404);
        }

        $class = self::$map[$key];
        if (!class_exists($class) || !method_exists($class, 'schema')) {
            abort(500, 'Invalid CRUD schema.');
        }

        return $class::schema();
    }

    public static function keys(): array
    {
        return array_keys(self::$map);
    }
}
