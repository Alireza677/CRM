<x-app-layout>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-4">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">منو</h2>
                <nav>
                    <a href="{{ route('sales.opportunities.index') }}" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded">
                        فرصت‌های فروش
                    </a>
                    <a href="{{ route('sales.opportunities.create') }}" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded">
                        فرصت جدید
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-8">
                <!-- Header with Title and Actions -->
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $opportunity->name }}</h1>
                    <div class="flex space-x-4">
                        <a href="{{ route('sales.opportunities.edit', $opportunity) }}" 
                           class="text-blue-600 hover:text-blue-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <form action="{{ route('sales.opportunities.destroy', $opportunity) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="text-red-600 hover:text-red-800"
                                    onclick="return confirm('آیا از حذف این فرصت فروش اطمینان دارید؟')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Opportunity Details -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Main Information -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">اطلاعات اصلی</h2>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">عنوان</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">سازمان</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->organization->name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">مخاطب</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->contact->name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">نوع</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->type ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">منبع</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->source ?? '-' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Additional Information -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">اطلاعات تکمیلی</h2>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">ارجاع به</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->assignedTo->name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">درصد موفقیت</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $opportunity->success_rate ?? '-' }}%</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">مبلغ</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ number_format($opportunity->amount) ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">تاریخ پیگیری بعدی</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        @if($opportunity->next_follow_up)
                                            {{ \Carbon\Carbon::parse($opportunity->next_follow_up)->format('Y/m/d') }}
                                        @else
                                            -
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-6">
                        <h2 class="text-lg font-semibold mb-4">توضیحات</h2>
                        <p class="text-sm text-gray-900">{{ $opportunity->description ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 