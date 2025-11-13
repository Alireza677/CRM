@extends('layouts.app')

@section('title', 'لیست مرخصی‌ها')

@section('content')
  @php($breadcrumb = $breadcrumb ?? [['title' => 'پرتال کارمند','url'=>route('employee.portal.index')],['title'=>'لیست مرخصی‌ها']])
  <div class="max-w-4xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">لیست مرخصی‌ها</h1>

    @if (session('success'))
      <div class="mb-4 rounded bg-green-50 text-green-700 px-3 py-2">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded shadow p-4">
      <div class="text-gray-600">فعلاً داده‌ای برای نمایش وجود ندارد.</div>
      <div class="mt-2 text-sm text-gray-500">(در گام بعد به پایگاه داده متصل می‌شود.)</div>
    </div>
  </div>
@endsection