@extends('layouts.app')

@section('title', 'پرتال کارمند')

@section('content')
  @php($breadcrumb = $breadcrumb ?? [['title' => 'پرتال کارمند']])
  <div class="max-w-5xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">پرتال کارمند</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <a href="{{ route('employee.portal.contract') }}" class="block p-4 bg-white rounded shadow hover:shadow-md transition">
        <div class="font-medium mb-1">قرارداد من</div>
        <div class="text-sm text-gray-500">مشاهده جزئیات قرارداد و شرایط.</div>
      </a>

      <a href="{{ route('employee.portal.leave.request') }}" class="block p-4 bg-white rounded shadow hover:shadow-md transition">
        <div class="font-medium mb-1">درخواست مرخصی</div>
        <div class="text-sm text-gray-500">ثبت درخواست مرخصی جدید.</div>
      </a>

      <a href="{{ route('employee.portal.leaves') }}" class="block p-4 bg-white rounded shadow hover:shadow-md transition">
        <div class="font-medium mb-1">لیست مرخصی‌ها</div>
        <div class="text-sm text-gray-500">مرخصی‌های گذشته و در حال انتظار.</div>
      </a>

      <a href="{{ route('employee.portal.payslips') }}" class="block p-4 bg-white rounded shadow hover:shadow-md transition">
        <div class="font-medium mb-1">فیش‌های حقوقی</div>
        <div class="text-sm text-gray-500">مشاهده و دانلود فیش‌ها.</div>
      </a>

      <a href="{{ route('employee.portal.insurance') }}" class="block p-4 bg-white rounded shadow hover:shadow-md transition">
        <div class="font-medium mb-1">وضعیت بیمه</div>
        <div class="text-sm text-gray-500">اطلاعات بیمه و سابقه.</div>
      </a>
    </div>
  </div>
@endsection