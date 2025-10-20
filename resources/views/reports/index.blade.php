@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'داشبورد گزارش‌ها', 'url' => route('reports.dashboard')],
        ['title' => 'همهٔ گزارش‌ها'],
    ];
@endphp

@section('content')
    <div class="py-6" dir="rtl">
        @include('components.toast')
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">گزارش‌ها</h1>
            <a href="{{ route('reports.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ایجاد گزارش</a>
        </div>

        <form method="get" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <input type="text" name="q" value="{{ $search }}" placeholder="جستجوی عنوان..."
                       class="w-full border rounded p-2" />
            </div>
            <div>
                <select name="visibility" class="w-full border rounded p-2">
                    <option value="">همهٔ محدوده‌ها</option>
                    <option value="private" @selected($visibility==='private')>خصوصی</option>
                    <option value="public" @selected($visibility==='public')>عمومی</option>
                    <option value="shared" @selected($visibility==='shared')>اشتراکی</option>
                </select>
            </div>
            <div>
                <button class="w-full md:w-auto px-4 py-2 bg-gray-700 text-white rounded">اعمال فیلتر</button>
            </div>
        </form>

        <div class="bg-white shadow rounded overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-right">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2"><a href="?{{ http_build_query(array_merge(request()->all(), ['sort'=>'title','dir'=> request('dir')==='asc'?'desc':'asc'])) }}">عنوان</a></th>
                    <th class="px-4 py-2">محدوده نمایش</th>
                    <th class="px-4 py-2"><a href="?{{ http_build_query(array_merge(request()->all(), ['sort'=>'created_by','dir'=> request('dir')==='asc'?'desc':'asc'])) }}">ایجادکننده</a></th>
                    <th class="px-4 py-2"><a href="?{{ http_build_query(array_merge(request()->all(), ['sort'=>'created_at','dir'=> request('dir')==='asc'?'desc':'asc'])) }}">تاریخ ایجاد</a></th>
                    <th class="px-4 py-2">عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($reports as $report)
                    <tr class="hover:bg-blue-50">
                        <td class="px-4 py-2 font-medium">
                            <a class="text-blue-700 hover:underline" href="{{ route('reports.show',$report) }}">{{ $report->title }}</a>
                            @if(!$report->is_active)
                                <span class="ml-2 text-xs text-red-600">غیرفعال</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @php
                                $label = $report->visibility === 'public' ? 'عمومی' : ($report->visibility==='shared' ? 'اشتراکی' : 'خصوصی');
                                $color = $report->visibility === 'public' ? 'bg-green-100 text-green-800' : ($report->visibility==='shared' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800');
                            @endphp
                            <span class="px-2 py-1 text-xs rounded {{ $color }}">{{ $label }}</span>
                        </td>
                        <td class="px-4 py-2">{{ $report->creator->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ jdate($report->created_at)->format('Y/m/d H:i') }}</td>
                        <td class="px-4 py-2 space-x-2 space-x-reverse">
                            <a href="{{ route('reports.edit',$report) }}" class="text-blue-600 hover:underline">ویرایش</a>
                            <form action="{{ route('reports.destroy',$report) }}" method="post" class="inline" onsubmit="return confirm('حذف شود؟');">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">گزارشی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $reports->links() }}</div>
    </div>
@endsection
