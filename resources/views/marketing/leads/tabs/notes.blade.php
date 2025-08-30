<div class="space-y-6">
    <!-- فرم افزودن یادداشت -->
    <div class="bg-white p-4 shadow rounded-md">
        <h3 class="text-md font-semibold text-gray-700 mb-2">افزودن یادداشت</h3>

        <form id="noteForm" method="POST" action="{{ route('marketing.leads.notes.store', $lead) }}">
            @csrf

            <!-- متن یادداشت -->
            <textarea name="body" rows="3" class="w-full border rounded p-2 text-sm" placeholder="یادداشت را بنویسید...">{{ old('body') }}</textarea>

            <!-- دکمه باز کردن مودال -->
            <div class="mt-4">
                <label class="text-sm text-gray-600 block mb-1">منشن کردن کاربران:</label>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="openMentionBtn" class="bg-gray-200 text-gray-800 px-3 py-1 rounded hover:bg-gray-300">
                        انتخاب کاربران
                    </button>
                    <div id="selectedMentions" class="flex flex-wrap gap-2"></div>
                </div>
            </div>

            <!-- فیلد مخفی برای ارسال -->
            <input type="hidden" name="mentions[]" id="mentionInput">


            <!-- دکمه ذخیره -->
            <div class="flex justify-end mt-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    ذخیره یادداشت
                </button>
            </div>
        </form>
    </div>

   <!-- لیست یادداشت‌ها -->
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

<!-- Modal -->
<div id="mentionModal" class="fixed inset-0 bg-transparent bg-opacity-30 z-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded shadow w-96 max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">انتخاب کاربران</h3>
        <div class="space-y-2">
            @foreach($allUsers as $user)
                @if($user->username)
                    <label class="flex items-center space-x-2">
                    <input type="checkbox" class="mention-checkbox" value="{{ $user->username }}" data-name="{{ $user->name }}">
                    <span>{{ $user->name }} <span class="text-gray-400 text-xs">(@{{ $user->username }})</span></span>
                    </label>
                @endif
            @endforeach
        </div>
        <div class="flex justify-end space-x-2 mt-4">
            <button type="button" id="cancelMentionBtn" class="text-gray-600 hover:underline">لغو</button>
            <button type="button" id="applyMentionBtn" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">تأیید</button>
        </div>
    </div>
</div>


