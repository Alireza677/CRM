<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ایجاد فرم جدید
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('forms.store') }}" class="space-y-6">
                        @csrf

                        <!-- Type -->
                        <div>
                            <x-label for="type" value="نوع فرم" />
                            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">انتخاب کنید</option>
                                @foreach(Form::getTypes() as $key => $value)
                                    <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error for="type" class="mt-2" />
                        </div>

                        <!-- Title -->
                        <div>
                            <x-label for="title" value="عنوان" />
                            <x-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required autofocus />
                            <x-input-error for="title" class="mt-2" />
                        </div>

                        <!-- Supplier -->
                        <div>
                            <x-label for="supplier_id" value="تأمین‌کننده" />
                            <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">انتخاب کنید</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error for="supplier_id" class="mt-2" />
                        </div>

                        <!-- Purchase Date -->
                        <div>
                            <x-label for="purchase_date" value="تاریخ خرید" />
                            <x-input id="purchase_date" class="block mt-1 w-full" type="date" name="purchase_date" :value="old('purchase_date')" required />
                            <x-input-error for="purchase_date" class="mt-2" />
                        </div>

                        <!-- Status -->
                        <div>
                            <x-label for="status" value="وضعیت" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">انتخاب کنید</option>
                                @foreach(Form::getStatuses() as $key => $value)
                                    <option value="{{ $key }}" {{ old('status') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error for="status" class="mt-2" />
                        </div>

                        <!-- Total -->
                        <div>
                            <x-label for="total" value="مجموع" />
                            <x-input id="total" class="block mt-1 w-full" type="number" name="total" :value="old('total')" required />
                            <x-input-error for="total" class="mt-2" />
                        </div>

                        <!-- Assigned To -->
                        <div>
                            <x-label for="assigned_to" value="ارجاع به" />
                            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">انتخاب کنید</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error for="assigned_to" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div>
                            <x-label for="description" value="توضیحات" />
                            <textarea id="description" name="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('description') }}</textarea>
                            <x-input-error for="description" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('forms.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                انصراف
                            </a>
                            <x-button class="mr-3">
                                ایجاد فرم
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 