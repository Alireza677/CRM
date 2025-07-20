<form method="POST" action="{{ route('password.update') }}" class="space-y-4 text-right">
    @csrf

    <div>
        <label for="current_password" class="block text-sm font-medium text-gray-700">رمز عبور فعلی</label>
        <input id="current_password" name="current_password" type="password"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
    </div>

    <div>
        <label for="new_password" class="block text-sm font-medium text-gray-700">رمز عبور جدید</label>
        <input id="new_password" name="new_password" type="password"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
    </div>

    <div>
        <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">تأیید رمز عبور جدید</label>
        <input id="new_password_confirmation" name="new_password_confirmation" type="password"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition duration-150">
        ذخیره رمز عبور
    </button>
</form>
