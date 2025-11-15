<div class="bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">اسناد فرصت فروش</h3>

        <a href="{{ route('sales.documents.create', ['opportunity_id' => $opportunity->id]) }}"
           class="bg-blue-600 text-white px-4 py-2 text-sm rounded hover:bg-blue-700 transition">
            ثبت سند جدید
        </a>
    </div>

    @if ($opportunity->documents && $opportunity->documents->count())
        <div class="overflow-x-auto rounded border border-gray-200">
            <table class="min-w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-2">عنوان</th>
                        <th class="px-4 py-2">نوع سند</th>
                        <th class="px-4 py-2">تاریخ ثبت</th>
                        <th class="px-4 py-2">مشاهده</th>
                        <th class="px-4 py-2">دانلود</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($opportunity->documents->sortByDesc('created_at') as $document)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $document->title ?? '---' }}</td>
                            <td class="px-4 py-2">{{ $document->type ?? '---' }}</td>
                            <td class="px-4 py-2">{{ jdate($document->created_at)->format('Y/m/d') }}</td>
                            <td class="px-4 py-2">
                            <a href="{{ route('sales.documents.view', $document) }}" target="_blank" class="text-blue-600 hover:underline">
                                مشاهده سند
                            </a>

                            </td>
                            <td class="px-4 py-2">
                                <a href="{{ route('sales.documents.download', $document->id) }}"
                                   class="text-green-600 hover:underline text-xs">
                                    دانلود
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 text-sm py-6">
            هیچ رکوردی پیدا نشد <br>
            یک رکورد جدید ایجاد کنید یا جستجوی خود را تغییر دهید
        </div>
    @endif
</div>
