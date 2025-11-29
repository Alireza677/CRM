@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    use App\Models\OnlineChatMembership;

    $activeGroup = $groups->firstWhere('id', $activeGroupId) ?? $groups->first();
    $ownerCount  = $activeGroup ? $activeGroup->memberships->where('role', OnlineChatMembership::ROLE_OWNER)->count() : 0;
    $nonMembers  = $activeGroup
        ? $users->whereNotIn('id', $activeGroup->memberships->pluck('user_id'))->values()
        : $users;
@endphp

@section('content')
<div class="w-[95%] mx-auto pt-4 pb-0 h-[calc(100vh-110px)]" dir="rtl">
    {{-- کانتینر اصلی شبیه واتس‌اپ: تمام قد، دو ستون --}}
    <div class="h-full bg-gray-200 rounded-2xl shadow flex overflow-hidden flex-row-reverse min-h-0">


        {{-- ستون چپ: لیست گروه‌ها + ساخت گروه (مثل سایدبار واتس‌اپ) --}}
<div class="w-full md:w-64 lg:w-72 bg-white border-l border-gray-200 flex flex-col min-h-0">

            {{-- هدر سایدبار --}}
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800">گفتگوها</h2>

                <button type="button"
                        x-data
                        @click="$dispatch('toggle-create-group')"
                        class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100">
                    <span class="text-lg leading-none">+</span>
                    <span>گروه جدید</span>
                </button>
            </div>

            {{-- فرم ایجاد گروه (بالای لیست، قابل جمع شدن) --}}
            <div class="px-3 pt-2 pb-3 border-b border-gray-100"
                 x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }"
                 @toggle-create-group.window="open = !open">
                <form x-show="open" x-cloak method="POST" action="{{ route('chat.groups.store') }}" class="space-y-2">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-medium text-gray-700 mb-1">عنوان گروه</label>
                        <input type="text" name="title" class="input text-sm" required value="{{ old('title') }}">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-700 mb-1">توضیحات</label>
                        <textarea name="description" class="input text-sm" rows="2"
                                  placeholder="هدف گروه را بنویسید">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-700 mb-1">اعضای اولیه</label>
                        <select name="members[]" class="input text-xs" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-500 mt-1">سازنده به‌صورت خودکار مالک گروه می‌شود.</p>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="submit"
                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs">
                            ساخت گروه
                        </button>
                    </div>
                </form>
            </div>

            {{-- لیست گروه‌ها --}}
            <div class="flex-1 overflow-y-auto">
                @forelse($groups as $group)
                    @php
                        $isActive = $activeGroup && $group->id === $activeGroup->id;
                        $badge = $group->is_active ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50';
                    @endphp
                    <a href="{{ route('chat.index', ['group_id' => $group->id]) }}"
                       class="block px-3 py-2.5 border-b border-gray-100 hover:bg-gray-50 transition
                              {{ $isActive ? 'bg-blue-50/60' : '' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-semibold text-sm text-gray-800 truncate">
                                    {{ $group->title }}
                                </div>
                                <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                                    {{ $group->description ?: 'بدون توضیح' }}
                                </p>
                                <div class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                                    <span class="font-medium text-gray-600">آخرین پیام:</span>
                                    <span class="truncate">
                                        {{ Str::limit($group->lastMessage?->body ?? 'هنوز پیامی ارسال نشده', 40) }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full shrink-0 {{ $badge }}">
                                {{ $group->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="h-full flex items-center justify-center px-4">
                        <p class="text-xs text-gray-500 text-center">
                            هنوز در هیچ گروهی عضو نیستید.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ستون راست: چت فعال شبیه واتس‌اپ --}}
<div class="flex-1 flex flex-col min-h-0 overflow-hidden" x-data="{ showInfoModal: false }">

            @if($activeGroup)
                @php
                    $userRole = $activeGroup->memberRole(auth()->user());
                    $canManage = in_array($userRole, [OnlineChatMembership::ROLE_OWNER, OnlineChatMembership::ROLE_ADMIN], true);
                @endphp

                {{-- هدر چت (بالای صفحه مثل واتس‌اپ) --}}
                <div class="px-4 py-3 bg-white border-b border-gray-200 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg">
                            {{ mb_substr($activeGroup->title, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm md:text-base font-bold text-gray-800 truncate">
                                {{ $activeGroup->title }}
                            </h3>
                            <p class="text-[11px] text-gray-500 truncate">
                                {{ $activeGroup->description ?: 'بدون توضیح' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($activeGroup->is_active)
                            <button type="button"
                                    id="startVideoCallButton"
                                    class="flex items-center gap-1 px-3 py-1 rounded-full text-[11px] bg-blue-50 text-blue-700 hover:bg-blue-100"
                                    title="شروع تماس ویدیویی">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M4 4.75A1.75 1.75 0 0 1 5.75 3h5.5A1.75 1.75 0 0 1 13 4.75v1.53l2.2-1.65A.75.75 0 0 1 16.5 5v10a.75.75 0 0 1-1.3.47L13 13.81v1.44A1.75 1.75 0 0 1 11.25 17h-5.5A1.75 1.75 0 0 1 4 15.25v-10Z" />
                                </svg>
                                <span class="hidden sm:inline">تماس ویدیویی</span>
                            </button>
                        @endif
                        <button type="button"
                                class="px-3 py-1 rounded-full text-[11px] bg-gray-100 text-gray-700 hover:bg-gray-200"
                                @click="showInfoModal = true">
                            اطلاعات گروه
                        </button>
                        @if(! $activeGroup->is_active)
                            <span class="px-3 py-1 rounded-full text-[11px] bg-red-100 text-red-700">
                                غیرفعال
                            </span>
                        @endif
                    </div>
                </div>

                {{-- بدنه: لیست پیام‌ها + سایدبار اطلاعات گروه (فقط در دسکتاپ) --}}
<div class="flex flex-1 min-h-0 overflow-hidden">

                    {{-- ناحیه پیام‌ها --}}
<div class="flex-1 flex flex-col bg-gray-50 min-h-0">

                        {{-- لیست پیام‌ها --}}
                        <div id="messageList"
                 class="flex-1 overflow-y-auto px-3 md:px-4 py-4 space-y-3 bg-[url('/images/chat-bg.svg')] bg-cover bg-center">
                        </div>

                        {{-- باکس ارسال پیام (پایین مثل واتس‌اپ) --}}
                        <div class="border-t border-gray-200 bg-white p-3">
                            @if($activeGroup->is_active)
                                <form id="messageForm"
                                      data-send-url="{{ route('chat.groups.messages.store', ['group' => $activeGroup]) }}"
                                      class="flex gap-2 items-end">
                                    @csrf
                                    <textarea id="messageBody"
                                              class="flex-1 input text-sm resize-none"
                                              rows="1"
                                              placeholder="پیام خود را بنویسید..."></textarea>
                                    <button type="submit"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs md:text-sm">
                                        ارسال
                                    </button>
                                </form>
                            @else
                                <div class="text-xs md:text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2 text-center">
                                    این گروه غیرفعال است و امکان ارسال پیام وجود ندارد.
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                {{-- مودال اطلاعات گروه --}}
                <div x-show="showInfoModal" x-cloak
                     class="fixed inset-0 z-40 flex items-center justify-center bg-black/40"
                     x-transition>
                    <div class="bg-white w-full max-w-lg max-h-[80vh] rounded-2xl shadow-lg flex flex-col"
                         @click.outside="showInfoModal = false">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h4 class="font-semibold text-gray-800 text-sm">اطلاعات گروه</h4>
                            <button type="button" class="text-gray-500 hover:text-gray-700 text-lg leading-none"
                                    @click="showInfoModal = false">
                                &times;
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-4">

                            {{-- اعضا --}}
                            <div>
                                <h5 class="font-semibold text-gray-800 text-sm mb-2">اعضا</h5>
                                <div class="space-y-2">
                                    @foreach($activeGroup->memberships as $member)
                                        <div class="flex items-center justify-between gap-2 rounded-lg border border-gray-100 px-2 py-2">
                                            <div>
                                                <div class="text-xs font-semibold text-gray-800">
                                                    {{ $member->user->name }}
                                                </div>
                                                <div class="text-[10px] text-gray-500">
                                                    {{ $member->user->email }}
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                                                    {{ $member->role }}
                                                </span>
                                                @if($canManage && $member->user_id !== auth()->id() && !($member->role === OnlineChatMembership::ROLE_OWNER && $ownerCount <= 1))
                                                    <form method="POST"
                                                          action="{{ route('chat.groups.members.destroy', ['group' => $activeGroup, 'user' => $member->user_id]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-[10px] text-red-600 hover:text-red-800">
                                                            حذف
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- مدیریت اعضا و تنظیمات (فقط اگر دسترسی داشته باشد) --}}
                            @if($canManage)
                                <div class="space-y-3 pt-3 border-t border-gray-100">
                                    <h5 class="font-semibold text-gray-800 text-sm">مدیریت گروه</h5>

                                    {{-- افزودن عضو --}}
                                    <form method="POST"
                                          action="{{ route('chat.groups.members.store', ['group' => $activeGroup]) }}"
                                          class="space-y-2">
                                        @csrf
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 mb-1">کاربر</label>
                                            <select name="user_id" class="input text-xs" required>
                                                @foreach($nonMembers as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 mb-1">نقش</label>
                                            <select name="role" class="input text-xs">
                                                <option value="{{ OnlineChatMembership::ROLE_MEMBER }}">member</option>
                                                <option value="{{ OnlineChatMembership::ROLE_ADMIN }}">admin</option>
                                            </select>
                                        </div>
                                        <div class="flex justify-end">
                                            <button class="px-3 py-1.5 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-xs">
                                                افزودن
                                            </button>
                                        </div>
                                    </form>

                                    {{-- تنظیمات گروه --}}
                                    <form method="POST"
                                          action="{{ route('chat.groups.update', ['group' => $activeGroup]) }}"
                                          class="space-y-2 pt-2 border-t border-gray-100">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 mb-1">عنوان</label>
                                            <input type="text" name="title" class="input text-xs"
                                                   value="{{ $activeGroup->title }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 mb-1">توضیحات</label>
                                            <textarea name="description" class="input text-xs" rows="2">{{ $activeGroup->description }}</textarea>
                                        </div>
                                        <label class="inline-flex items-center gap-2 text-[11px] text-gray-700">
                                            <input type="checkbox" name="is_active" value="1"
                                                   class="rounded border-gray-300" {{ $activeGroup->is_active ? 'checked' : '' }}>
                                            فعال باشد
                                        </label>
                                        <div class="flex justify-end">
                                            <button class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs">
                                                ذخیره تغییرات
                                            </button>
                                        </div>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('chat.groups.destroy', ['group' => $activeGroup]) }}"
                                          class="pt-2 border-t border-gray-100"
                                          onsubmit="return confirm('گروه و پیام‌های آن حذف شود؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="w-full px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg text-xs">
                                            حذف گروه
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                {{-- اگر گروهی انتخاب نشده --}}
                <div class="flex-1 flex items-center justify-center bg-gray-50">
                    <div class="text-center px-6">
                        <p class="text-gray-700 text-sm md:text-lg">
                            از سایدبار سمت چپ یک گروه انتخاب کنید یا گروه جدید بسازید.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const groupId = {{ $activeGroup?->id ?? 'null' }};
    if (!groupId) return;

    const fetchUrl = @json($activeGroup ? route('chat.groups.messages', ['group' => $activeGroup]) : '');
    const sendUrl  = @json($activeGroup ? route('chat.groups.messages.store', ['group' => $activeGroup]) : '');
    const startCallUrl = @json($activeGroup ? route('chat.groups.start-call', ['group' => $activeGroup]) : '');
    const activeGroupTitle = @json($activeGroup->title ?? '');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const messageList = document.getElementById('messageList');
    const messageForm = document.getElementById('messageForm');
    const messageBody = document.getElementById('messageBody');
    const startCallButton = document.getElementById('startVideoCallButton');
    const toastContainer = document.getElementById('notification-toast-container');

    const echoConfig = {
        key: @json(config('broadcasting.connections.pusher.key')),
        cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
        host: @json(config('broadcasting.connections.pusher.options.host')),
        port: @json(config('broadcasting.connections.pusher.options.port')),
        scheme: @json(config('broadcasting.connections.pusher.options.scheme')),
        forceTLS: @json(config('broadcasting.connections.pusher.options.useTLS')),
        authEndpoint: @json(url('/broadcasting/auth')),
        enabled: {{ config('broadcasting.default') ? 'true' : 'false' }},
    };

    let lastId = null;
    let poller = null;

    function loadScriptOnce(src) {
        return new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                if (existing.dataset.loaded === '1') {
                    resolve();
                } else {
                    existing.addEventListener('load', () => resolve(), { once: true });
                    existing.addEventListener('error', () => reject(new Error(`Failed to load ${src}`)), { once: true });
                }
                return;
            }
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => {
                script.dataset.loaded = '1';
                resolve();
            };
            script.onerror = () => reject(new Error(`Failed to load ${src}`));
            document.head.appendChild(script);
        });
    }

    function escapeHtml(str) {
        return (str || '').replace(/[&<>"']/g, function(m) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[m];
        });
    }

    function formatTime(iso) {
        if (!iso) return '';
        try {
            const d = new Date(iso);
            return new Intl.DateTimeFormat('fa-IR', { hour: '2-digit', minute: '2-digit' }).format(d);
        } catch (e) {
            return '';
        }
    }

    function renderMessage(msg) {
        const el = document.createElement('div');
        el.className = 'bg-white rounded-lg shadow-sm p-3';
        el.innerHTML = `
            <div class="flex items-center justify-between text-xs text-gray-500">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                        ${escapeHtml((msg.sender?.name || '?').charAt(0))}
                    </div>
                    <div class="text-gray-800 font-semibold">${escapeHtml(msg.sender?.name || 'کاربر')}</div>
                </div>
                <span>${formatTime(msg.created_at)}</span>
            </div>
            <div class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap mt-2">${escapeHtml(msg.body)}</div>
        `;
        return el;
    }

    function showEmptyState() {
        if (messageList.childElementCount === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-center text-sm text-gray-500 py-4';
            empty.textContent = 'پیامی ثبت نشده است.';
            empty.dataset.empty = '1';
            messageList.appendChild(empty);
        }
    }

    function showCallToast({ title, description = '', url }) {
        if (!toastContainer || !url) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'pointer-events-auto max-w-sm w-80 bg-white border border-blue-200 shadow-lg rounded-xl p-3 flex gap-3 items-start';

        const icon = document.createElement('div');
        icon.className = 'inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-700';
        icon.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M4 4.75A1.75 1.75 0 0 1 5.75 3h5.5A1.75 1.75 0 0 1 13 4.75v1.53l2.2-1.65A.75.75 0 0 1 16.5 5v10a.75.75 0 0 1-1.3.47L13 13.81v1.44A1.75 1.75 0 0 1 11.25 17h-5.5A1.75 1.75 0 0 1 4 15.25v-10Z" />
            </svg>
        `;

        const body = document.createElement('div');
        body.className = 'flex-1 space-y-1';

        const titleEl = document.createElement('div');
        titleEl.className = 'text-sm font-semibold text-gray-900';
        titleEl.textContent = title || 'تماس گروهی';

        const descEl = document.createElement('div');
        descEl.className = 'text-[11px] text-gray-600';
        descEl.textContent = description;

        const actions = document.createElement('div');
        actions.className = 'flex items-center justify-end gap-2 pt-1';

        const joinBtn = document.createElement('button');
        joinBtn.type = 'button';
        joinBtn.textContent = 'پیوستن به تماس';
        joinBtn.className = 'px-3 py-1 rounded-lg bg-blue-600 text-white text-xs hover:bg-blue-700';
        joinBtn.addEventListener('click', () => {
            window.open(url, '_blank', 'noopener');
        });

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.innerHTML = '&times;';
        closeBtn.className = 'text-gray-400 hover:text-gray-600 text-lg leading-none';
        closeBtn.addEventListener('click', () => wrapper.remove());

        actions.appendChild(joinBtn);
        actions.appendChild(closeBtn);

        body.appendChild(titleEl);
        if (description) {
            body.appendChild(descEl);
        }
        body.appendChild(actions);

        wrapper.appendChild(icon);
        wrapper.appendChild(body);

        toastContainer.appendChild(wrapper);

        setTimeout(() => wrapper.remove(), 10000);
    }

    async function loadMessages(opts = { reset: false }) {
        if (!fetchUrl) return;
        try {
            let url = fetchUrl;
            if (lastId && !opts.reset) {
                url += (url.includes('?') ? '&' : '?') + 'after_id=' + lastId;
            }
            const response = await axios.get(url);
            const data = response.data?.data || [];

            if (opts.reset) {
                messageList.innerHTML = '';
            }

            if (data.length) {
                if (messageList.firstElementChild && messageList.firstElementChild.dataset.empty) {
                    messageList.innerHTML = '';
                }
                data.forEach(msg => {
                    lastId = msg.id;
                    messageList.appendChild(renderMessage(msg));
                });
                messageList.scrollTop = messageList.scrollHeight;
            } else if (opts.reset) {
                showEmptyState();
            }
        } catch (error) {
            console.error('خطا در دریافت پیام‌ها', error);
        }
    }

    async function sendMessage(body) {
        try {
            const response = await axios.post(sendUrl, { body });
            const msg = response.data?.data;
            if (msg) {
                lastId = msg.id;
                if (messageList.firstElementChild && messageList.firstElementChild.dataset.empty) {
                    messageList.innerHTML = '';
                }
                messageList.appendChild(renderMessage(msg));
                messageList.scrollTop = messageList.scrollHeight;
            }
        } catch (error) {
            console.error('خطا در ارسال پیام', error);
        }
    }

    async function startVideoCall() {
        if (!startCallUrl) return;
        if (startCallButton) {
            startCallButton.disabled = true;
            startCallButton.classList.add('opacity-70');
        }
        try {
            const response = await fetch(startCallUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload?.message || 'خطا در شروع تماس');
            }
            const url = payload?.data?.url;
            if (url) {
                window.open(url, '_blank', 'noopener');
                showCallToast({
                    title: 'تماس گروهی شروع شد',
                    description: activeGroupTitle ? `گروه: ${activeGroupTitle}` : '',
                    url,
                });
            }
        } catch (error) {
            console.error('خطا در شروع تماس ویدیویی', error);
            alert('راه‌اندازی تماس با خطا مواجه شد. لطفاً دوباره امتحان کنید.');
        } finally {
            if (startCallButton) {
                startCallButton.disabled = false;
                startCallButton.classList.remove('opacity-70');
            }
        }
    }

    async function setupEcho() {
        if (!echoConfig.enabled || !echoConfig.key) {
            return null;
        }

        const forceTLS = typeof echoConfig.forceTLS === 'boolean'
            ? echoConfig.forceTLS
            : (echoConfig.scheme ? echoConfig.scheme === 'https' : window.location.protocol === 'https:');

        if (typeof window.Pusher === 'undefined') {
            await loadScriptOnce('https://js.pusher.com/8.2/pusher.min.js');
        }

        const echoAlreadyLoaded = typeof window.Echo === 'function' || (window.Echo && typeof window.Echo.connector !== 'undefined');
        if (!echoAlreadyLoaded) {
            await loadScriptOnce('https://cdn.jsdelivr.net/npm/laravel-echo@1/dist/echo.iife.js');
        }

        const EchoConstructor = typeof window.Echo === 'function'
            ? window.Echo
            : (window.EchoConstructor || window.LaravelEcho || window.Echo);

        if (typeof EchoConstructor !== 'function') {
            console.warn('Laravel Echo library not available');
            return null;
        }

        const instance = new EchoConstructor({
            broadcaster: 'pusher',
            key: echoConfig.key,
            cluster: echoConfig.cluster || undefined,
            wsHost: echoConfig.host || window.location.hostname,
            wsPort: echoConfig.port || (forceTLS ? 443 : 6001),
            wssPort: echoConfig.port || 443,
            forceTLS,
            encrypted: forceTLS,
            disableStats: true,
            authEndpoint: echoConfig.authEndpoint || '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            },
        });

        window.EchoConstructor = EchoConstructor;
        window.chatEcho = instance;
        window.Echo = instance;

        return instance;
    }

    // Initial load + polling
    loadMessages({ reset: true });
    poller = setInterval(() => loadMessages({ reset: false }), 4000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            loadMessages({ reset: false });
        }
    });

    messageForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = (messageBody.value || '').trim();
        if (!body) return;
        messageBody.value = '';
        await sendMessage(body);
    });

    startCallButton?.addEventListener('click', (e) => {
        e.preventDefault();
        startVideoCall();
    });

    setupEcho()
        .then((echo) => {
            if (!echo) return;
            echo.private(`chat.groups.${groupId}`)
                .listen('.chat.call.started', (event) => {
                    if (!event?.jitsi_url) return;
                    const starter = event.started_by?.name ? `شروع‌کننده: ${event.started_by.name}` : '';
                    showCallToast({
                        title: event.group_title ? `تماس جدید در ${event.group_title}` : 'تماس گروهی جدید',
                        description: starter,
                        url: event.jitsi_url,
                    });
                });
        })
        .catch((err) => {
            console.warn('راه‌اندازی شنونده تماس ناموفق بود', err);
        });

    window.addEventListener('beforeunload', () => clearInterval(poller));
});
</script>
@endpush
