@php $proforma = $proforma ?? $model ?? null; @endphp

@if(!$proforma)
    <div class="text-sm text-gray-500">اسناد پیش‌فاکتور در دسترس نیست.</div>
@else
    @php ob_start(); @endphp
<div class="bg-white p-6 rounded shadow">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-700">اسناد پیش‌فاکتور</h3>
        @php
            $opportunityId = $proforma->opportunity_id ?? $proforma->opportunity?->id;
        @endphp
        @if($opportunityId)
            <a href="{{ route('sales.documents.create', ['opportunity_id' => $opportunityId]) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                افزودن سند
            </a>
        @else
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">
                افزودن سند
            </span>
        @endif
    </div>

    @if (($documents ?? collect())->count())
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($documents as $doc)
                @php
                    $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                    $viewUrl = route('sales.documents.view', $doc);
                    $registeredAt = $doc->created_at;

                    $isAdmin = auth()->check() && (
                        (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin'))
                        || (auth()->user()->is_admin ?? false)
                    );
                    $isOwner = auth()->check() && (int) ($doc->user_id ?? 0) === (int) auth()->id();
                    $canManage = $isAdmin || $isOwner;
                    $isVoided = (bool) ($doc->is_voided ?? false);

                    $confirmVoidMsg = $isVoided
                        ? 'ابطال این سند لغو شود؟'
                        : 'آیا مطمئن هستید این سند باطل شود؟ سند حذف نخواهد شد.';
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
                            <div class="text-sm font-medium text-gray-800 flex items-center gap-2">
                                <span>{{ $doc->title }}</span>
                                @if($isVoided)
                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">باطل شده</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">{{ $ext ?: 'file' }}</div>
                            @if($registeredAt)
                                <div class="text-xs text-gray-500 mt-1">ثبت: {{ jdate($registeredAt)->format('Y/m/d') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ $viewUrl }}" target="_blank" class="text-blue-600 text-sm hover:underline">مشاهده</a>
                        <a href="{{ route('sales.documents.download', $doc) }}" class="text-gray-700 text-sm hover:underline">دانلود</a>
                        @if($canManage)
                            <form action="{{ route('sales.documents.toggle-void', $doc) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm(@js($confirmVoidMsg));">
                                @csrf
                                @method('PATCH')

                                <button type="submit"
                                        class="{{ $isVoided ? 'text-amber-600 hover:text-amber-700' : 'text-gray-600 hover:text-gray-800' }}"
                                        title="{{ $isVoided ? 'رفع ابطال' : 'باطل کردن' }}">
                                    <i class="fas {{ $isVoided ? 'fa-undo-alt' : 'fa-ban' }}"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center text-gray-500 text-sm py-6">
            سندی برای این پیش‌فاکتور پیدا نشد.
        </div>
    @endif
</div>
    @php
        $__html = ob_get_clean();
        $blocks = [[
            'type' => 'html',
            'html' => $__html,
            'class' => 'md:col-span-2 lg:col-span-3 p-0 bg-transparent border-0 shadow-none rounded-none',
        ]];
    @endphp
    @include('crud.partials.cards', ['blocks' => $blocks])
@endif
