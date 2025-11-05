@extends('layouts.app')

@section('content')
    <div class="p-6 bg-white shadow rounded">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">گردش کارها</h1>
        <p class="text-gray-600 mb-6">تنظیم گردش کار و تاییدکنندگان برای سفارش خرید.</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="border rounded p-4">
                <h2 class="text-lg font-semibold mb-3">مراحل سفارش خرید</h2>
                <ol class="list-decimal pr-5 space-y-2 text-gray-800">
                    @foreach(($poStages ?? []) as $key => $label)
                        <li>
                            <span class="font-medium">{{ $label }}</span>
                            @if($key === 'supervisor_approval')
                                <span class="text-gray-500 text-sm">— مسئول: سرپرست کارخانه</span>
                            @elseif($key === 'manager_approval')
                                <span class="text-gray-500 text-sm">— مسئول: مدیر کل</span>
                            @elseif($key === 'accounting_approval')
                                <span class="text-gray-500 text-sm">— مسئول: حسابداری</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="border rounded p-4">
                <h2 class="text-lg font-semibold mb-3">تعیین تاییدکنندگان</h2>
                <form method="POST" action="{{ route('settings.workflows.purchase-orders.update') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">سرپرست کارخانه (First Approver)</label>
                        <select name="first_approver_id" class="w-full border rounded p-2">
                            <option value="">— انتخاب کاربر —</option>
                            @foreach(($users ?? []) as $u)
                                <option value="{{ $u->id }}" @selected(optional($poSettings)->first_approver_id == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('first_approver_id')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror

                        <div class="mt-3">
                            <label class="block text-sm text-gray-700 mb-1">نفر جایگزین</label>
                            <select name="first_approver_substitute" class="w-full border rounded p-2" placeholder="انتخاب نفر جایگزین">
                                <option value="">— انتخاب نفر جایگزین —</option>
                                @foreach(($users ?? []) as $u)
                                    <option value="{{ $u->id }}" @selected(optional($poSettings)->first_approver_substitute_id == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                            @error('first_approver_substitute')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">مدیر کل (Second Approver)</label>
                        <select name="second_approver_id" class="w-full border rounded p-2">
                            <option value="">— انتخاب کاربر —</option>
                            @foreach(($users ?? []) as $u)
                                <option value="{{ $u->id }}" @selected(optional($poSettings)->second_approver_id == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('second_approver_id')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror

                        <div class="mt-3">
                            <label class="block text-sm text-gray-700 mb-1">نفر جایگزین</label>
                            <select name="second_approver_substitute" class="w-full border rounded p-2" placeholder="انتخاب نفر جایگزین">
                                <option value="">— انتخاب نفر جایگزین —</option>
                                @foreach(($users ?? []) as $u)
                                    <option value="{{ $u->id }}" @selected(optional($poSettings)->second_approver_substitute_id == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                            @error('second_approver_substitute')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">حسابداری (Accounting)</label>
                        <select name="accounting_user_id" class="w-full border rounded p-2">
                            <option value="">— انتخاب کاربر —</option>
                            @foreach(($users ?? []) as $u)
                                <option value="{{ $u->id }}" @selected(optional($poSettings)->accounting_user_id == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('accounting_user_id')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror

                        <div class="mt-3">
                            <label class="block text-sm text-gray-700 mb-1">نفر جایگزین</label>
                            <select name="accounting_approver_substitute" class="w-full border rounded p-2" placeholder="انتخاب نفر جایگزین">
                                <option value="">— انتخاب نفر جایگزین —</option>
                                @foreach(($users ?? []) as $u)
                                    <option value="{{ $u->id }}" @selected(optional($poSettings)->accounting_approver_substitute_id == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                            @error('accounting_approver_substitute')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ذخیره تنظیمات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
