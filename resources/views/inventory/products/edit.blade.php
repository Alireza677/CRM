@extends('layouts.app')

@section('content')
<div class="py-12" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">ویرایش محصول: {{ $product->name }}</h2>
        </div>

        @include('inventory.products._form', [
            'product'    => $product,        // حالت ویرایش
            'categories' => $categories,
            'suppliers'  => $suppliers,
        ])
    </div>
</div>
@endsection
