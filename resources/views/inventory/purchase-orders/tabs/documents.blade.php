<div class="bg-white p-6 rounded shadow">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">اسناد سفارش خرید</h3>

    @if ($purchaseOrder->documents && $purchaseOrder->documents->count())
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($purchaseOrder->documents as $doc)
                @php
                    $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                    $viewUrl = route('sales.documents.view', $doc);
                @endphp
                <div class="p-3 border rounded-md flex items-center gap-3 justify-between">
                    <div class="flex items-center gap-3">
                        @if($isImage)
                            <a href="{{ $viewUrl }}" target="_blank">
                                <img src="{{ $viewUrl }}" alt="{{ $doc->title }}" class="h-20 w-20 object-cover rounded border">
                            </a>
                        @else
                            <div class="h-12 w-12 flex items-center justify-center bg-gray-100 rounded border text-xs text-gray-600">
                                {{ strtoupper($ext) ?: 'FILE' }}
                            </div>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ $doc->title }}</div>
                            <div class="text-xs text-gray-500">{{ $ext ?: 'file' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ $viewUrl }}" target="_blank" class="text-blue-600 text-sm hover:underline">مشاهده</a>
                        <a href="{{ route('sales.documents.download', $doc) }}" class="text-gray-700 text-sm hover:underline">دانلود</a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center text-gray-500 text-sm py-6">
            سندی ثبت نشده است.
        </div>
    @endif
</div>

