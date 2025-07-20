@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" dir="rtl">
    <div class="bg-white shadow rounded-lg p-4">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">اعلان‌ها</h2>

        @if (session('success'))
            <div class="mb-4 text-sm text-green-600 bg-green-100 border border-green-300 p-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('notifications.bulkAction') }}">
            @csrf

            <div class="flex items-center gap-3 mb-4">
                <button type="submit" name="action" value="markAsRead"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-1.5 rounded">
                    ✔️ علامت‌گذاری به عنوان خوانده‌شده
                </button>
                <button type="submit" name="action" value="delete"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm px-3 py-1.5 rounded">
                    🗑️ حذف اعلان‌های انتخاب‌شده
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border rounded-lg">
                    <thead class="bg-gray-100 text-gray-700 text-sm">
                        <tr>
                            <th class="px-3 py-2 text-right"><input type="checkbox" id="select-all"></th>
                            <th class="px-3 py-2 text-right">پیام</th>
                            <th class="px-3 py-2 text-right">ارجاع‌دهنده</th>
                            <th class="px-3 py-2 text-right">زمان</th>
                            <th class="px-3 py-2 text-right">وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            <tr class="{{ $notification->read_at ? 'bg-white text-gray-400' : 'bg-yellow-50 text-gray-800 font-semibold' }} border-b">
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" name="selected[]" value="{{ $notification->id }}">
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('notifications.read', $notification->id) }}" class="hover:underline">
                                        {{ $notification->data['message'] ?? 'اعلان جدیدی دارید' }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-right">{{ $notification->data['assigned_by'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ $notification->created_at->diffForHumans() }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $notification->read_at ? 'bg-gray-200 text-gray-500' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $notification->read_at ? 'خوانده‌شده' : 'خوانده‌نشده' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-gray-500">هیچ اعلانی وجود ندارد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all');
        selectAll?.addEventListener('click', function () {
            document.querySelectorAll('input[name="selected[]"]').forEach(cb => cb.checked = this.checked);
        });
    });
</script>
@endsection
