<form method="post" action="{{ route('profile.update') }}" class="space-y-4">
    @csrf
    @method('patch')

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
