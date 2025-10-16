@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'سازمان‌ها', 'url' => route('sales.organizations.index')],
        ['title' => $organization->name ?? ('#' . $organization->id)],
    ];
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" dir="rtl">
    <h2 class="text-2xl font-bold mb-6 text-neutral-900">مشاهده سازمان</h2>

    <div class="bg-neutral-100 shadow rounded-2xl p-6">
        {{-- مشخصات سازمان --}}
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6">
            <div class="space-y-1">
                <dt class="text-sm text-neutral-600">نام</dt>
                <dd class="font-medium text-neutral-900">{{ $organization->name ?: '—' }}</dd>
            </div>

            <div class="space-y-1">
                <dt class="text-sm text-neutral-600">ایمیل</dt>
                <dd class="text-neutral-900">{{ $organization->email ?: '—' }}</dd>
            </div>

            <div class="space-y-1">
                <dt class="text-sm text-neutral-600">شماره تماس</dt>
                <dd class="text-neutral-900">{{ $organization->phone ?: '—' }}</dd>
            </div>

            <div class="space-y-1">
                <dt class="text-sm text-neutral-600">آدرس</dt>
                <dd class="text-neutral-900">{{ $organization->address ?: '—' }}</dd>
            </div>

            <div class="space-y-1">
                <dt class="text-sm text-neutral-600">وب‌سایت</dt>
                <dd>
                    @if($organization->website)
                        <a href="{{ $organization->website }}" class="text-primary-dark hover:underline" target="_blank">
                            {{ $organization->website }}
                        </a>
                    @else
                        <span class="text-neutral-600">—</span>
                    @endif
                </dd>
            </div>

            <div class="space-y-1">
                <dt class="text-sm text-neutral-600">صنعت</dt>
                <dd class="text-neutral-900">{{ $organization->industry ?: '—' }}</dd>
            </div>

            <div class="space-y-1">
                <dt class="text-sm text-neutral-600">استان</dt>
                <dd class="text-neutral-900">{{ $organization->state ?: '—' }}</dd>
            </div>

            <div class="space-y-1">
                <dt class="text-sm text-neutral-600">شهر</dt>
                <dd class="text-neutral-900">{{ $organization->city ?: '—' }}</dd>
            </div>

            {{-- یادداشت --}}
            <div class="sm:col-span-2 lg:col-span-3 space-y-1">
                <dt class="text-sm text-neutral-600">یادداشت</dt>
                <dd class="text-neutral-900 leading-relaxed">{{ $organization->notes ?: '—' }}</dd>
            </div>
        </dl>

        {{-- مخاطبین مرتبط --}}
        @if($organization->contacts->count())
            <div class="mt-8 pt-6 border-t border-neutral-300">
                <h3 class="text-lg font-semibold mb-4 text-neutral-900">مخاطبین مرتبط</h3>
                <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($organization->contacts as $contact)
                        <li class="rounded-xl border border-neutral-300 bg-white hover:bg-primary-light transition p-4">
                            <a href="{{ route('sales.contacts.show', $contact->id) }}" 
                               class="block font-medium text-primary-dark hover:underline">
                                {{ $contact->first_name }} {{ $contact->last_name }}
                            </a>
                            <div class="text-sm text-neutral-600 mt-1">
                                {{ $contact->mobile ?: $contact->phone ?: '—' }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- دکمه‌ها --}}
        <div class="mt-8 flex items-center gap-3">
            <a href="{{ route('sales.organizations.edit', $organization->id) }}"
               class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-neutral-900 font-medium hover:bg-primary-dark">
                ویرایش
            </a>
            <a href="{{ route('sales.organizations.index') }}"
               class="inline-flex items-center rounded-lg bg-secondary px-4 py-2 text-neutral-900 font-medium hover:bg-secondary-dark">
                بازگشت
            </a>
        </div>
    </div>
</div>
@endsection
