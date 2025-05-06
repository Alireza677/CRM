<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ایجاد پیش‌فاکتور جدید') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('sales.proformas.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Subject -->
                        <div>
                            <x-input-label for="subject" :value="__('موضوع')" />
                            <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                        </div>

                        <!-- Organization -->
                        <div>
                            <x-input-label for="organization_id" :value="__('سازمان')" />
                            <select id="organization_id" name="organization_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">انتخاب کنید</option>
                                @foreach($organizations as $organization)
                                    <option value="{{ $organization->id }}" {{ old('organization_id') == $organization->id ? 'selected' : '' }}>
                                        {{ $organization->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('organization_id')" />
                        </div>

                        <!-- Contact -->
                        <div>
                            <x-input-label for="contact_id" :value="__('مخاطب')" />
                            <select id="contact_id" name="contact_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">انتخاب کنید</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->first_name }} {{ $contact->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('contact_id')" />
                        </div>

                        <!-- Total Amount -->
                        <div>
                            <x-input-label for="total_amount" :value="__('مبلغ کل')" />
                            <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" class="mt-1 block w-full" :value="old('total_amount')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('total_amount')" />
                        </div>

                        <!-- Proforma Date -->
                        <div>
                            <x-input-label for="proforma_date" :value="__('تاریخ پیش‌فاکتور')" />
                            <x-text-input id="proforma_date" name="proforma_date" type="date" class="mt-1 block w-full" :value="old('proforma_date')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('proforma_date')" />
                        </div>

                        <!-- Opportunity -->
                        <div>
                            <x-input-label for="opportunity_id" :value="__('فرصت')" />
                            <select id="opportunity_id" name="opportunity_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">انتخاب کنید</option>
                                @foreach($opportunities as $opportunity)
                                    <option value="{{ $opportunity->id }}" {{ old('opportunity_id') == $opportunity->id ? 'selected' : '' }}>
                                        {{ $opportunity->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('opportunity_id')" />
                        </div>

                        <!-- Assigned To -->
                        <div>
                            <x-input-label for="assigned_to" :value="__('ارجاع به')" />
                            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">انتخاب کنید</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('assigned_to')" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-secondary-button class="ml-4" onclick="window.location='{{ route('sales.proformas.index') }}'">
                                {{ __('انصراف') }}
                            </x-secondary-button>
                            <x-primary-button class="ml-4">
                                {{ __('ذخیره') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 