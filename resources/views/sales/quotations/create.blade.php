@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'ایجاد پیش فاکتور جدید']
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                {{ __('ایجاد پیش فاکتور جدید') }}
            </h2>

            <form method="POST" action="{{ route('sales.quotations.store') }}" class="space-y-6">
                @csrf

                <!-- اطلاعات پیش فاکتور -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium mb-4">اطلاعات پیش فاکتور</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700">موضوع</label>
                            <input type="text" name="subject" id="subject" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="quotation_date" class="block text-sm font-medium text-gray-700">تاریخ پیش فاکتور</label>
                            <input type="date" name="quotation_date" id="quotation_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="contact_id" class="block text-sm font-medium text-gray-700">نام مخاطب</label>
                            <select name="contact_id" id="contact_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="organization_id" class="block text-sm font-medium text-gray-700">نام سازمان</label>
                            <select name="organization_id" id="organization_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                @foreach($organizations as $organization)
                                    <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="opportunity_id" class="block text-sm font-medium text-gray-700">نام فرصت فروش</label>
                            <select name="opportunity_id" id="opportunity_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                @foreach($opportunities as $opportunity)
                                    <option value="{{ $opportunity->id }}">{{ $opportunity->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700">ارجاع به</label>
                            <select name="assigned_to" id="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="product_manager" class="block text-sm font-medium text-gray-700">مدیر محصول</label>
                            <select name="product_manager" id="product_manager" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="quotation_number" class="block text-sm font-medium text-gray-700">شماره پیش فاکتور</label>
                            <input type="text" name="quotation_number" id="quotation_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- اطلاعات آدرس -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium mb-4">اطلاعات آدرس</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">کپی آدرس صورت‌حساب از:</label>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="radio" name="billing_address_source" value="organization" id="billing_org" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                <label for="billing_org" class="mr-2 block text-sm text-gray-900">سازمان</label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio" name="billing_address_source" value="contact" id="billing_contact" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                <label for="billing_contact" class="mr-2 block text-sm text-gray-900">مربوط به</label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio" name="billing_address_source" value="custom" id="billing_custom" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                <label for="billing_custom" class="mr-2 block text-sm text-gray-900">آدرس تحویل صورت حساب</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">کپی آدرس تحویل محصول از:</label>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="radio" name="shipping_address_source" value="organization" id="shipping_org" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                <label for="shipping_org" class="mr-2 block text-sm text-gray-900">سازمان</label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio" name="shipping_address_source" value="contact" id="shipping_contact" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                <label for="shipping_contact" class="mr-2 block text-sm text-gray-900">مربوط به</label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio" name="shipping_address_source" value="custom" id="shipping_custom" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                <label for="shipping_custom" class="mr-2 block text-sm text-gray-900">آدرس تحویل محصول</label>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="province" class="block text-sm font-medium text-gray-700">استان</label>
                            <input type="text" name="province" id="province" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">شهر</label>
                            <input type="text" name="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="md:col-span-2">
                            <label for="customer_address" class="block text-sm font-medium text-gray-700">آدرس مشتری</label>
                            <textarea name="customer_address" id="customer_address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label for="postal_code" class="block text-sm font-medium text-gray-700">کد پستی</label>
                            <textarea name="postal_code" id="postal_code" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                </div>

                <!-- اطلاعات آیتم -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium mb-4">اطلاعات آیتم</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="item_name" class="block text-sm font-medium text-gray-700">نام آیتم</label>
                            <select name="item_name" id="item_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700">مقدار</label>
                            <input type="number" name="quantity" id="quantity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="price_list" class="block text-sm font-medium text-gray-700">لیست قیمت</label>
                            <select name="price_list" id="price_list" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                @foreach($price_lists as $price_list)
                                    <option value="{{ $price_list->id }}">{{ $price_list->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="discount" class="block text-sm font-medium text-gray-700">تخفیف</label>
                            <input type="text" name="discount" id="discount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="unit" class="block text-sm font-medium text-gray-700">واحد استفاده</label>
                            <select name="unit" id="unit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="no_tax_region" id="no_tax_region" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="no_tax_region" class="mr-2 block text-sm text-gray-900">منطقه مالیاتی وجود ندارد</label>
                        </div>

                        <div>
                            <label for="tax_type" class="block text-sm font-medium text-gray-700">نوع مالیات</label>
                            <select name="tax_type" id="tax_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">انتخاب کنید</option>
                                <option value="group">گروه</option>
                            </select>
                        </div>

                        <div>
                            <label for="item_total" class="block text-sm font-medium text-gray-700">جمع آیتم‌ها</label>
                            <input type="text" name="item_total" id="item_total" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>

                        <div>
                            <label for="total_discount" class="block text-sm font-medium text-gray-700">تخفیف کل</label>
                            <input type="text" name="total_discount" id="total_discount" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>

                        <div>
                            <label for="surcharge" class="block text-sm font-medium text-gray-700">عوارض</label>
                            <input type="text" name="surcharge" id="surcharge" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>

                        <div>
                            <label for="subtotal" class="block text-sm font-medium text-gray-700">مجموع قبل از مالیات</label>
                            <input type="text" name="subtotal" id="subtotal" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>

                        <div>
                            <label for="tax" class="block text-sm font-medium text-gray-700">مالیات</label>
                            <input type="text" name="tax" id="tax" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>

                        <div>
                            <label for="tax_on_surcharge" class="block text-sm font-medium text-gray-700">مالیات بر عوارض</label>
                            <input type="text" name="tax_on_surcharge" id="tax_on_surcharge" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>

                        <div>
                            <label for="tax_deduction" class="block text-sm font-medium text-gray-700">کسر مالیات</label>
                            <input type="text" name="tax_deduction" id="tax_deduction" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">تنظیم / تعدیل</label>
                            <div class="flex items-center space-x-4 rtl:space-x-reverse">
                                <div class="flex items-center">
                                    <input type="radio" name="adjustment_type" value="adjustment" id="adjustment" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <label for="adjustment" class="mr-2 block text-sm text-gray-900">تعدیل</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" name="adjustment_type" value="setting" id="setting" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <label for="setting" class="mr-2 block text-sm text-gray-900">تنظیم</label>
                                </div>
                                <input type="text" name="adjustment_value" class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label for="total" class="block text-sm font-medium text-gray-700">جمع کل</label>
                            <input type="text" name="total" id="total" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 rtl:space-x-reverse">
                    <a href="{{ route('sales.quotations.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        لغو
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        ذخیره
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection 