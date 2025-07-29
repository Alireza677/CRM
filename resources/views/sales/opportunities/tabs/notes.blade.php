<div class="space-y-6">

    <!-- فرم افزودن یادداشت -->
        <div class="bg-white p-4 shadow rounded-md">
            <h3 class="text-md font-semibold text-gray-700 mb-2">افزودن یادداشت</h3>

            <form method="POST" action="{{ route('sales.opportunities.notes.store', $opportunity->id) }}" id="noteForm">
                @csrf

                <textarea name="content" rows="3" class="w-full border rounded p-2 text-sm"
                        placeholder="یادداشتی ارسال کنید و برای اطلاع رسانی به کاربران افزودن کاربر را بزنید">{{ old('content') }}</textarea>

                {{-- نمای گرافیکی کاربران منشن‌شده --}}
                <div id="selectedMentions" class="flex flex-wrap mt-2 gap-1"></div>

                {{-- دکمه افزودن کاربران --}}
                <div class="flex justify-between items-center mt-2">
                    <button type="button" id="openMentionBtn" class="text-sm text-blue-600 hover:underline">
                        + افزودن کاربران
                    </button>

                    <button type="submit"
                            class="bg-blue-600 text-white text-sm px-4 py-1.5 rounded hover:bg-blue-700 transition">
                        ذخیره یادداشت
                    </button>
                </div>
            </form>
        </div>

        
   <!-- لیست یادداشت‌ها -->
    @foreach($opportunity->notes()->latest()->get() as $note)
        @php
            preg_match_all('/@([^\s@]+)/u', $note->body, $matches);
            $mentionedUsernames = array_unique($matches[1] ?? []);
            $mentionedUsers = \App\Models\User::whereIn('username', $mentionedUsernames)->get()->keyBy('username');

            // تولید نسخه‌ای از متن یادداشت با نام فارسی کاربران
            $displayBody = $note->body;
            foreach ($mentionedUsers as $username => $user) {
                $displayBody = str_replace("@$username", '@' . $user->name, $displayBody);
            }
        @endphp

        <div class="bg-gradient-to-b from-white to-gray-50 p-4 shadow rounded-md">
            <div class="flex items-center justify-between mb-2 text-sm text-gray-500">
                <div>نویسنده: {{ $note->user->name ?? 'کاربر' }}</div>
                <span>{{ jdate($note->created_at, 'relative') }}</span>
            </div>

            <div class="text-sm text-gray-800 mb-2 whitespace-pre-wrap">
                {!! nl2br(e($displayBody)) !!}
            </div>

            <!-- @if($mentionedUsers->isNotEmpty())
                <div class="mt-2 text-xs text-gray-600">
                    <strong>کاربران منشن‌شده:</strong>
                    @foreach($mentionedUsernames as $username)
                        @if($mentionedUsers->has($username))
                            <span class="inline-block bg-gray-100 text-gray-800 px-2 py-0.5 rounded mr-1">
                                {{ $mentionedUsers[$username]->name }}
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif -->
        </div>
    @endforeach
</div>


<!-- Modal انتخاب کاربران -->
<div id="mentionModal" class="fixed inset-0 bg-transparent bg-opacity-30 z-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded shadow w-96 max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">انتخاب کاربران</h3>
        <div class="space-y-2">
        @foreach($allUsers as $user)
            @if($user->username)
                <label class="flex items-center space-x-2">
                    <input type="checkbox" class="mention-checkbox" value="{{ $user->username }}"
                        data-name="{{ $user->name }}" data-id="{{ $user->id }}">
                    <span>{{ $user->name }} <span class="text-gray-400 text-xs">(@user{{ $user->id }})</span></span>
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
