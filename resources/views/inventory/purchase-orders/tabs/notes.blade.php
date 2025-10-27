<div class="space-y-6">

    <div class="bg-white p-4 shadow rounded-md">
        <h3 class="text-md font-semibold text-gray-700 mb-2">افزودن یادداشت</h3>

        <form method="POST" action="{{ route('inventory.purchase-orders.notes.store', $purchaseOrder->id) }}" id="noteForm">
            @csrf

            <textarea name="content" rows="3" class="w-full border rounded p-2 text-sm"
                    placeholder="یادداشتی ارسال کنید و برای اطلاع‌رسانی به کاربران، کاربر را اضافه کنید">{{ old('content') }}</textarea>

            <div id="selectedMentions" class="flex flex-wrap mt-2 gap-1"></div>

            <div class="flex justify-between items-center mt-2">
                <button type="button" id="openMentionBtn" class="text-sm text-blue-600 hover:underline">+ افزودن کاربران</button>
                <button type="submit" class="bg-blue-600 text-white text-sm px-4 py-1.5 rounded hover:bg-blue-700 transition">ذخیره یادداشت</button>
            </div>
        </form>
    </div>

    @foreach($purchaseOrder->notes()->latest()->get() as $note)
        @php
            preg_match_all('/@([^\s@]+)/u', $note->body, $matches);
            $mentionedUsernames = array_unique($matches[1] ?? []);
            $mentionedUsers = \App\Models\User::whereIn('username', $mentionedUsernames)->get()->keyBy('username');
            $displayBody = $note->body;
            foreach ($mentionedUsers as $username => $user) {
                $displayBody = str_replace("@$username", '@' . $user->name, $displayBody);
            }
        @endphp

        <div id="note-{{ $note->id }}" class="bg-gradient-to-b from-white to-gray-50 p-4 shadow rounded-md">
            <div class="flex items-center justify-between mb-2 text-sm text-gray-500">
                <div>نویسنده: {{ $note->user->name ?? 'کاربر' }}</div>
                <span>{{ jdate($note->created_at, 'relative') }}</span>
            </div>
            <div class="text-sm text-gray-800 mb-2 whitespace-pre-wrap">{!! nl2br(e($displayBody)) !!}</div>
        </div>
    @endforeach
</div>

<div id="mentionModal" class="fixed inset-0 bg-transparent bg-opacity-30 z-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded shadow w-96 max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">انتخاب کاربران</h3>
        <div class="space-y-2">
        @foreach(($allUsers ?? collect()) as $user)
            @if($user->username)
                <label class="flex items-center space-x-2">
                    <input type="checkbox" class="mention-checkbox" value="{{ $user->username }}" data-name="{{ $user->name }}" data-id="{{ $user->id }}">
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

