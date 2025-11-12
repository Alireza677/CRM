@extends('layouts.app')

@section('content')

    @section('header')
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            داشبورد گزارش‌گیری پیامک
        </h2>
    @endsection

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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

                    <div class="mb-4 flex items-center justify-end">
                        <a href="{{ route('tools.sms.create') }}" class="btn btn-secondary">
                            بازگشت به ارسال پیامک
                        </a>
                    </div>

                    <form method="GET" action="{{ route('tools.sms.report') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">از تاریخ</label>
                            <input type="hidden" id="date_from" name="date_from" value="{{ $filters['date_from'] }}">
                            <input
                                type="text"
                                id="date_from_shamsi"
                                class="persian-datepicker form-input w-full"
                                data-alt-field="date_from"
                                autocomplete="off"
                                value="">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">تا تاریخ</label>
                            <input type="hidden" id="date_to" name="date_to" value="{{ $filters['date_to'] }}">
                            <input
                                type="text"
                                id="date_to_shamsi"
                                class="persian-datepicker form-input w-full"
                                data-alt-field="date_to"
                                autocomplete="off"
                                value="">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
                        </div>
                        <div class="flex items-end">
                            <a class="btn btn-secondary" href="{{ route('tools.sms.report.export', request()->query()) }}">خروجی CSV</a>
                        </div>
                    </form>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="p-4 bg-gray-50 rounded border">
                            <div class="text-sm text-gray-500">کل</div>
                            <div class="text-2xl font-semibold">{{ $stats['total'] }}</div>
                        </div>
                        <div class="p-4 bg-green-50 rounded border">
                            <div class="text-sm text-gray-600">ارسال شده</div>
                            <div class="text-2xl font-semibold text-green-700">{{ $stats['delivered'] }}</div>
                        </div>
                        <div class="p-4 bg-red-50 rounded border">
                            <div class="text-sm text-gray-600">ارسال نشده</div>
                            <div class="text-2xl font-semibold text-red-700">{{ $stats['failed'] }}</div>
                        </div>
                        <div class="p-4 bg-yellow-50 rounded border">
                            <div class="text-sm text-gray-600">درحال ارسال</div>
                            <div class="text-2xl font-semibold text-yellow-700">{{ $stats['pending'] }}</div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700">
                                    <th class="px-3 py-2 text-right">تاریخ</th>
                                    <th class="px-3 py-2 text-right">ارسال‌کننده</th>
                                    <th class="px-3 py-2 text-right">به</th>
                                    <th class="px-3 py-2 text-right">متن</th>
                                    <th class="px-3 py-2 text-right">وضعیت</th>
                                    <th class="px-3 py-2 text-right">شناسه ارائه‌دهنده</th>
                                    <th class="px-3 py-2 text-right">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($logs as $log)
                                <tr class="border-b">
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $log->created_at }}</td>
                                    <td>{{ optional($log->sender)->name ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $log->to }}</td>
                                    <td class="px-3 py-2">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</td>
                                    <td class="px-3 py-2">
                                        @php $s = $log->status; @endphp
                                        @if($s)
                                            @php
                                                $color = match($s) {
                                                    'delivered' => 'text-green-700',
                                                    'failed', 'rejected' => 'text-red-700',
                                                    'sent','accepted','queued','pending' => 'text-yellow-700',
                                                    default => 'text-gray-700'
                                                };
                                                $label = match($s) {
                                                    'delivered' => 'تحویل شد',
                                                    'failed' => 'ناموفق',
                                                    'rejected' => 'رد شد',
                                                    'sent' => 'ارسال شد',
                                                    'accepted' => 'پذیرفته شد',
                                                    'queued' => 'در صف',
                                                    'pending' => 'در انتظار',
                                                    default => $s,
                                                };
                                            @endphp
                                            <span class="{{ $color }}" title="{{ $log->status_updated_at ? ('بروزرسانی: ' . $log->status_updated_at) : '' }}">{{ $label }}</span>
                                        @elseif($log->status_code === 200)
                                            <span class="text-green-700">ارسال شد</span>
                                        @elseif($log->status_text === 'ERROR' || ($log->status_code && $log->status_code >= 400))
                                            <span class="text-red-700">ارسال نشده</span>
                                        @else
                                            <span class="text-yellow-700">درحال ارسال</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">{{ $log->provider_message_id }}</td>
                                    <td class="px-3 py-2">
                                        @php $isBlack = in_array($log->to, $blacklistedSet, true); @endphp
                                        @if(!$isBlack)
                                            <form method="POST" action="{{ route('tools.sms.blacklist.add') }}">
                                                @csrf
                                                <input type="hidden" name="mobile" value="{{ $log->to }}" />
                                                <button type="submit" class="btn btn-sm btn-outline-danger">افزودن به بلک‌لیست</button>
                                            </form>
                                        @else
                                            <span class="text-gray-500">در بلک‌لیست</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-500">رکوردی یافت نشد.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $logs->links() }}</div>

                </div>
            </div>
        </div>
    </div>

@endsection
