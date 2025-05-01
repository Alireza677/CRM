<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('جزئیات سرنخ فروش') }}
            </h2>
            <div class="flex space-x-2 space-x-reverse">
                <a href="{{ route('marketing.leads.edit', $lead) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('ویرایش') }}
                </a>
                <a href="{{ route('marketing.leads.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('بازگشت') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Include the sidebar component -->
            <x-lead-sidebar :lead="$lead" :activeSection="$activeSection ?? 'summary'" />

            <!-- Main content with right margin for sidebar -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mr-64">
                <div class="p-6">
                    <!-- Summary Section -->
                    <div id="summary" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('خلاصه') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <p class="text-sm text-gray-500">{{ __('نام') }}</p>
                                <p class="mt-1">{{ $lead->first_name }} {{ $lead->last_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('شرکت') }}</p>
                                <p class="mt-1">{{ $lead->company ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('وضعیت سرنخ') }}</p>
                                <p class="mt-1">{{ $lead->lead_status }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Information Section -->
                    @include('partials.lead-details')

                    <!-- Follow-ups Section -->
                    <div id="updates" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('پیگیری‌ها') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500">{{ __('تاریخ ثبت سرنخ') }}</p>
                                <p class="mt-1">{{ $lead->lead_date?->format('Y/m/d') ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('تاریخ پیگیری بعدی') }}</p>
                                <p class="mt-1">{{ $lead->next_follow_up_date?->format('Y/m/d') ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div id="address" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('آدرس') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-3">
                                <p class="text-sm text-gray-500">{{ __('آدرس کامل') }}</p>
                                <p class="mt-1">{{ $lead->address ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('استان') }}</p>
                                <p class="mt-1">{{ $lead->state ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('شهر') }}</p>
                                <p class="mt-1">{{ $lead->city ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div id="additional-info" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('اطلاعات تکمیلی') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <p class="text-sm text-gray-500">{{ __('نوع مشتری') }}</p>
                                <p class="mt-1">{{ $lead->customer_type ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('صنعت') }}</p>
                                <p class="mt-1">{{ $lead->industry ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('تابعیت') }}</p>
                                <p class="mt-1">{{ $lead->nationality ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('عدم ارسال ایمیل') }}</p>
                                <p class="mt-1">{{ $lead->do_not_email ? 'بله' : 'خیر' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div id="notes" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('یادداشت‌ها') }}</h3>
                        @if($lead->notes)
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-700">{{ $lead->notes }}</p>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('یادداشتی وجود ندارد') }}</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید یادداشت‌های خود را در اینجا اضافه کنید.') }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Files Section -->
                    <div id="documents" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('فایل‌ها') }}</h3>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('فایلی وجود ندارد') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید فایل‌های خود را در اینجا آپلود کنید.') }}</p>
                        </div>
                    </div>

                    <!-- Activities Section -->
                    <div id="activities" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('فعالیت‌ها') }}</h3>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('فعالیتی وجود ندارد') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید فعالیت‌های خود را در اینجا ثبت کنید.') }}</p>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div id="products" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('محصولات') }}</h3>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('محصولی وجود ندارد') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید محصولات مرتبط را در اینجا ثبت کنید.') }}</p>
                        </div>
                    </div>

                    <!-- Campaigns Section -->
                    <div id="campaigns" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('کمپین‌های تبلیغاتی') }}</h3>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('کمپین تبلیغاتی وجود ندارد') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید کمپین‌های تبلیغاتی مرتبط را در اینجا ثبت کنید.') }}</p>
                        </div>
                    </div>

                    <!-- Services Section -->
                    <div id="services" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('سرویس‌ها') }}</h3>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('سرویسی وجود ندارد') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید سرویس‌های مرتبط را در اینجا ثبت کنید.') }}</p>
                        </div>
                    </div>

                    <!-- Approvals Section -->
                    <div id="approvals" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('تاییدیه‌ها') }}</h3>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('تاییدیه‌ای وجود ندارد') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید تاییدیه‌های خود را در اینجا ثبت کنید.') }}</p>
                        </div>
                    </div>

                    <!-- Emails Section -->
                    <div id="emails" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('ایمیل‌ها') }}</h3>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('ایمیلی وجود ندارد') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید ایمیل‌های مرتبط را در اینجا ثبت کنید.') }}</p>
                        </div>
                    </div>

                    <!-- Calls Section -->
                    <div id="calls" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('تماس‌های تلفنی') }}</h3>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('تماس تلفنی وجود ندارد') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('شما می‌توانید تماس‌های تلفنی مرتبط را در اینجا ثبت کنید.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
    // Add smooth scrolling to anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>
@endpush 