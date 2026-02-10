@php $lead = $lead ?? $model ?? null; @endphp

@if(!$lead)
    <div class="text-sm text-gray-500">یادداشت‌ها در دسترس نیست.</div>
@else
<div class="space-y-6">
    <!-- فرم افزودن یادداشت -->
    <div class="bg-white p-4 shadow rounded-md">
        <h3 class="text-md font-semibold text-gray-700 mb-2">افزودن یادداشت</h3>

        <form id="noteForm" method="POST" action="{{ route('marketing.leads.notes.store', $lead) }}">
            @csrf

            <!-- متن یادداشت -->
            <div class="relative">
                <textarea name="body" rows="3" class="w-full border rounded p-2 text-sm" placeholder="یادداشت را بنویسید...">{{ old('body') }}</textarea>
                <div id="mentionDropdown"
                     class="absolute left-0 top-full mt-1 z-30 hidden min-w-[200px] max-w-[320px] rounded-xl border border-gray-200 bg-white shadow-lg">
                    <ul id="mentionList" class="max-h-44 overflow-y-auto py-1"></ul>
                </div>
            </div>

            <!-- نمایش منشن‌های انتخاب‌شده -->
            <div class="mt-4">
                <label class="text-sm text-gray-600 block mb-1">منشن‌شده‌ها:</label>
                <div id="selectedMentions" class="flex flex-wrap gap-2"></div>
            </div>


            <!-- دکمه ذخیره -->
            <div class="flex justify-end mt-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    ذخیره یادداشت
                </button>
            </div>
        </form>
    </div>

   <!-- لیست یادداشت‌ها -->
    @if(!empty($lead->notes))
        <div class="bg-gradient-to-b from-white to-gray-50 p-4 shadow rounded-md border border-dashed border-blue-200">
            <div class="flex items-center justify-between mb-2 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded-full">یادداشت اولیه</span>
                    <span class="text-gray-600">ثبت‌شده همراه سرنخ</span>
                </div>
                <span>{{ jdate($lead->created_at, 'relative') }}</span>
            </div>

            <div class="text-sm text-gray-800 mb-2 whitespace-pre-wrap">
                {!! nl2br(e($lead->notes)) !!}
            </div>
        </div>
    @endif

   @foreach($lead->notes()->latest()->get() as $note)
        @php
            // استخراج usernameها از متن یادداشت
            preg_match_all('/@([^\s@]+)/u', $note->body, $matches);
            $mentionedUsernames = array_unique($matches[1] ?? []);

            // گرفتن کاربران با username
            $mentionedUsers = \App\Models\User::whereIn('username', $mentionedUsernames)->get()->keyBy('username');

            // جایگزینی @username با @نام‌فارسی
            $displayBody = $note->body;
            foreach ($mentionedUsers as $username => $user) {
                $displayBody = str_replace("@$username", '@' . $user->name, $displayBody);
            }
        @endphp

        <div class="bg-gradient-to-b from-white to-gray-50 p-4 shadow rounded-md">
            <div class="flex items-center justify-between mb-2 text-sm text-gray-500">
                <div>
                    نویسنده: {{ $note->user->name ?? 'کاربر' }}
                </div>
                <span>{{ jdate($note->created_at, 'relative') }}</span>
            </div>

            <div class="text-sm text-gray-800 mb-2 whitespace-pre-wrap">
                {!! nl2br(e($displayBody)) !!}
            </div>

        
        </div>
    @endforeach



</div>

@php
    $mentionCandidates = ($allUsers ?? collect())
        ->filter(fn ($u) => !empty($u->username))
        ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'username' => $u->username])
        ->values();
@endphp
<div id="mentionData" class="hidden" data-mention-candidates='@json($mentionCandidates)'></div>
@endif


