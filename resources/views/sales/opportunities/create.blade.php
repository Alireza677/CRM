@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ایجاد فرصت جدید']
    ];
@endphp

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">فرصت جدید</h2>

        <form method="POST" action="{{ route('sales.opportunities.store') }}">
            @csrf

            @include('sales.opportunities._form')

            <div class="mt-6">
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    ذخیره
                </button>
            </div>
        </form>
    </div>
</div>

@include('sales.opportunities._modals')
@include('sales.opportunities._scripts')
@endsection
