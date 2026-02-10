<?php

namespace App\Crud\Schemas;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;

class ProductSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'products',
            'title' => 'محصولات',
            'model' => Product::class,
            'routes' => [
                'index' => 'inventory.products.index',
                'create' => 'inventory.products.create',
                'show' => 'inventory.products.show',
                'edit' => 'inventory.products.edit',
                'destroy' => 'inventory.products.destroy',
                'import' => 'inventory.products.import',
            ],
            'per_page' => 10,
            'per_page_options' => [10, 25, 50, 100, 200],
            'query' => function ($query, $request) {
                $query->with(['category', 'supplier']);

                if (!$request->filled('sort')) {
                    $query->orderByDesc('created_at');
                }
            },
            'columns' => [
                [
                    'key' => 'name',
                    'label' => 'نام محصول',
                    'type' => 'link',
                    'sortable' => true,
                ],
                [
                    'key' => 'code',
                    'label' => 'کد محصول',
                    'type' => 'text',
                    'sortable' => true,
                ],
                [
                    'key' => 'stock_quantity',
                    'label' => 'موجودی',
                    'type' => 'text',
                    'sortable' => true,
                ],
                [
                    'key' => 'unit_price',
                    'label' => 'قیمت واحد',
                    'type' => 'text',
                    'sortable' => true,
                    'format' => function ($row, $raw) {
                        if ($raw === null || $raw === '') {
                            return '—';
                        }
                        return number_format((float) $raw);
                    },
                ],
                [
                    'key' => 'is_active',
                    'label' => 'وضعیت',
                    'type' => 'badge',
                    'sortable' => true,
                    'badges' => [
                        1 => 'bg-green-100 text-green-700',
                        0 => 'bg-red-100 text-red-700',
                    ],
                    'format' => function ($row, $raw) {
                        return $raw ? 'فعال' : 'غیرفعال';
                    },
                ],
                [
                    'key' => 'category.name',
                    'label' => 'دسته‌بندی',
                    'type' => 'relation',
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
                    'column' => 'name',
                    'columns' => ['name', 'code'],
                    'type' => 'text',
                    'placeholder' => 'نام/کد',
                ],
                [
                    'key' => 'name',
                    'type' => 'text',
                    'placeholder' => 'نام محصول',
                ],
                [
                    'key' => 'code',
                    'type' => 'text',
                    'placeholder' => 'کد محصول',
                ],
                [
                    'key' => 'stock_quantity',
                    'type' => 'text',
                    'placeholder' => 'موجودی',
                ],
                [
                    'key' => 'unit_price',
                    'type' => 'text',
                    'placeholder' => 'قیمت واحد',
                ],
                [
                    'key' => 'is_active',
                    'column' => 'is_active',
                    'type' => 'select',
                    'options' => [
                        1 => 'فعال',
                        0 => 'غیرفعال',
                    ],
                    'placeholder' => 'وضعیت',
                ],
                [
                    'key' => 'category_id',
                    'column' => 'category_id',
                    'type' => 'select',
                    'options' => function () {
                        return Category::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'دسته‌بندی',
                ],
                [
                    'key' => 'supplier_id',
                    'column' => 'supplier_id',
                    'type' => 'select',
                    'options' => function () {
                        return Supplier::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'تأمین‌کننده',
                ],
            ],
        ];
    }
}
