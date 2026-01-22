@extends('layouts.app')

    @section('header')
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">ارسال پیامک</h2>
            <a href="{{ route('tools.sms.report') }}" class="btn btn-secondary">داشبورد گزارش‌گیری</a>
        </div>
    @endsection

    @section('content')
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if ($errors->any())
                        <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
                            <ul class="list-disc pr-6">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('tools.sms.send') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">انتخاب کاربران (تکی/گروهی)</label>
                                <select name="users[]" id="users" class="form-select w-full" multiple>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->mobile }}</option>
                                    @endforeach
                                </select>
                                <small class="text-gray-500">می‌توانید چند کاربر را انتخاب کنید.</small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">شماره‌ها (دلخواه)</label>
                                <textarea name="mobiles" rows="4" class="form-control w-full" placeholder="شماره‌ها را با ویرگول یا خط جدید جدا کنید">
{{ old('mobiles') }}</textarea>
                                <small class="text-gray-500">فرمت پیشنهادی: +98912xxxxxxx یا 0912xxxxxxx</small>
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="send_to_all" value="1" {{ old('send_to_all') ? 'checked' : '' }}>
                                    <span class="mr-2">ارسال به همه کاربران (دارای شماره موبایل)</span>
                                </label>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">متن پیامک</label>
                                <textarea required name="message" rows="5" class="form-control w-full" placeholder="متن پیامک را وارد کنید">{{ old('message') }}</textarea>
                                <small class="text-gray-500">حداکثر 700 کاراکتر (چند-پیامه محاسبه می‌شود).</small>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-paper-plane ms-1"></i>
                                ارسال پیامک
                            </button>
                        </div>
                    </form>

                    <hr class="my-8" />

                    <h3 class="font-semibold text-lg mb-4">لیست‌های پیامک</h3>

                    <div class="mb-6 p-4 border rounded bg-gray-50">
                        <form method="POST" action="{{ route('tools.sms.lists.store') }}" class="flex gap-3 items-end">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">نام لیست جدید</label>
                                <input type="text" name="name" class="form-input w-full" placeholder="مثلاً مشتریان ویژه" required>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-secondary">ایجاد لیست</button>
                            </div>
                        </form>
                    </div>

                    @isset($lists)
                        @forelse($lists as $list)
                            <div class="mb-6 p-4 border rounded">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="font-medium">
                                        {{ $list->name }}
                                        <span class="text-sm text-gray-500">(تعداد مخاطب: {{ $list->contacts_count }})</span>
                                    </div>
                                    <form method="POST" action="{{ route('tools.sms.lists.destroy', $list) }}" onsubmit="return confirm('حذف این لیست؟ مخاطبین داخل آن حذف نمی‌شوند.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">حذف لیست</button>
                                    </form>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <form method="POST" action="{{ route('tools.sms.lists.contacts.add', $list) }}">
                                            @csrf
                                            <label class="block text-sm font-medium text-gray-700 mb-1">افزودن مخاطبین به این لیست</label>
                                            <select name="contact_ids[]" class="select2-contacts w-full" multiple>
                                                @isset($contacts)
                                                    @foreach($contacts as $c)
                                                        <option value="{{ $c->id }}">{{ trim(($c->first_name.' '.$c->last_name)) }} — {{ $c->mobile }}</option>
                                                    @endforeach
                                                @endisset
                                            </select>
                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-light">افزودن مخاطبین</button>
                                            </div>
                                        </form>

                                        @if($list->contacts->isNotEmpty())
                                            <div class="mt-4">
                                                <div class="text-sm text-gray-600 mb-2">مخاطبین این لیست:</div>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($list->contacts as $c)
                                                        <div class="px-2 py-1 rounded bg-gray-100 text-gray-800 text-sm flex items-center gap-2">
                                                            <span>{{ trim(($c->first_name.' '.$c->last_name)) }} — {{ $c->mobile }}</span>
                                                            <form method="POST" action="{{ route('tools.sms.lists.contacts.remove', [$list, $c]) }}" onsubmit="return confirm('حذف این مخاطب از لیست؟');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600 hover:text-red-800">×</button>
                                                            </form>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <form method="POST" action="{{ route('tools.sms.lists.send', $list) }}">
                                            @csrf
                                            <label class="block text-sm font-medium text-gray-700 mb-1">متن پیام برای این لیست</label>
                                            <textarea name="message" rows="4" class="form-control w-full" placeholder="متن پیام مخصوص این لیست را وارد کنید" required></textarea>
                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fa fa-paper-plane ms-1"></i>
                                                    ارسال به این لیست
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 border rounded bg-gray-50 text-gray-600">
                                هنوز لیستی ساخته نشده است. از فرم بالا یک لیست بسازید.
                            </div>
                        @endforelse
                    @endisset
                </div>
            </div>
        </div>
    </div>

    @endsection

@push('scripts')
<script>
    $(function(){
        if (window.jQuery && $.fn.select2) {
            $('#users').select2({
            width: '100%',
            dir: 'rtl',
            placeholder: 'کاربران را انتخاب کنید',
        });
        }
    });
</script>
<script>
    $(function(){
        if (window.jQuery && $.fn.select2) {
            $('.select2-contacts').select2({
            width: '100%',
            dir: 'rtl',
            placeholder: 'انتخاب مخاطبین...'
        });
        }
    });
</script>
@endpush
