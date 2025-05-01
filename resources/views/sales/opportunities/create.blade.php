<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ایجاد فرصت فروش جدید') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('sales.opportunities.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Opportunity Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">نام فرصت</label>
                                <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            </div>

                            <div>
                                <label for="contact" class="block text-sm font-medium text-gray-700">نام مخاطب</label>
                                <input type="text" name="contact" id="contact" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            </div>

                            <div>
                                <label for="organization" class="block text-sm font-medium text-gray-700">نام سازمان</label>
                                <input type="text" name="organization" id="organization" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">نوع</label>
                                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="">انتخاب کنید</option>
                                    <option value="new">جدید</option>
                                    <option value="existing">موجود</option>
                                    <option value="upgrade">ارتقاء</option>
                                </select>
                            </div>

                            <div>
                                <label for="source" class="block text-sm font-medium text-gray-700">منبع</label>
                                <select name="source" id="source" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">انتخاب کنید</option>
                                    <option value="website">وبسایت</option>
                                    <option value="social">شبکه‌های اجتماعی</option>
                                    <option value="referral">معرفی</option>
                                    <option value="other">سایر</option>
                                </select>
                            </div>

                            <div>
                                <label for="assigned_to" class="block text-sm font-medium text-gray-700">ارجاع به</label>
                                <select name="assigned_to" id="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="">انتخاب کنید</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="success_rate" class="block text-sm font-medium text-gray-700">نرخ موفقیت (%)</label>
                                <input type="number" name="success_rate" id="success_rate" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">مبلغ (ریال)</label>
                                <input type="number" name="amount" id="amount" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="next_follow_up" class="block text-sm font-medium text-gray-700">تاریخ پیگیری بعدی</label>
                                <input type="datetime-local" name="next_follow_up" id="next_follow_up" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">توضیحات</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 space-x-reverse">
                            <a href="{{ route('sales.opportunities.index') }}" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                انصراف
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                ذخیره
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 