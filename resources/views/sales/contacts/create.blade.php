@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight mb-6">
            {{ __('ایجاد مخاطب جدید') }}
        </h2>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form method="POST" action="{{ route('sales.contacts.store') }}" class="space-y-4">
                    @include('sales.contacts._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
