@extends('layouts.app')

@section('title', 'درخواست مرخصی')

@section('content')
  @php($breadcrumb = $breadcrumb ?? [['title' => 'پرتال کارمند','url'=>route('employee.portal.index')],['title'=>'درخواست مرخصی']])
  <div class="max-w-3xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">درخواست مرخصی</h1>

    @if ($errors->any())
      <div class="mb-4 rounded bg-red-50 text-red-700 px-3 py-2">
        لطفاً خطاهای زیر را برطرف کنید.
      </div>
    @endif

    <div class="bg-white rounded shadow p-4">
      <form action="{{ route('employee.portal.leave.submit') }}" method="POST" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">از تاریخ</label>
            <input type="hidden" id="from_date" name="from_date" value="{{ old('from_date') }}">
            <input type="text" id="from_date_shamsi" class="persian-datepicker w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500" data-alt-field="from_date" placeholder="YYYY/MM/DD" autocomplete="off">
            @error('from_date')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="block text-sm mb-1">تا تاریخ</label>
            <input type="hidden" id="to_date" name="to_date" value="{{ old('to_date') }}">
            <input type="text" id="to_date_shamsi" class="persian-datepicker w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500" data-alt-field="to_date" placeholder="YYYY/MM/DD" autocomplete="off">
            @error('to_date')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
          </div>
        </div>
        <div>
          <label class="block text-sm mb-1">دلیل مرخصی</label>
          <textarea name="reason" rows="3" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="کوتاه توضیح دهید...">{{ old('reason') }}</textarea>
          @error('reason')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="text-left">
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ثبت درخواست</button>
        </div>
      </form>
    </div>
  </div>
@endsection