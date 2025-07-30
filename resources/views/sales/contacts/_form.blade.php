@csrf

@if(isset($contact))
    @method('PUT')
@endif


<input type="hidden" name="opportunity_id" value="{{ request('opportunity_id', $contact->opportunity_id ?? '') }}">

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="first_name" class="block text-sm font-medium text-gray-700">نام <span class="text-red-500">*</span></label>
        <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $contact->first_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="last_name" class="block text-sm font-medium text-gray-700">نام خانوادگی <span class="text-red-500">*</span></label>
        <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $contact->last_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">ایمیل <span class="text-red-500">*</span></label>
        <input type="email" name="email" id="email" value="{{ old('email', $contact->email ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">شماره تلفن</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $contact->phone ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="mobile" class="block text-sm font-medium text-gray-700">شماره موبایل</label>
        <input type="text" name="mobile" id="mobile" value="{{ old('mobile', $contact->mobile ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="relative">
        <label for="company_input" class="block text-sm font-medium text-gray-700">سازمان</label>
        <div class="flex">
            <input type="text" name="company" id="company_input"
                value="{{ old('company', $contact->organization->name ?? '') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="نام سازمان را وارد کنید یا از جستجو انتخاب کنید">
            
            <!-- آیکن ذره‌بین -->
            <button type="button" id="open-org-modal" class="ml-2 mt-1 inline-flex items-center px-2 bg-gray-200 hover:bg-gray-300 rounded">
                🔍
            </button>
        </div>
    </div>



    <div>
        <label for="city" class="block text-sm font-medium text-gray-700">شهر</label>
        <input type="text" name="city" id="city" value="{{ old('city', $contact->city ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
    <label for="assigned_to" class="block text-sm font-medium text-gray-700">ارجاع به</label>
    <select name="assigned_to" id="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value=""> انتخاب کاربر </option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ old('assigned_to', $contact->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                {{ $user->name ?? ($user->first_name . ' ' . $user->last_name) }}
            </option>
        @endforeach
    </select>
</div>
</div>



<div class="flex justify-end space-x-4 rtl:space-x-reverse mt-6">
    <a href="{{ route('sales.contacts.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">لغو</a>
    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">ذخیره</button>
</div>

<!-- Modal -->
<div id="org-modal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-96 max-h-[80vh] overflow-y-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">انتخاب سازمان</h2>
            <button id="close-org-modal" class="text-red-500 text-xl">×</button>
        </div>
        
        <ul id="org-list" class="space-y-2">
            @foreach($organizations as $org)
                <li>
                    <button type="button"
                            class="org-select-item w-full text-right px-3 py-2 hover:bg-gray-100 rounded text-gray-800"
                            data-name="{{ $org->name }}">
                        {{ $org->name }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</div>





