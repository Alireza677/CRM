@php
    $service = $service ?? null;
@endphp

<div class="space-y-6">
    <div>
        <label for="customer_name" class="block text-sm font-medium text-gray-700">
            نام مشتری <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            name="customer_name"
            id="customer_name"
            value="{{ old('customer_name', optional($service)->customer_name) }}"
            class="mt-2 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
            required
        >
        @error('customer_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="address" class="block text-sm font-medium text-gray-700">
            آدرس <span class="text-red-500">*</span>
        </label>
        <textarea
            name="address"
            id="address"
            rows="3"
            class="mt-2 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
            required
        >{{ old('address', optional($service)->address) }}</textarea>
        @error('address')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="coordinator_name" class="block text-sm font-medium text-gray-700">
                مسئول هماهنگ‌کننده <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                name="coordinator_name"
                id="coordinator_name"
                value="{{ old('coordinator_name', optional($service)->coordinator_name) }}"
                class="mt-2 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                required
            >
            @error('coordinator_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="coordinator_mobile" class="block text-sm font-medium text-gray-700">
                شماره همراه هماهنگ‌کننده <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                name="coordinator_mobile"
                id="coordinator_mobile"
                value="{{ old('coordinator_mobile', optional($service)->coordinator_mobile) }}"
                class="mt-2 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                required
                placeholder="مثلاً 09121234567"
            >
            @error('coordinator_mobile')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="issue_description" class="block text-sm font-medium text-gray-700">
            شرح مشکل دستگاه <span class="text-red-500">*</span>
        </label>
        <textarea
            name="issue_description"
            id="issue_description"
            rows="4"
            class="mt-2 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
            required
        >{{ old('issue_description', optional($service)->issue_description) }}</textarea>
        @error('issue_description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
