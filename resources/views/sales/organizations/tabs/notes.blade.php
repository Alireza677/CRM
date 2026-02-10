@php
    $organization = $organization ?? $model ?? null;
@endphp

@if(!$organization)
    <div class="text-sm text-gray-500">یادداشت‌ها در دسترس نیست.</div>
@else
<div class="space-y-6">
    <div class="bg-white p-4 shadow rounded-md">
        <h3 class="text-md font-semibold text-gray-700 mb-2">افزودن یادداشت</h3>

        <form id="noteForm" method="POST" action="{{ route('sales.organizations.notes.store', $organization) }}">
            @csrf

            <div class="relative">
                <textarea name="body" rows="3" class="w-full border rounded p-2 text-sm" placeholder="یادداشت را بنویسید...">{{ old('body') }}</textarea>
                <div id="mentionDropdown"
                     class="absolute left-0 top-full mt-1 z-30 hidden min-w-[200px] max-w-[320px] rounded-xl border border-gray-200 bg-white shadow-lg">
                    <ul id="mentionList" class="max-h-44 overflow-y-auto py-1"></ul>
                </div>
            </div>

            <div class="mt-4">
                <label class="text-sm text-gray-600 block mb-1">منشن‌شده‌ها:</label>
                <div id="selectedMentions" class="flex flex-wrap gap-2"></div>
            </div>

            <div class="flex justify-end mt-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    ذخیره یادداشت
                </button>
            </div>
        </form>
    </div>

    @php
        $notes = $organization->notes()->with('user')->latest()->get();
    @endphp

    @if($notes->count())
        @foreach($notes as $note)
            <div class="bg-gradient-to-b from-white to-gray-50 p-4 shadow rounded-md">
                <div class="flex items-center justify-between mb-2 text-sm text-gray-500">
                    <div>
                        نویسنده: {{ $note->user->name ?? 'کاربر' }}
                    </div>
                    <span>{{ jdate($note->created_at, 'relative') }}</span>
                </div>

                <div class="text-sm text-gray-800 mb-2 whitespace-pre-wrap">
                    {!! nl2br(e($note->display_body ?? $note->body)) !!}
                </div>
            </div>
        @endforeach
    @else
        <div class="text-sm text-gray-500">هنوز یادداشتی ثبت نشده است.</div>
    @endif
</div>

@php
    $mentionCandidates = ($allUsers ?? collect())
        ->filter(fn ($u) => !empty($u->username))
        ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'username' => $u->username])
        ->values();
@endphp
<div id="mentionData" class="hidden" data-mention-candidates='@json($mentionCandidates)'></div>

@include('notes.mentions-scripts')
@endif
