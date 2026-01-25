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
    <div class="h-full bg-gray-200 rounded-2xl shadow flex overflow-hidden min-h-0">


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
                        <div class="max-h-36 overflow-y-auto rounded-lg border border-gray-200 bg-white p-2 space-y-2">
                            @foreach($users as $user)
                                <label class="flex items-center gap-2 text-[11px] text-gray-700">
                                    <input type="checkbox"
                                           name="members[]"
                                           value="{{ $user->id }}"
                                           class="rounded border-gray-300">
                                    <span class="truncate">{{ $user->name }} ({{ $user->email }})</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1">سازنده به‌صورت خودکار مالک گروه می‌شود.</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs"
                                @click="open = false">
                            انصراف
                        </button>
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
                       data-group-id="{{ $group->id }}"
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
                            <div class="flex items-center gap-2 shrink-0">
                                <span data-unread-badge
                                      class="text-[10px] px-2 py-0.5 rounded-full bg-blue-600 text-white {{ ($group->unread_count ?? 0) > 0 ? '' : 'hidden' }}">
                                    {{ $group->unread_count ?? 0 }}
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $badge }}">
                                    {{ $group->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </div>
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
                 class="flex-1 overflow-y-auto px-3 md:px-4 py-4 space-y-3 bg-[#cddbb7] bg-[url('/images/chat-bg.svg')] bg-repeat bg-[length:220px_220px]">
                        </div>
                        <div id="imageLightbox"
                             class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4"
                             role="dialog"
                             aria-modal="true">
                            <div class="relative max-w-4xl w-full">
                                <button id="closeLightbox"
                                        type="button"
                                        class="absolute -top-10 left-0 text-white text-2xl leading-none"
                                        title="بستن">
                                    &times;
                                </button>
                                <img id="lightboxImage"
                                     src=""
                                     alt="نمایش تصویر"
                                     class="w-full max-h-[80vh] object-contain rounded-lg bg-white">
                                <div class="mt-3 flex items-center justify-between text-white text-sm">
                                    <div id="lightboxTitle" class="truncate"></div>
                                    <a id="lightboxDownload"
                                       href="#"
                                       download
                                       class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-1.5 hover:bg-white/20">
                                        دانلود
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- باکس ارسال پیام (پایین مثل واتس‌اپ) --}}
                        <div class="border-t border-gray-200 bg-white p-3">
                            @if($activeGroup->is_active)
                                <form id="messageForm"
                                      data-send-url="{{ route('chat.groups.messages.store', ['group' => $activeGroup]) }}"
                                      class="space-y-2"
                                      enctype="multipart/form-data">
                                    @csrf
                                    <input id="messageImage"
                                           name="images[]"
                                           type="file"
                                           accept="image/*"
                                           multiple
                                           class="hidden">
                                    <input id="messageFiles"
                                           name="files[]"
                                           type="file"
                                           accept=".pdf,.zip,.rar,application/pdf,application/zip,application/x-rar-compressed"
                                           multiple
                                           class="hidden">
                                    <div id="imagePreviewContainer"
                                         class="hidden rounded-lg border border-gray-200 bg-gray-50 p-3">
                                        <div class="flex items-center justify-between">
                                            <div class="text-xs font-semibold text-gray-700">ارسال عکس</div>
                                            <button id="clearImagePreview"
                                                    type="button"
                                                    class="text-gray-500 hover:text-gray-700 text-lg leading-none"
                                                    title="حذف تصویر">
                                                &times;
                                            </button>
                                        </div>
                                        <div class="mt-3 flex items-start gap-3">
                                            <div id="imagePreviewList" class="grid grid-cols-3 gap-2"></div>
                                            <div class="flex-1 space-y-2">
                                                <label for="messageImageTitle"
                                                       class="block text-[11px] font-medium text-gray-700">عنوان</label>
                                                <input id="messageImageTitle"
                                                       name="image_title"
                                                       type="text"
                                               class="input text-sm"
                                               placeholder="عنوانی اضافه کنید...">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <div class="text-[11px] font-medium text-gray-700 mb-1">فایل‌ها</div>
                                            <div id="filePreviewList" class="space-y-2"></div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 items-end">
                                        <textarea id="messageBody"
                                                  class="flex-1 input text-sm resize-none"
                                                  rows="1"
                                                  placeholder="پیام خود را بنویسید..."></textarea>
                                        <div class="relative">
                                            <button id="attachMenuButton"
                                                    type="button"
                                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg"
                                                    title="پیوست">
                                                <img src="{{ asset('images/pin.svg') }}" alt="" class="w-4 h-4">
                                            </button>
                                            <div id="attachMenu"
                                                 class="absolute left-0 bottom-12 hidden min-w-[160px] rounded-xl border border-gray-200 bg-white shadow-lg">
                                                <button id="attachImageOption"
                                                        type="button"
                                                        class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-t-xl">
                                                    <span>عکس  </span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M4 3.5A1.5 1.5 0 0 1 5.5 2h9A1.5 1.5 0 0 1 16 3.5v13a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 4 16.5v-13Zm1.5 0a.5.5 0 0 0-.5.5V13l2.5-2.5a1 1 0 0 1 1.4 0l2.1 2.1 1.6-1.6a1 1 0 0 1 1.4 0l1.5 1.5V4a.5.5 0 0 0-.5-.5h-9Zm3.25 2.75a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Z" />
                                                    </svg>
                                                </button>
                                                <button id="attachFileOption"
                                                        type="button"
                                                        class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-b-xl">
                                                    <span>فایل</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M4.75 2A1.75 1.75 0 0 0 3 3.75v12.5C3 17.216 3.784 18 4.75 18h10.5A1.75 1.75 0 0 0 17 16.25V6.414A1.75 1.75 0 0 0 16.487 5.2l-3.687-3.687A1.75 1.75 0 0 0 11.586 1H4.75Zm7.5 1.5v3.75c0 .414.336.75.75.75h3.75v8.25a.75.75 0 0 1-.75.75H4.75a.75.75 0 0 1-.75-.75V3.75a.75.75 0 0 1 .75-.75h7.5Z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="submit"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs md:text-sm">
                                            ارسال
                                        </button>
                                    </div>
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
                                            <div class="flex items-center gap-2">
                                                <div class="text-xs font-semibold text-gray-800">
                                                    {{ $member->user->name }}
                                                </div>
                                                <img src="{{ asset('images/call.svg') }}" alt="" class="w-4 h-4">
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                                                    {{ $member->role === \App\Models\OnlineChatMembership::ROLE_OWNER ? 'مدیر گروه' : ($member->role === \App\Models\OnlineChatMembership::ROLE_ADMIN ? 'مدیر' : 'عضو') }}
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
                                            <label class="block text-[11px] font-medium text-gray-700 mb-1">افزودن کاربر</label>
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
                                    <form id="groupSettingsForm" method="POST"
                                          action="{{ route('chat.groups.update', ['group' => $activeGroup]) }}"
                                          class="space-y-2 pt-2 border-t border-gray-100">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 mb-1">عنوان گروه</label>
                                            <input type="text" name="title" class="input text-xs"
                                                   value="{{ $activeGroup->title }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 mb-1">توضیحات</label>
                                            <textarea name="description" class="input text-xs" rows="2">{{ $activeGroup->description }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 mb-1">لینک تماس گروه</label>
                                            <input type="text" name="call_link" class="input text-xs"
                                                   value="{{ $activeGroup->call_link }}" placeholder="https://">
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


<style>
.chat-bubble {
    position: relative;
}
.chat-bubble.is-own::after {
    content: "";
    position: absolute;
    right: -10px;
    top: 12px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 0 8px 10px;
    border-color: transparent transparent transparent #ffffff;
}
.chat-bubble.is-other::after {
    content: "";
    position: absolute;
    left: -10px;
    top: 12px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 10px 8px 0;
    border-color: transparent #ecfdf3 transparent transparent;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const groupId = {{ $activeGroup?->id ?? 'null' }};
    if (!groupId) return;
    const currentUserId = {{ auth()->id() ?? 'null' }};

    const fetchUrl = @json($activeGroup ? route('chat.groups.messages', ['group' => $activeGroup]) : '');
    const sendUrl  = @json($activeGroup ? route('chat.groups.messages.store', ['group' => $activeGroup]) : '');
    const unreadCountsUrl = @json(route('chat.groups.unread-counts'));
    const startCallUrl = @json($activeGroup ? route('chat.groups.start-call', ['group' => $activeGroup]) : '');
    let groupCallLink = @json($activeGroup->call_link ?? '');
    const activeGroupTitle = @json($activeGroup->title ?? '');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const messageList = document.getElementById('messageList');
    const messageForm = document.getElementById('messageForm');
    const messageBody = document.getElementById('messageBody');
    const messageImageInput = document.getElementById('messageImage');
    const messageFilesInput = document.getElementById('messageFiles');
    const messageImageTitle = document.getElementById('messageImageTitle');
    const attachMenuButton = document.getElementById('attachMenuButton');
    const attachMenu = document.getElementById('attachMenu');
    const attachImageOption = document.getElementById('attachImageOption');
    const attachFileOption = document.getElementById('attachFileOption');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreviewList = document.getElementById('imagePreviewList');
    const filePreviewList = document.getElementById('filePreviewList');
    const clearImagePreview = document.getElementById('clearImagePreview');
    const startCallButton = document.getElementById('startVideoCallButton');
    const groupSettingsForm = document.getElementById('groupSettingsForm');
    const toastContainer = document.getElementById('notification-toast-container');
    const imageLightbox = document.getElementById('imageLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const lightboxDownload = document.getElementById('lightboxDownload');
    const closeLightbox = document.getElementById('closeLightbox');

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

    function formatFileSize(bytes) {
        if (!bytes) return '';
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unit = 0;
        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit += 1;
        }
        return `${size.toFixed(size >= 10 || unit === 0 ? 0 : 1)} ${units[unit]}`;
    }

    function renderMessage(msg) {
        const isOwn = currentUserId && msg.sender?.id === currentUserId;
        const wrapper = document.createElement('div');
        wrapper.className = `flex ${isOwn ? 'justify-start' : 'justify-end'}`;

        const el = document.createElement('div');
        el.className = `chat-bubble w-1/2 max-w-[50%] rounded-2xl px-3 py-2 shadow-sm ${isOwn ? 'is-own bg-white text-right' : 'is-other bg-green-50 text-right border border-green-100'}`;
        const bodyHtml = msg.body
            ? `<div class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap mt-2">${escapeHtml(msg.body)}</div>`
            : '';
        const imageHtml = msg.image_url
            ? `<div class="mt-2">
                    <img src="${escapeHtml(msg.image_url)}"
                         alt="تصویر پیوست"
                         class="w-20 h-20 object-cover rounded-lg border border-gray-100 cursor-zoom-in"
                         data-image-url="${escapeHtml(msg.image_url)}"
                         data-image-title="${escapeHtml(msg.image_title || '')}">
                    ${msg.image_title ? `<div class="mt-1 text-xs text-gray-600">${escapeHtml(msg.image_title)}</div>` : ''}
               </div>`
            : '';
        const fileHtml = msg.file_url
            ? `<div class="mt-2">
                    <a href="${escapeHtml(msg.file_url)}" download
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-xs text-gray-700 hover:bg-gray-100">
                        <span class="font-semibold">${escapeHtml(msg.file_name || 'فایل')}</span>
                        ${msg.file_size ? `<span class="text-[10px] text-gray-500">${formatFileSize(msg.file_size)}</span>` : ''}
                    </a>
               </div>`
            : '';
        el.innerHTML = `
            <div class="flex items-center justify-between text-xs text-gray-500">
                <div class="flex items-center gap-2">
                    <div class="text-xs font-semibold ${isOwn ? 'text-blue-700' : 'text-green-700'}">
                        ${isOwn ? 'من' : escapeHtml(msg.sender?.name || 'کاربر')}
                    </div>
                </div>
                <span>${formatTime(msg.created_at)}</span>
            </div>
            ${bodyHtml}
            ${imageHtml}
            ${fileHtml}
        `;
        wrapper.appendChild(el);
        return wrapper;
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

    async function loadUnreadCounts() {
        if (!unreadCountsUrl) return;
        try {
            const response = await axios.get(unreadCountsUrl);
            const data = response.data?.data || {};
            Object.entries(data).forEach(([groupId, count]) => {
                const link = document.querySelector(`[data-group-id="${groupId}"]`);
                if (!link) return;
                const badge = link.querySelector('[data-unread-badge]');
                if (!badge) return;
                const num = Number(count) || 0;
                badge.textContent = num;
                badge.classList.toggle('hidden', num === 0);
            });
        } catch (error) {
            console.error('خطا در دریافت تعداد پیام‌های خوانده نشده', error);
        }
    }

    async function sendMessage(body, imageFiles, files, imageTitle) {
        try {
            const formData = new FormData();
            if (body) {
                formData.append('body', body);
            }
            if (imageFiles && imageFiles.length) {
                imageFiles.forEach((file) => {
                    formData.append('images[]', file);
                });
            }
            if (imageFiles && imageFiles.length && imageTitle) {
                formData.append('image_title', imageTitle);
            }
            if (files && files.length) {
                files.forEach((file) => {
                    formData.append('files[]', file);
                });
            }
            const response = await axios.post(sendUrl, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const payload = response.data?.data;
            const messages = Array.isArray(payload) ? payload : (payload ? [payload] : []);
            if (messages.length) {
                const last = messages[messages.length - 1];
                lastId = last.id;
                if (messageList.firstElementChild && messageList.firstElementChild.dataset.empty) {
                    messageList.innerHTML = '';
                }
                messages.forEach(msg => messageList.appendChild(renderMessage(msg)));
                messageList.scrollTop = messageList.scrollHeight;
            }
            return messages;
        } catch (error) {
            console.error('خطا در ارسال پیام', error);
            return [];
        }
    }

    async function startVideoCall() {
        const directLink = (groupCallLink || '').trim();
        if (directLink) {
            window.open(directLink, '_blank', 'noopener');
            showCallToast({
                title: 'تماس گروه',
                description: activeGroupTitle ? `گروه: ${activeGroupTitle}` : '',
                url: directLink,
            });
            return;
        }
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
    loadUnreadCounts();
    poller = setInterval(() => {
        loadMessages({ reset: false });
        loadUnreadCounts();
    }, 4000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            loadMessages({ reset: false });
            loadUnreadCounts();
        }
    });

    messageForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = (messageBody.value || '').trim();
        const imageTitle = (messageImageTitle?.value || '').trim();
        if (!body && !selectedImages.length && !selectedFiles.length) return;
        const sent = await sendMessage(
            body,
            selectedImages.map(item => item.file),
            selectedFiles,
            imageTitle
        );
        if (!sent.length) return;
        messageBody.value = '';
        autoResizeMessageBody();
        resetImagePreview();
    });

    groupSettingsForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData(groupSettingsForm);
            const response = await axios.post(groupSettingsForm.action, formData, {
                headers: {
                    'Accept': 'application/json',
                },
            });
            const callLink = response.data?.data?.call_link;
            if (typeof callLink === 'string') {
                groupCallLink = callLink;
            }
        } catch (error) {
            groupSettingsForm.submit();
        }
    });

    attachMenuButton?.addEventListener('click', (event) => {
        event.stopPropagation();
        if (!attachMenu) return;
        attachMenu.classList.toggle('hidden');
    });

    attachImageOption?.addEventListener('click', () => {
        attachMenu?.classList.add('hidden');
        messageImageInput?.click();
    });

    attachFileOption?.addEventListener('click', () => {
        attachMenu?.classList.add('hidden');
        messageFilesInput?.click();
    });

    let selectedImages = [];
    let selectedFiles = [];

    function resetImagePreview() {
        selectedImages.forEach((item) => {
            URL.revokeObjectURL(item.url);
        });
        selectedImages = [];
        if (imagePreviewList) {
            imagePreviewList.innerHTML = '';
        }
        selectedFiles = [];
        if (filePreviewList) {
            filePreviewList.innerHTML = '';
        }
        if (imagePreviewContainer) {
            imagePreviewContainer.classList.add('hidden');
        }
        if (messageImageInput) {
            messageImageInput.value = '';
        }
        if (messageFilesInput) {
            messageFilesInput.value = '';
        }
        if (messageImageTitle) {
            messageImageTitle.value = '';
        }
    }

    messageImageInput?.addEventListener('change', () => {
        const files = Array.from(messageImageInput?.files || []);
        if (!files.length) {
            return;
        }
        files.forEach((file) => {
            selectedImages.push({
                file,
                url: URL.createObjectURL(file),
            });
        });
        if (imagePreviewList) {
            renderImagePreviews();
        }
        imagePreviewContainer?.classList.remove('hidden');
        messageImageInput.value = '';
    });

    messageFilesInput?.addEventListener('change', () => {
        const files = Array.from(messageFilesInput?.files || []);
        if (!files.length) {
            return;
        }
        files.forEach((file) => {
            selectedFiles.push(file);
        });
        renderFilePreviews();
        imagePreviewContainer?.classList.remove('hidden');
        messageFilesInput.value = '';
    });

    clearImagePreview?.addEventListener('click', () => {
        resetImagePreview();
    });

    function renderImagePreviews() {
        if (!imagePreviewList) return;
        imagePreviewList.innerHTML = '';
        selectedImages.forEach((item, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative';
            const img = document.createElement('img');
            img.src = item.url;
            img.alt = 'پیش نمایش تصویر';
            img.className = 'w-20 h-20 object-cover rounded-lg border border-gray-100 bg-white';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.className = 'absolute -top-2 -right-2 h-6 w-6 rounded-full bg-gray-800 text-white text-sm leading-none';
            remove.addEventListener('click', () => {
                URL.revokeObjectURL(item.url);
                selectedImages.splice(index, 1);
                renderImagePreviews();
                if (!selectedImages.length) {
                    resetImagePreview();
                }
            });

            wrapper.appendChild(img);
            wrapper.appendChild(remove);
            imagePreviewList.appendChild(wrapper);
        });
    }

    function renderFilePreviews() {
        if (!filePreviewList) return;
        filePreviewList.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between rounded-lg border border-gray-100 bg-white px-2 py-1 text-xs text-gray-700';

            const name = document.createElement('span');
            name.textContent = file.name;
            name.className = 'truncate max-w-[180px]';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.className = 'ml-2 h-5 w-5 rounded-full bg-gray-800 text-white text-xs leading-none';
            remove.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                renderFilePreviews();
                if (!selectedFiles.length && !selectedImages.length) {
                    resetImagePreview();
                }
            });

            row.appendChild(name);
            row.appendChild(remove);
            filePreviewList.appendChild(row);
        });
    }

    function openLightbox(url, title) {
        if (!imageLightbox || !lightboxImage || !lightboxDownload) return;
        lightboxImage.src = url;
        lightboxDownload.href = url;
        if (lightboxTitle) {
            lightboxTitle.textContent = title || '';
        }
        imageLightbox.classList.remove('hidden');
        imageLightbox.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeImageLightbox() {
        if (!imageLightbox) return;
        imageLightbox.classList.add('hidden');
        imageLightbox.classList.remove('flex');
        if (lightboxImage) {
            lightboxImage.src = '';
        }
        if (lightboxTitle) {
            lightboxTitle.textContent = '';
        }
        if (lightboxDownload) {
            lightboxDownload.href = '#';
        }
        document.body.classList.remove('overflow-hidden');
    }

    messageList?.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLImageElement)) return;
        const url = target.dataset.imageUrl;
        if (!url) return;
        openLightbox(url, target.dataset.imageTitle || '');
    });

    imageLightbox?.addEventListener('click', (event) => {
        if (event.target === imageLightbox) {
            closeImageLightbox();
        }
    });

    closeLightbox?.addEventListener('click', () => {
        closeImageLightbox();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeImageLightbox();
        }
    });

    document.addEventListener('click', (event) => {
        if (!attachMenu || attachMenu.classList.contains('hidden')) return;
        if (attachMenu.contains(event.target) || attachMenuButton?.contains(event.target)) return;
        attachMenu.classList.add('hidden');
    });

    function autoResizeMessageBody() {
        if (!messageBody) return;
        messageBody.style.height = 'auto';
        messageBody.style.height = `${messageBody.scrollHeight}px`;
    }

    messageBody?.addEventListener('input', autoResizeMessageBody);
    autoResizeMessageBody();

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
