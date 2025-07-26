<form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif
    


    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="name" class="block font-medium text-sm text-gray-700">{{ __('نام سازمان') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $organization->name ?? '') }}" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            @error('name') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="phone" class="block font-medium text-sm text-gray-700">{{ __('شماره تلفن') }}</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $organization->phone ?? '') }}" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('phone') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="website" class="block font-medium text-sm text-gray-700">{{ __('وب‌سایت') }}</label>
            <input id="website" name="website" type="url" value="{{ old('website', $organization->website ?? '') }}" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('website') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="state" class="block font-medium text-sm text-gray-700">{{ __('استان') }}</label>
            <input id="state" name="state" type="text" value="{{ old('state', $organization->state ?? '') }}" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('state') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="city" class="block font-medium text-sm text-gray-700">{{ __('شهر') }}</label>
            <input id="city" name="city" type="text" value="{{ old('city', $organization->city ?? '') }}" 
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('city') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="assigned_to" class="block font-medium text-sm text-gray-700">{{ __('ارجاع به') }}</label>
            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">انتخاب کنید</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('assigned_to', $organization->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>

        {{-- مخاطب مرتبط --}}
        <div>
            <label for="contact_display" class="block font-medium text-sm text-gray-700">مخاطب مرتبط</label>
            <div class="relative">
                <input id="contact_display" name="contact_display" type="text"
                value="{{ old('contact_display', optional(optional($organization)->contact)->first_name . ' ' . optional(optional($organization)->contact)->last_name) }}"
                       readonly
                       class="mt-1 block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-100 cursor-pointer" />
                <input type="hidden" id="contact_id" name="contact_id" value="{{ old('contact_id', $organization->contact_id ?? '') }}" />
                <button type="button" onclick="openContactsModal()"
                        class="absolute inset-y-0 left-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                    🔍
                </button>
            </div>
            @error('contact_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-4">
        <label for="address" class="block font-medium text-sm text-gray-700">{{ __('آدرس') }}</label>
        <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $organization->address ?? '') }}</textarea>
        @error('address') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    <div class="mt-4">
        <label for="description" class="block font-medium text-sm text-gray-700">{{ __('توضیحات') }}</label>
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $organization->description ?? '') }}</textarea>
        @error('description') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    <div class="flex items-center justify-end mt-6">
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
            {{ __('ذخیره') }}
        </button>
    </div>
</form>


<div id="contactsModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white w-full max-w-xl p-6 rounded shadow-lg max-h-[80vh] overflow-y-auto">
        <h2 class="text-lg font-bold mb-4 text-right">انتخاب مخاطب</h2>

        <table class="w-full text-sm text-right border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2 border">نام</th>
                    <th class="p-2 border">موبایل</th>
                    <th class="p-2 border">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contacts as $contact)
                <tr>
                    <td class="p-2 border">{{ $contact->first_name }} {{ $contact->last_name }}</td>
                    <td class="p-2 border">{{ $contact->mobile }}</td>
                    <td class="p-2 border">
                        <button type="button"
                                onclick="selectContact('{{ $contact->id }}', '{{ $contact->first_name }} {{ $contact->last_name }}')"
                                class="text-blue-600 hover:underline">
                            انتخاب
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 text-left">
            <button onclick="closeContactsModal()" class="text-red-600 hover:underline">بستن</button>
        </div>
    </div>
</div>
