@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'مدیریت کاربران']
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">مدیریت کاربران</h2>
                        <a href="{{ route('settings.users.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            افزودن کاربر جدید
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-50 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">نام</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-50 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">ایمیل</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-50 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">تاریخ ثبت نام</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-50 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">{{ $user->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">{{ $user->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">{{ $user->created_at->format('Y/m/d') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200 text-left text-sm font-medium">
                                            <a href="{{ route('settings.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 ml-4">ویرایش</a>
                                            @if($user->id !== auth()->id())
                                            <!-- <button type="button"
                                            class="text-red-600 hover:text-red-900"
                                            data-user-id="{{ $user->id }}"
                                            onclick="openReassignModal(this)">حذف
                                            </button> -->

                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
<div class="modal fade" id="reassignUserModal" tabindex="-1" aria-labelledby="reassignUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('settings.users.reassign') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id_to_delete" id="user_id_to_delete">

            <div class="modal-content text-end">
                <div class="modal-header">
                    <h5 class="modal-title" id="reassignUserModalLabel">تخصیص اطلاعات به کاربر دیگر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <label for="new_user_id">لطفاً کاربری را انتخاب کنید تا اطلاعات به او منتقل شود:</label>
                    <select class="form-control mt-2" name="new_user_id" id="new_user_id" required>
                        <option value="">انتخاب کاربر</option>
                        @foreach($users as $otherUser)
                            <option value="{{ $otherUser->id }}">{{ $otherUser->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">انتقال و حذف</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                </div>
            </div>
        </form>
    </div>
</div>


    @push('scripts')
<script>
    function openReassignModal(button) {
        const userId = button.getAttribute('data-user-id');
        const modal = new bootstrap.Modal(document.getElementById('reassignUserModal'));
        document.getElementById('user_id_to_delete').value = userId;
        modal.show();
    }
</script>
@endpush

@endsection 