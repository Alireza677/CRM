<?php

namespace App\Crud\Schemas;

use App\Models\AfterSalesService;

class AfterSalesServiceSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'after_sales_services',
            'title' => 'فرم‌های خدمات پس از فروش',
            'model' => AfterSalesService::class,
            'routes' => [
                'index' => 'support.after-sales-services.index',
                'create' => 'support.after-sales-services.create',
                'show' => 'support.after-sales-services.show',
                'edit' => 'support.after-sales-services.edit',
                'destroy' => 'support.after-sales-services.destroy',
                'bulkDestroy' => 'support.after-sales-services.bulk-destroy',
            ],
            'per_page' => 12,
            'per_page_options' => [12, 25, 50, 100],
            'query' => function ($query, $request) {
                if (!$request->filled('sort')) {
                    $query->latest();
                }
            },
            'columns' => [
                [
                    'key' => 'customer_name',
                    'label' => 'نام مشتری',
                    'type' => 'text',
                    'sortable' => true,
                ],
                [
                    'key' => 'coordinator_name',
                    'label' => 'هماهنگ‌کننده',
                    'type' => 'text',
                    'sortable' => true,
                ],
                [
                    'key' => 'coordinator_mobile',
                    'label' => 'شماره تماس',
                    'type' => 'text',
                ],
                [
                    'key' => 'created_at',
                    'label' => 'تاریخ ثبت',
                    'type' => 'datetime',
                    'sortable' => true,
                ],
                [
                    'key' => 'actions',
                    'label' => 'عملیات',
                    'type' => 'actions',
                ],
            ],
            'filters' => [
                [
                    'key' => 'search',
                    'column' => 'customer_name',
                    'columns' => ['customer_name', 'coordinator_name', 'coordinator_mobile', 'address', 'issue_description'],
                    'type' => 'text',
                    'placeholder' => 'جستجو...',
                ],
                [
                    'key' => 'customer_name',
                    'type' => 'text',
                    'placeholder' => 'نام مشتری',
                ],
                [
                    'key' => 'coordinator_name',
                    'type' => 'text',
                    'placeholder' => 'هماهنگ‌کننده',
                ],
                [
                    'key' => 'coordinator_mobile',
                    'type' => 'text',
                    'placeholder' => 'شماره تماس',
                ],
                [
                    'key' => 'created_at',
                    'type' => 'date',
                    'placeholder' => 'تاریخ ثبت',
                ],
            ],
        ];
    }
}
