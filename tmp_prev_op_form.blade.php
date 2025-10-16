@php
    // $opportunity (nullable) : +¿+¦+º¦î edit +à+ê+¼+ê+» +º+¦+¬+î +¿+¦+º¦î create +«+º+ä¦î +º+¦+¬
    // $users, $contacts, $organizations, $defaultContact (nullable)
    // $nextFollowUpDate (shamsi) +¿+¦+º¦î edit +º+¦ +¬+å+¬+¦+ä+¦ +++º+¦ +à¦îGÇî+¦+ê+»

    $isEdit = isset($opportunity) && $opportunity?->id;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- +¦+å+ê+º+å --}}
    <div>
        <label for="name" class="block font-medium text-sm text-gray-700 required">+¦+å+ê+º+å</label>
        <input id="name" name="name" type="text"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm form-field"
               value="{{ old('name', $opportunity->name ?? '') }}" required>
        @error('name') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- +¦+º+¦+à+º+å --}}
    <div>
        <label for="organization_id" class="block font-medium text-sm text-gray-700">+¦+º+¦+à+º+å</label>
        <div class="flex items-center gap-2">
            <input type="text" id="organization_name" name="organization_name"
                   class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                   placeholder="+º+å+¬+«+º+¿ +¦+º+¦+à+º+å" readonly
                   value="{{ old('organization_name', optional($opportunity->organization ?? null)->name) }}">
            <input type="hidden" id="organization_id" name="organization_id"
                   value="{{ old('organization_id', optional($opportunity->organization ?? null)->id) }}">
            <button type="button" onclick="openOrganizationModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">=ƒöì</button>
        </div>
        @error('organization_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- +à+«+º+++¿ --}}
    <div>
        <label for="contact_display" class="block font-medium text-sm text-gray-700">+à+«+º+++¿</label>
        <div class="relative">
            <input type="text" id="contact_display"
                   class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                   placeholder="+º+å+¬+«+º+¿ +à+«+º+++¿..." readonly
                   value="{{ old('contact_display',
                            ($defaultContact->full_name ?? '') ?: optional($opportunity->contact ?? null)->full_name) }}">
            <input type="hidden" name="contact_id" id="contact_id"
                   value="{{ old('contact_id',
                            ($defaultContact->id ?? '') ?: optional($opportunity->contact ?? null)->id) }}">
            <button type="button" onclick="openContactModal()"
                    class="absolute inset-y-0 left-0 flex items-center px-3 text-gray-500 hover:text-blue-600">=ƒöì</button>
        </div>
        @error('contact_id') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- +å+ê+¦ +¬+¦+¿GÇî+ê+¬+º+¦ --}}
    <div>
        <label for="type" class="block font-medium text-sm text-gray-700">+å+ê+¦ +¬+¦+¿GÇî+ê+¬+º+¦</label>
        <select id="type" name="type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @php $type = old('type', $opportunity->type ?? ''); @endphp
            <option value="">+º+å+¬+«+º+¿ +¬+å¦î+»</option>
            <option value="+¬+¦+¿ +ê +¬+º+¦ +à+ê+¼+ê+»" {{ $type === '+¬+¦+¿ +ê +¬+º+¦ +à+ê+¼+ê+»' ? 'selected' : '' }}>+¬+¦+¿ +ê +¬+º+¦ +à+ê+¼+ê+»</option>
            <option value="+¬+¦+¿ +ê +¬+º+¦ +¼+»¦î+»"  {{ $type === '+¬+¦+¿ +ê +¬+º+¦ +¼+»¦î+»'  ? 'selected' : '' }}>+¬+¦+¿ +ê +¬+º+¦ +¼+»¦î+»</option>
        </select>
        @error('type') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>
    {{-- +¬+º+¦+¿+¦¦î +¦+º+«+¬+à+º+å --}}
    <div>
        <label for="building_usage" class="block font-medium text-sm text-gray-700 required">
            +¬+º+¦+¿+¦¦î +¦+º+«+¬+à+º+å
        </label>
        @php $buildingUsage = old('building_usage', $opportunity->building_usage ?? ''); @endphp
        <select id="building_usage" name="building_usage" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">+º+å+¬+«+º+¿ +¬+å¦î+»...</option>
            @foreach([
                '+¬+º+¦+»+º+ç +ê ¦î+º +¬+º+¦+«+º+å+ç',
                '+ü+¦+º¦î +¿+º+¦ +ê +¦+¦+¬+ê+¦+º+å',
                '+¬+¦+à¦î+¦+»+º+ç +ê +¦+º+ä+å +¦+å+¦+¬¦î',
                '+»+ä+«+º+å+ç +ê +++¦+ê+¦+¦ +»¦î+º+ç',
                '+à+¦+¦+»+º+¦¦î +ê +++¦+ê+¦+¦ +»+º+à +ê ++¦î+ê+¦',
                '+ü+¦+ê+¦+»+º+ç +ê +à+¦+º+¬+¦ +«+¦¦î+»',
                '+¦+º+ä+å +ê +¿+º+¦+»+º+ç +ç+º¦î +ê+¦+¦+¦¦î',
                '+¦+º+ä+å +ç+º¦î +å+à+º¦î+¦',
                '+à+»+º+¦+¦ +ê +à+¡¦î++ +ç+º¦î +ó+à+ê+¦+¦¦î',
                '+¦+º¦î+¦'
            ] as $opt)
                <option value="{{ $opt }}" {{ $buildingUsage === $opt ? 'selected' : '' }}>
                    {{ $opt }}
                </option>
            @endforeach
        </select>
        @error('building_usage') 
            <div class="text-red-500 text-xs mt-2">{{ $message }}</div> 
        @enderror
    </div>

    {{-- +à+¦+¡+ä+ç +ü+¦+ê+¦ --}}
    <div>
        <label for="stage" class="block font-medium text-sm text-gray-700 required">+à+¦+¡+ä+ç +ü+¦+ê+¦</label>
        @php $stage = old('stage', $opportunity->stage ?? ''); @endphp
        <select name="stage" id="stage" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">+º+å+¬+«+º+¿ +¬+å¦î+»...</option>
            @foreach(['+»+¦ +¡+º+ä ++¦î+»¦î+¦¦î','++¦î+»¦î+¦¦î +»+¦ +ó¦î+å+»+ç','+¿+¦+å+»+ç','+¿+º+¦+å+»+ç','+¦+¦+¬+º+¦¦î','+º+¦+¦+º+ä ++¦î+¦ +ü+º+¬+¬+ê+¦'] as $opt)
                <option value="{{ $opt }}" {{ $stage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
        @error('stage') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- +à+å+¿+¦ +¦+¦+å+« --}}
    <div>
        <label for="source" class="block font-medium text-sm text-gray-700 required">+à+å+¿+¦ +¦+¦+å+«</label>
        @php $source = old('source', $opportunity->source ?? ''); @endphp
        <select id="source" name="source" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">+º+å+¬+«+º+¿ +¬+å¦î+»</option>
            @foreach(['+ê+¿ +¦+º¦î+¬','+à+¦+¬+¦¦î+º+å +é+»¦î+à¦î','+å+à+º¦î+¦+»+º+ç','+¿+º+¦+º+¦¦î+º+¿¦î +¡+¦+ê+¦¦î'] as $opt)
                <option value="{{ $opt }}" {{ $source === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
        @error('source') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- +º+¦+¼+º+¦ +¿+ç --}}
    <div>
        <label for="assigned_to" class="block font-medium text-sm text-gray-700 required">+º+¦+¼+º+¦ +¿+ç</label>
        @php $assigned = old('assigned_to', $opportunity->assigned_to ?? ''); @endphp
        <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">+º+å+¬+«+º+¿ +¬+å¦î+»</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ (string)$assigned === (string)$user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('assigned_to') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    {{-- +»+¦+¦+» +à+ê+ü+é¦î+¬ --}}
    <div>
        <label for="success_rate" class="block font-medium text-sm text-gray-700">+»+¦+¦+» +à+ê+ü+é¦î+¬</label>
        <input id="success_rate" name="success_rate" type="number" min="0" max="100"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
               value="{{ old('success_rate', $opportunity->success_rate ?? '') }}" required>
        @error('success_rate') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>

    
    {{-- +¬+º+¦¦î+« ++¦î+»¦î+¦¦î +¿+¦+»¦î (+å+à+º¦î+¦ +¦+à+¦¦î + hidden +à¦î+ä+º+»¦î) --}}
<div class="md:col-span-2">
    <label for="next_follow_up_shamsi" class="block font-medium text-sm text-gray-700">
        +¬+º+¦¦î+« ++¦î+»¦î+¦¦î +¿+¦+»¦î
    </label>

    {{-- +ê+¦+ê+»¦î +å+à+º¦î+¦¦î +¦+à+¦¦î (+ü+é++ name + data-jdp +¬+º+ü¦î+¦+¬) --}}
    <input
        type="text"
        id="next_follow_up_shamsi"
        name="next_follow_up_shamsi"
        data-jdp
        dir="ltr"
        class="form-control"
        placeholder="+º+å+¬+«+º+¿ +¬+º+¦¦î+«"
        value="{{ old('next_follow_up_shamsi') }}"
    >

    {{-- hidden +à¦î+ä+º+»¦î +¬+ç +¿+ç +»¦î+¬+º+¿¦î+¦ +à¦îGÇî+¦+ê+» --}}
    <input
        type="hidden"
        name="next_follow_up"
        id="next_follow_up"
        value="{{ old('next_follow_up', $opportunity->next_follow_up ?? '') }}"
    >

    @error('next_follow_up')
        <span class="text-danger text-xs">{{ $message }}</span>
    @enderror
</div>


    {{-- +¬+ê+¦¦î+¡+º+¬ --}}
    <div class="md:col-span-2">
        <label for="description" class="block font-medium text-sm text-gray-700">+¬+ê+¦¦î+¡+º+¬</label>
        <textarea id="description" name="description" rows="3"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $opportunity->description ?? '') }}</textarea>
        @error('description') <div class="text-red-500 text-xs mt-2">{{ $message }}</div> @enderror
    </div>
</div>


