<div class="space-y-6">
    <!-- فرم افزودن یادداشت -->
    <div class="bg-white p-4 shadow rounded-md">
        <h3 class="text-md font-semibold text-gray-700 mb-2">افزودن یادداشت</h3>

        <!-- فرم افزودن یادداشت -->
            <form method="POST" action="{{ route('marketing.leads.notes.store', $lead->id) }}">
                @csrf

                <!-- متن یادداشت -->
                <textarea name="body" rows="3" class="w-full border rounded p-2 text-sm" placeholder="یادداشت را بنویسید...">
                    {{ old('body') }}
                </textarea>

                <!-- لیست کشویی ساده کاربران -->
                <div class="mt-4">
                    <label for="mention" class="text-sm text-gray-600">منشن کردن کاربر:</label>
                    <select name="mentions[]" multiple class="w-full border rounded p-2 text-sm">
                        @foreach($allUsers as $user)
                            @if($user->username)
                                <option value="{{ $user->username }}">{{ $user->name }}</option>
                            @endif
                        @endforeach
                    </select>

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
    @foreach($lead->notes()->latest()->get() as $note)
        <div class="bg-gradient-to-b from-white to-gray-50 p-4 shadow rounded-md">
            <div class="flex items-center justify-between mb-2 text-sm text-gray-500">
                <span>عمومی</span>
                <span>{{ jdate($note->created_at, 'relative') }}</span>
            </div>

            <div class="text-sm text-gray-800 mb-2 whitespace-pre-wrap">
                {!! nl2br(e($note->body)) !!}
            </div>

            {{-- استخراج منشن‌ها از متن --}}
            @php
                preg_match_all('/@([\w\d\-_]+)/u', $note->body, $matches);
                $mentionedUsernames = $matches[1] ?? [];
            @endphp

            @if(!empty($mentionedUsernames))
                <div class="text-xs text-gray-600 mt-2">
                    منشن‌شده‌ها:
                    @foreach($mentionedUsernames as $username)
                        <span class="text-blue-700 font-medium">@{{ $username }}</span>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between text-xs text-gray-500 border-t pt-2 mt-2">
                <div>
                    نویسنده: {{ $note->user->name ?? 'کاربر' }}
                </div>
            </div>
        </div>
    @endforeach
</div>


