<div class="space-y-6">

    <!-- فرم افزودن یادداشت -->
        <div class="bg-white p-4 shadow rounded-md">
            <h3 class="text-md font-semibold text-gray-700 mb-2">افزودن یادداشت</h3>

            <form method="POST" action="{{ route('sales.opportunities.notes.store', $opportunity->id) }}" id="noteForm">
                @csrf

            <div class="relative">
                <textarea name="content" rows="3" class="w-full border rounded p-2 text-sm"
                        placeholder="یادداشتی ارسال کنید و برای اطلاع رسانی به کاربران افزودن کاربر را بزنید">{{ old('content') }}</textarea>
                <div id="mentionDropdown"
                     class="absolute left-0 top-full mt-1 z-30 hidden min-w-[200px] max-w-[320px] rounded-xl border border-gray-200 bg-white shadow-lg">
                    <ul id="mentionList" class="max-h-44 overflow-y-auto py-1"></ul>
                </div>
            </div>

                {{-- نمای گرافیکی کاربران منشن‌شده --}}
                <div id="selectedMentions" class="flex flex-wrap mt-2 gap-1"></div>

                <div class="flex justify-end items-center mt-2">
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


@php
    $mentionCandidates = ($allUsers ?? collect())
        ->filter(fn ($u) => !empty($u->username))
        ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'username' => $u->username])
        ->values();
@endphp
<div id="mentionData" class="hidden" data-mention-candidates='@json($mentionCandidates)'></div>
