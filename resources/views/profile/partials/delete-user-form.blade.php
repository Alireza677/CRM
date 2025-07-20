<form method="POST" action="{{ route('profile.destroy') }}" class="text-right">
    @csrf
    @method('DELETE')

    <p class="text-sm text-gray-600 mb-4 leading-relaxed">
        با حذف حساب کاربری، تمام اطلاعات شما به‌صورت دائمی حذف خواهد شد. لطفاً قبل از حذف، اطلاعات مورد نیاز را ذخیره نمایید.
    </p>

    <button type="submit"
            class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 transition duration-150">
        حذف حساب کاربری
    </button>
</form>
