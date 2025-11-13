@extends('layouts.app')

@section('title', 'قرارداد من')

@section('content')
  @php($breadcrumb = $breadcrumb ?? [['title' => 'پرتال کارمند','url'=>route('employee.portal.index')],['title'=>'قرارداد من']])
  <div class="max-w-3xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">قرارداد من</h1>

    <div class="bg-white rounded shadow p-4">
      <div class="text-gray-600">در این بخش جزئیات قرارداد شما نمایش داده می‌شود.</div>
      <div class="mt-4 text-sm text-gray-500">(اتصال به داده‌های واقعی در مرحله بعد انجام می‌شود.)</div>
    </div>
  </div>
@endsection