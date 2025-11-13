@extends('layouts.app')

@section('title', 'وضعیت بیمه')

@section('content')
  @php($breadcrumb = $breadcrumb ?? [['title' => 'پرتال کارمند','url'=>route('employee.portal.index')],['title'=>'وضعیت بیمه']])
  <div class="max-w-4xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">وضعیت بیمه</h1>

    <div class="bg-white rounded shadow p-4">
      <div class="text-gray-600">اطلاعات بیمه شما در این بخش نمایش داده می‌شود.</div>
    </div>
  </div>
@endsection