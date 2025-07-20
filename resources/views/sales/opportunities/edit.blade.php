@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ویرایش: ' . $opportunity->subject]
    ];
@endphp

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">ویرایش فرصت فروش</h2>

                <form method="POST" action="{{ route('sales.opportunities.update', $opportunity) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- عنوان --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">عنوان</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $opportunity->name) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm" />
                            @error('name') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- سازمان --}}
                        <div>
                            <label for="organization_id" class="block text-sm font-medium text-gray-700">سازمان</label>
                            <select name="organization_id" id="organization_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                @foreach($organizations as $organization)
                                    <option value="{{ $organization->id }}" {{ old('organization_id', $opportunity->organization_id) == $organization->id ? 'selected' : '' }}>
                                        {{ $organization->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('organization_id') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- مخاطب --}}
                        <div>
                            <label for="contact_id" class="block text-sm font-medium text-gray-700">مخاطب</label>
                            <select name="contact_id" id="contact_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('contact_id', $opportunity->contact_id) == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contact_id') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- نوع --}}
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">نوع</label>
                            <select name="type" id="type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                <option value="کسب و کار موجود" {{ old('type', $opportunity->type) == 'کسب و کار موجود' ? 'selected' : '' }}>کسب و کار موجود</option>
                                <option value="کسب و کار جدید" {{ old('type', $opportunity->type) == 'کسب و کار جدید' ? 'selected' : '' }}>کسب و کار جدید</option>
                            </select>
                            @error('type') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- مرحله فروش --}}
                        <div>
                            <label for="stage" class="block text-sm font-medium text-gray-700">مرحله فروش</label>
                            <select name="stage" id="stage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید...</option>
                                @foreach(['در حال پیگیری', 'پیگیری در آینده', 'برنده', 'بازنده', 'سرکاری', 'ارسال پیش فاکتور'] as $stage)
                                    <option value="{{ $stage }}" {{ old('stage', $opportunity->stage) == $stage ? 'selected' : '' }}>{{ $stage }}</option>
                                @endforeach
                            </select>
                            @error('stage') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- منبع --}}
                        <div>
                            <label for="source" class="block text-sm font-medium text-gray-700">منبع</label>
                            <select name="source" id="source" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                @foreach(['وب سایت', 'مشتریان قدیمی', 'نمایشگاه', 'بازاریابی حضوری'] as $source)
                                    <option value="{{ $source }}" {{ old('source', $opportunity->source) == $source ? 'selected' : '' }}>{{ $source }}</option>
                                @endforeach
                            </select>
                            @error('source') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- ارجاع به --}}
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700">ارجاع به</label>
                            <select name="assigned_to" id="assigned_to" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to', $opportunity->assigned_to) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('assigned_to') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- درصد موفقیت --}}
                        <div>
                            <label for="success_rate" class="block text-sm font-medium text-gray-700">درصد موفقیت</label>
                            <input type="number" name="success_rate" id="success_rate" min="0" max="100" value="{{ old('success_rate', $opportunity->success_rate) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm" />
                            @error('success_rate') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- مبلغ --}}
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">مبلغ</label>
                            <input type="number" name="amount" id="amount" min="0" value="{{ old('amount', $opportunity->amount) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm" />
                            @error('amount') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- تاریخ پیگیری بعدی --}}
                        <div>
                            <label for="next_follow_up_shamsi" class="block text-sm font-medium text-gray-700">تاریخ پیگیری بعدی</label>
                            <input type="text" id="next_follow_up_shamsi" value="{{ $nextFollowUpDate }}" class="form-input bg-white mt-1 block w-full border border-gray-300 rounded-md shadow-sm" autocomplete="off">
                            <input type="hidden" id="next_follow_up" name="next_follow_up" value="{{ old('next_follow_up', $opportunity->next_follow_up) }}">
                            @error('next_follow_up') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- توضیحات --}}
                        <div class="col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700">توضیحات</label>
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">{{ old('description', $opportunity->description) }}</textarea>
                            @error('description') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mt-6">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">ذخیره</button>
                        <a href="{{ route('sales.opportunities.show', $opportunity) }}" class="text-gray-600 hover:text-gray-900">انصراف</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
