@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ویرایش فرصت']
    ];
@endphp

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">ویرایش فرصت</h2>

        <form method="POST" action="{{ route('sales.opportunities.update', $opportunity) }}">
            @csrf
            @method('PUT')

            @include('sales.opportunities._form')

            <div class="mt-6 flex gap-2">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    بروزرسانی
                </button>
                <a href="{{ route('sales.opportunities.index') }}"
                   class="px-6 py-2 rounded border border-gray-300 hover:bg-gray-50">انصراف</a>
            </div>
        </form>
    </div>
</div>

@include('sales.opportunities._modals')
@include('sales.opportunities._scripts')
@endsection
