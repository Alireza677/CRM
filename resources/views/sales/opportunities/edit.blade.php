<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ویرایش فرصت فروش') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('sales.opportunities.update', $opportunity) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('عنوان')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $opportunity->name)" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="organization_id" :value="__('سازمان')" />
                                <select id="organization_id" name="organization_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">انتخاب کنید</option>
                                    @foreach($organizations as $organization)
                                        <option value="{{ $organization->id }}" {{ old('organization_id', $opportunity->organization_id) == $organization->id ? 'selected' : '' }}>
                                            {{ $organization->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('organization_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="contact_id" :value="__('مخاطب')" />
                                <select id="contact_id" name="contact_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="">انتخاب کنید</option>
                                    @foreach($contacts as $contact)
                                        <option value="{{ $contact->id }}" {{ old('contact_id', $opportunity->contact_id) == $contact->id ? 'selected' : '' }}>
                                            {{ $contact->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('contact_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="type" :value="__('نوع')" />
                                <select id="type" name="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">انتخاب کنید</option>
                                    <option value="کسب و کار موجود" {{ old('type', $opportunity->type) == 'کسب و کار موجود' ? 'selected' : '' }}>کسب و کار موجود</option>
                                    <option value="کسب و کار جدید" {{ old('type', $opportunity->type) == 'کسب و کار جدید' ? 'selected' : '' }}>کسب و کار جدید</option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="source" :value="__('منبع')" />
                                <select id="source" name="source" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">انتخاب کنید</option>
                                    <option value="وب سایت" {{ old('source', $opportunity->source) == 'وب سایت' ? 'selected' : '' }}>وب سایت</option>
                                    <option value="مشتریان قدیمی" {{ old('source', $opportunity->source) == 'مشتریان قدیمی' ? 'selected' : '' }}>مشتریان قدیمی</option>
                                    <option value="نمایشگاه" {{ old('source', $opportunity->source) == 'نمایشگاه' ? 'selected' : '' }}>نمایشگاه</option>
                                    <option value="بازاریابی حضوری" {{ old('source', $opportunity->source) == 'بازاریابی حضوری' ? 'selected' : '' }}>بازاریابی حضوری</option>
                                </select>
                                <x-input-error :messages="$errors->get('source')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="assigned_to" :value="__('ارجاع به')" />
                                <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">انتخاب کنید</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('assigned_to', $opportunity->assigned_to) == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="success_rate" :value="__('درصد موفقیت')" />
                                <x-text-input id="success_rate" name="success_rate" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('success_rate', $opportunity->success_rate)" />
                                <x-input-error :messages="$errors->get('success_rate')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="amount" :value="__('مبلغ')" />
                                <x-text-input id="amount" name="amount" type="number" min="0" class="mt-1 block w-full" :value="old('amount', $opportunity->amount)" />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="next_follow_up" :value="__('تاریخ پیگیری بعدی')" />
                                <x-text-input id="next_follow_up" name="next_follow_up" type="date" class="mt-1 block w-full" :value="old('next_follow_up', $opportunity->next_follow_up)" />
                                <x-input-error :messages="$errors->get('next_follow_up')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('توضیحات')" />
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $opportunity->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('ذخیره') }}</x-primary-button>
                            <a href="{{ route('sales.opportunities.show', $opportunity) }}" class="text-gray-600 hover:text-gray-900">انصراف</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 