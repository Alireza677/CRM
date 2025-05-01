<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ویرایش سرنخ فروش') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('marketing.leads.update', $lead) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Basic Information Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Prefix -->
                            <div>
                                <x-input-label for="prefix" :value="__('پیشوند')" />
                                <x-text-input id="prefix" name="prefix" type="text" class="mt-1 block w-full" :value="old('prefix', $lead->prefix)" />
                                <x-input-error :messages="$errors->get('prefix')" class="mt-2" />
                            </div>

                            <!-- First Name -->
                            <div>
                                <x-input-label for="first_name" :value="__('نام')" />
                                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $lead->first_name)" required />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <x-input-label for="last_name" :value="__('نام خانوادگی')" />
                                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $lead->last_name)" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>

                            <!-- Company -->
                            <div>
                                <x-input-label for="company" :value="__('شرکت')" />
                                <x-text-input id="company" name="company" type="text" class="mt-1 block w-full" :value="old('company', $lead->company)" />
                                <x-input-error :messages="$errors->get('company')" class="mt-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <x-input-label for="email" :value="__('ایمیل')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $lead->email)" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <!-- Mobile -->
                            <div>
                                <x-input-label for="mobile" :value="__('شماره موبایل')" />
                                <div class="flex mt-1">
                                    <select name="mobile_country" class="rounded-l-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="+98">🇮🇷 +98</option>
                                    </select>
                                    <x-text-input id="mobile" name="mobile" type="text" class="rounded-r-md block w-full" :value="old('mobile', $lead->mobile)" />
                                </div>
                                <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
                            </div>

                            <!-- Phone -->
                            <div>
                                <x-input-label for="phone" :value="__('تلفن')" />
                                <div class="flex mt-1">
                                    <select name="phone_country" class="rounded-l-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="+98">🇮🇷 +98</option>
                                    </select>
                                    <x-text-input id="phone" name="phone" type="text" class="rounded-r-md block w-full" :value="old('phone', $lead->phone)" />
                                </div>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>

                            <!-- Website -->
                            <div>
                                <x-input-label for="website" :value="__('وب سایت')" />
                                <x-text-input id="website" name="website" type="url" class="mt-1 block w-full" :value="old('website', $lead->website)" />
                                <x-input-error :messages="$errors->get('website')" class="mt-2" />
                            </div>

                            <!-- Lead Source -->
                            <div>
                                <x-input-label for="lead_source" :value="__('منبع سرنخ')" />
                                <select id="lead_source" name="lead_source" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required>
                                    <option value="">انتخاب کنید</option>
                                    <option value="وب سایت" {{ old('lead_source', $lead->lead_source) == 'وب سایت' ? 'selected' : '' }}>وب سایت</option>
                                    <option value="نمایشگاه" {{ old('lead_source', $lead->lead_source) == 'نمایشگاه' ? 'selected' : '' }}>نمایشگاه</option>
                                    <option value="معرفی" {{ old('lead_source', $lead->lead_source) == 'معرفی' ? 'selected' : '' }}>معرفی</option>
                                    <option value="تبلیغات" {{ old('lead_source', $lead->lead_source) == 'تبلیغات' ? 'selected' : '' }}>تبلیغات</option>
                                    <option value="سایر" {{ old('lead_source', $lead->lead_source) == 'سایر' ? 'selected' : '' }}>سایر</option>
                                </select>
                                <x-input-error :messages="$errors->get('lead_source')" class="mt-2" />
                            </div>

                            <!-- Lead Status -->
                            <div>
                                <x-input-label for="lead_status" :value="__('وضعیت سرنخ فروش')" />
                                <select id="lead_status" name="lead_status" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required>
                                    <option value="">انتخاب کنید</option>
                                    <option value="تماس اولیه" {{ old('lead_status', $lead->lead_status) == 'تماس اولیه' ? 'selected' : '' }}>تماس اولیه</option>
                                    <option value="موکول به آینده" {{ old('lead_status', $lead->lead_status) == 'موکول به آینده' ? 'selected' : '' }}>موکول به آینده</option>
                                    <option value="در حال پیگیری" {{ old('lead_status', $lead->lead_status) == 'در حال پیگیری' ? 'selected' : '' }}>در حال پیگیری</option>
                                    <option value="تبدیل شده" {{ old('lead_status', $lead->lead_status) == 'تبدیل شده' ? 'selected' : '' }}>تبدیل شده</option>
                                    <option value="از دست رفته" {{ old('lead_status', $lead->lead_status) == 'از دست رفته' ? 'selected' : '' }}>از دست رفته</option>
                                </select>
                                <x-input-error :messages="$errors->get('lead_status')" class="mt-2" />
                            </div>

                            <!-- Assigned To -->
                            <div>
                                <x-input-label for="assigned_to" :value="__('ارجاع به')" />
                                <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required>
                                    <option value="">انتخاب کنید</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('assigned_to', $lead->assigned_to) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
                            </div>

                            <!-- Lead Date -->
                            <div>
                                <x-input-label for="lead_date" :value="__('تاریخ ثبت سرنخ')" />
                                <x-text-input id="lead_date" name="lead_date" type="date" class="mt-1 block w-full" :value="old('lead_date', $lead->lead_date->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('lead_date')" class="mt-2" />
                            </div>

                            <!-- Next Follow Up Date -->
                            <div>
                                <x-input-label for="next_follow_up_date" :value="__('تاریخ پیگیری بعدی')" />
                                <x-text-input id="next_follow_up_date" name="next_follow_up_date" type="date" class="mt-1 block w-full" :value="old('next_follow_up_date', $lead->next_follow_up_date->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('next_follow_up_date')" class="mt-2" />
                            </div>

                            <!-- Do Not Email -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="do_not_email" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" {{ old('do_not_email', $lead->do_not_email) ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-600">عدم ارسال ایمیل</span>
                                </label>
                            </div>

                            <!-- Customer Type -->
                            <div>
                                <x-input-label for="customer_type" :value="__('نوع مشتری')" />
                                <select id="customer_type" name="customer_type" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                    <option value="">انتخاب کنید</option>
                                    <option value="مشتری جدید" {{ old('customer_type', $lead->customer_type) == 'مشتری جدید' ? 'selected' : '' }}>مشتری جدید</option>
                                    <option value="مشتری قدیمی" {{ old('customer_type', $lead->customer_type) == 'مشتری قدیمی' ? 'selected' : '' }}>مشتری قدیمی</option>
                                    <option value="مشتری بالقوه" {{ old('customer_type', $lead->customer_type) == 'مشتری بالقوه' ? 'selected' : '' }}>مشتری بالقوه</option>
                                </select>
                                <x-input-error :messages="$errors->get('customer_type')" class="mt-2" />
                            </div>

                            <!-- Industry -->
                            <div>
                                <x-input-label for="industry" :value="__('صنعت')" />
                                <x-text-input id="industry" name="industry" type="text" class="mt-1 block w-full" :value="old('industry', $lead->industry)" />
                                <x-input-error :messages="$errors->get('industry')" class="mt-2" />
                            </div>

                            <!-- Nationality -->
                            <div>
                                <x-input-label for="nationality" :value="__('تابعیت')" />
                                <x-text-input id="nationality" name="nationality" type="text" class="mt-1 block w-full" :value="old('nationality', $lead->nationality)" />
                                <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
                            </div>

                            <!-- Main Test Field -->
                            <div>
                                <x-input-label for="main_test_field" :value="__('تست فیلد اصلی')" />
                                <select id="main_test_field" name="main_test_field" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                    <option value="">انتخاب کنید</option>
                                    <option value="گزینه 1" {{ old('main_test_field', $lead->main_test_field) == 'گزینه 1' ? 'selected' : '' }}>گزینه 1</option>
                                    <option value="گزینه 2" {{ old('main_test_field', $lead->main_test_field) == 'گزینه 2' ? 'selected' : '' }}>گزینه 2</option>
                                    <option value="گزینه 3" {{ old('main_test_field', $lead->main_test_field) == 'گزینه 3' ? 'selected' : '' }}>گزینه 3</option>
                                </select>
                                <x-input-error :messages="$errors->get('main_test_field')" class="mt-2" />
                            </div>

                            <!-- Dependent Test Field -->
                            <div>
                                <x-input-label for="dependent_test_field" :value="__('تست فیلد وابسته')" />
                                <select id="dependent_test_field" name="dependent_test_field" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                    <option value="">انتخاب کنید</option>
                                </select>
                                <x-input-error :messages="$errors->get('dependent_test_field')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900">آدرس</h3>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Address -->
                                <div class="md:col-span-3">
                                    <x-input-label for="address" :value="__('آدرس')" />
                                    <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">{{ old('address', $lead->address) }}</textarea>
                                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                                </div>

                                <!-- State -->
                                <div>
                                    <x-input-label for="state" :value="__('استان')" />
                                    <x-text-input id="state" name="state" type="text" class="mt-1 block w-full" :value="old('state', $lead->state)" />
                                    <x-input-error :messages="$errors->get('state')" class="mt-2" />
                                </div>

                                <!-- City -->
                                <div>
                                    <x-input-label for="city" :value="__('شهر')" />
                                    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $lead->city)" />
                                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mt-6">
                            <x-input-label for="notes" :value="__('یادداشت‌ها')" />
                            <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">{{ old('notes', $lead->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button>
                                {{ __('ذخیره') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic dependent field
    const mainField = document.getElementById('main_test_field');
    const dependentField = document.getElementById('dependent_test_field');

    const dependentOptions = {
        'گزینه 1': ['وابسته 1-1', 'وابسته 1-2', 'وابسته 1-3'],
        'گزینه 2': ['وابسته 2-1', 'وابسته 2-2', 'وابسته 2-3'],
        'گزینه 3': ['وابسته 3-1', 'وابسته 3-2', 'وابسته 3-3']
    };

    function updateDependentField() {
        const selectedValue = mainField.value;
        dependentField.innerHTML = '<option value="">انتخاب کنید</option>';
        
        if (selectedValue && dependentOptions[selectedValue]) {
            dependentOptions[selectedValue].forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option;
                optionElement.textContent = option;
                if (option === '{{ old('dependent_test_field', $lead->dependent_test_field) }}') {
                    optionElement.selected = true;
                }
                dependentField.appendChild(optionElement);
            });
        }
    }

    mainField.addEventListener('change', updateDependentField);
    updateDependentField(); // Initial load
});
</script>
@endpush 