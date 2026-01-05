<form method="post" action="{{ route('profile.update') }}" class="space-y-4" enctype="multipart/form-data" x-ref="profileForm">
    @csrf
    @method('patch')

    @php
        $profileUser = Auth::user();
        $profileAvatarUrl = $profileUser && $profileUser->profile_photo_path
            ? asset('storage/' . $profileUser->profile_photo_path)
            : asset('images/user.png');
    @endphp

    <div class="flex items-center gap-4">
        <img
            src="{{ $profileAvatarUrl }}"
            alt="User avatar"
            class="h-16 w-16 rounded-full object-cover border border-gray-200"
        >
        <div class="flex-1" x-data="{ fileName: 'عکسی انتخاب نشده' }">
            <label class="block text-sm font-medium text-gray-700">عکس پروفایل</label>
            <div class="mt-1 flex items-center gap-2">
                <input
                    id="profile_photo"
                    name="profile_photo"
                    type="file"
                    accept="image/*"
                    class="sr-only"
                    @change="fileName = $event.target.files?.[0]?.name || 'عکسی انتخاب نشده'; $refs.profileForm.submit()"
                >
                <label for="profile_photo" class="px-3 py-1.5 rounded-md bg-gray-200 text-gray-700 cursor-pointer">
                    انتخاب عکس
                </label>
                <span class="text-sm text-gray-600" x-text="fileName"></span>
            </div>
            <p class="text-xs text-gray-500 mt-1">حداکثر ۲ مگابایت (JPG, PNG, WEBP)</p>
            @error('profile_photo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">نام</label>
            <input name="name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                   value="{{ old('name', Auth::user()->name) }}">
            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ایمیل</label>
            <input name="email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                   value="{{ old('email', Auth::user()->email) }}">
            @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">نام کاربری</label>
            <input name="username" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                   value="{{ old('username', Auth::user()->username) }}">
            @error('username') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">شماره موبایل</label>
            <input name="mobile" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                   placeholder="+98912XXXXXXX"
                   value="{{ old('mobile', Auth::user()->mobile) }}">
            @error('mobile') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="pt-2">
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md shadow">
            ذخیره تغییرات
        </button>
    </div>
</form>
