<form method="POST" action="{{ route('profile.update') }}" class="space-y-4 text-right">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">نام</label>
        <input id="name" name="name" type="text" value="{{ old('name', Auth::user()->name) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200" />
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">ایمیل</label>
        <input id="email" name="email" type="email" value="{{ old('email', Auth::user()->email) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200" />
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition duration-150">
        ذخیره اطلاعات
    </button>
</form>
