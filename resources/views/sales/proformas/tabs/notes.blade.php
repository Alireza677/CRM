@php $proforma = $proforma ?? $model ?? null; @endphp

@if(!$proforma)
    <div class="text-sm text-gray-500">یادداشت‌ها در دسترس نیست.</div>
@else
    @php ob_start(); @endphp
<div class="space-y-6">

    <div class="bg-white p-4 shadow rounded-md">
        <h3 class="text-md font-semibold text-gray-700 mb-2">یادداشت‌ها</h3>

        <form method="POST" action="{{ route('sales.proformas.notes.store', $proforma->id) }}" id="noteForm">
            @csrf

            <div class="relative">
                <textarea name="content" rows="3" class="w-full border rounded p-2 text-sm"
                        placeholder="یادداشت خود را بنویسید و در صورت نیاز با @ همکاران را منشن کنید">{{ old('content') }}</textarea>
                <div id="mentionDropdown"
                     class="absolute left-0 top-full mt-1 z-30 hidden min-w-[200px] max-w-[320px] rounded-xl border border-gray-200 bg-white shadow-lg">
                    <ul id="mentionList" class="max-h-44 overflow-y-auto py-1"></ul>
                </div>
            </div>

            <div id="selectedMentions" class="flex flex-wrap mt-2 gap-1"></div>

            <div class="flex justify-end items-center mt-2">
                <button type="submit" class="bg-blue-600 text-white text-sm px-4 py-1.5 rounded hover:bg-blue-700 transition">ثبت یادداشت</button>
            </div>
        </form>
    </div>

    @foreach($proforma->notes()->latest()->get() as $note)
        @php
            preg_match_all('/@([^\\s@]+)/u', $note->body, $matches);
            $mentionedUsernames = array_unique($matches[1] ?? []);
            $mentionedUsers = \App\Models\User::whereIn('username', $mentionedUsernames)->get()->keyBy('username');
            $displayBody = $note->body;
            foreach ($mentionedUsers as $username => $user) {
                $displayBody = str_replace("@$username", '@' . $user->name, $displayBody);
            }
        @endphp

        <div id="note-{{ $note->id }}" class="bg-gradient-to-b from-white to-gray-50 p-4 shadow rounded-md">
            <div class="flex items-center justify-between mb-2 text-sm text-gray-500">
                <div>ثبت‌کننده: {{ $note->user->name ?? 'نامشخص' }}</div>
                <span>{{ jdate($note->created_at, 'relative') }}</span>
            </div>
            <div class="text-sm text-gray-800 mb-2 whitespace-pre-wrap">{!! nl2br(e($displayBody)) !!}</div>
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
