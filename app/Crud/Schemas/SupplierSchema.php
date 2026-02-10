<?php

namespace App\Crud\Schemas;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;

class SupplierSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'suppliers',
            'title' => 'تأمین‌کنندگان',
            'model' => Supplier::class,
            'routes' => [
                'index' => 'inventory.suppliers.index',
                'create' => 'inventory.suppliers.create',
                'show' => 'inventory.suppliers.show',
                'edit' => 'inventory.suppliers.edit',
                'destroy' => 'inventory.suppliers.destroy',
                'import' => 'inventory.suppliers.import',
            ],
            'per_page' => 10,
            'per_page_options' => [10, 25, 50, 100],
            'query' => function ($query, $request) {
                $query->with(['category', 'assignedUser']);

                if (!$request->filled('sort')) {
                    $query->orderByDesc('created_at');
                }
            },
            'columns' => [
                [
                    'key' => 'name',
                    'label' => 'نام',
                    'type' => 'link',
                    'sortable' => true,
                ],
                [
                    'key' => 'phone',
                    'label' => 'تلفن',
                    'type' => 'text',
                ],
                [
                    'key' => 'email',
                    'label' => 'ایمیل',
                    'type' => 'text',
                ],
                [
                    'key' => 'category.name',
                    'label' => 'دسته‌بندی',
                    'type' => 'relation',
                ],
                [
                    'key' => 'assignedUser.name',
                    'label' => 'ارجاع به',
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
                    'columns' => ['name', 'phone', 'email', 'category.name', 'assignedUser.name'],
                    'type' => 'text',
                    'placeholder' => 'جستجو...',
                ],
                [
                    'key' => 'phone',
                    'type' => 'text',
                    'placeholder' => 'تلفن',
                ],
                [
                    'key' => 'email',
                    'type' => 'text',
                    'placeholder' => 'ایمیل',
                ],
                [
                    'key' => 'category_id',
                    'column' => 'category.name',
                    'type' => 'select',
                    'options' => function () {
                        return Category::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'دسته‌بندی',
                ],
                [
                    'key' => 'assigned_to',
                    'column' => 'assignedUser.name',
                    'type' => 'select',
                    'options' => function () {
                        return User::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'ارجاع به',
                ],
            ],
        ];
    }
}
