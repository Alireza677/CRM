@extends('layouts.app')

@section('content')
    <div class="p-6 bg-white rounded shadow max-w-xl mx-auto">

        {{-- عنوان صفحه --}}
        <h1 class="text-xl font-bold mb-6 text-gray-700">ثبت سند جدید</h1>

        <form method="POST" action="{{ route('sales.documents.store') }}" enctype="multipart/form-data">
    @csrf
    <div>
        <label>عنوان</label>
        <input type="text" name="title" value="{{ old('title') }}" required>
        @error('title') <small class="text-red-500">{{ $message }}</small> @enderror
    </div>

    <div>
        <label>فایل</label>
        <input type="file" name="file" required>
        @error('file') <small class="text-red-500">{{ $message }}</small> @enderror
    </div>

    <div>
        <label>فرصت فروش (اختیاری)</label>
        <select name="opportunity_id">
            <option value="">— انتخاب کنید —</option>
            @foreach($opportunities as $opp)
                <option value="{{ $opp->id }}" @selected(old('opportunity_id')==$opp->id)>{{ $opp->title }}</option>
            @endforeach
        </select>
        @error('opportunity_id') <small class="text-red-500">{{ $message }}</small> @enderror
    </div>

    <button type="submit">ثبت</button>
</form>

    </div>
@endsection
