@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [ ['title' => 'گزارش نقش‌ها و دسترسی‌ها'] ];
        $scopeBadges = [
            'own' => 'خود',
            'team' => 'تیم',
            'department' => 'دپارتمان',
            'company' => 'شرکت',
        ];

        $actionLabels = [
            'view' => 'مشاهده',
            'create' => 'ایجاد',
            'update' => 'ویرایش',
            'delete' => 'حذف',
            'reassign' => 'اختصاص مجدد',
            'export' => 'خروجی',
        ];

        $roleLabels = [
            'admin' => 'مدیر سیستم',
            'finance' => 'مالی',
            'admin_manager' => 'مدیر ادمین',
            'admin_staff' => 'کارمند ادمین',
            'factory_supervisor' => 'سرپرست کارخانه',
            'support' => 'پشتیبانی',
            'sales_manager' => 'مدیر فروش',
            'salesperson' => 'کارشناس فروش',
            'sales_agent' => 'نماینده فروش',
            'user' => 'کاربر',
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">گزارش نقش‌ها و دسترسی‌ها</h2>
                <div class="space-x-2 space-x-reverse">
                    <a href="{{ route('admin.role-permissions.markdown') }}" class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm">
                        خروجی Markdown
                    </a>
                    <a href="{{ route('admin.role-permissions.csv') }}" class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
                        خروجی CSV
                    </a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @foreach($modules as $module)
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-3">ماژول: <span class="font-mono text-gray-700">{{ $module }}</span></h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full border divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-right text-sm font-semibold text-gray-700">نقش</th>
                                            @foreach($actions as $action)
                                                <th class="px-3 py-2 text-center text-sm font-semibold text-gray-700">{{ $actionLabels[$action] ?? $action }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($roles as $role)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 text-sm text-gray-800 whitespace-nowrap">{{ $roleLabels[$role->name] ?? $role->name }}</td>
                                                @foreach($actions as $action)
                                                    @php $cell = $matrix[$role->name][$module][$action] ?? null; @endphp
                                                    <td class="px-3 py-2 text-center text-sm whitespace-nowrap">
                                                        @if(is_array($cell) && count($cell))
                                                            <div class="flex items-center justify-center gap-1">
                                                                @foreach($cell as $s)
                                                                    <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 text-xs">{{ $scopeBadges[$s] ?? $s }}</span>
                                                                @endforeach
                                                            </div>
                                                        @elseif($cell === true)
                                                            <span class="text-green-600">✓</span>
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

