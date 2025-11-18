@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'تنظیمات', 'url' => route('settings.index')],
            ['title' => 'تنظیمات اعلان‌ها'],
        ];
    @endphp

    <div class="py-12" x-data="notificationsMatrix()" x-init="init()" dir="rtl">
      <div class="max-w-none w-full px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-2xl font-semibold text-right"> تنظیمات اعلان‌ها</h1>
                        <a href="{{ route('settings.index') }}" class="text-blue-600 hover:underline">بازگشت به تنظیمات</a>
                    </div>

                    <div class="flex items-center justify-between mb-3 gap-4">
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" x-model="toggleAll" @change="applyToggleAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>فعال‌سازی/غیرفعال‌سازی همه</span>
                            </label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="q" placeholder="جستجو در رویدادها..." class="w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button @click="clearSearch()" class="px-3 py-1.5 bg-gray-100 rounded border">پاک‌سازی</button>
                        </div>
                    </div>

                    @if($purchaseOrderSection)
                        <div class="mb-8 border rounded-lg overflow-hidden" x-data="{ showCreate: false, sectionOpen: true }" @po-rules-cancel.window="showCreate=false">
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 cursor-pointer" @click="sectionOpen = !sectionOpen; if(!sectionOpen){ showCreate = false }">
                                <div class="flex items-center gap-3">
                                    <svg x-bind:class="{ 'rotate-180': sectionOpen }" class="w-4 h-4 text-gray-600 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="text-lg font-semibold text-gray-800">{{ $purchaseOrderSection['module_label'] ?? 'سفارش‌های خرید' }}</div>
                                    <div class="text-sm text-gray-600">{{ $purchaseOrderSection['event_label'] ?? 'تغییر وضعیت' }}</div>
                                </div>
                                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" @click.stop="showCreate = !showCreate">+ اعلان جدید</button>
                            </div>
                            <div class="divide-y divide-gray-200" x-show="sectionOpen" x-transition x-cloak>
                                <div x-show="showCreate" x-cloak class="bg-blue-50/60">
                                    @php
                                        $poCreateRow = $purchaseOrderSection['defaults'] ?? [];
                                        $poCreateRow['form_action'] = route('settings.notifications.store');
                                    @endphp
                                    @include('settings.notifications.partials.purchase-order-rule-row', [
                                        'row' => $poCreateRow,
                                        'section' => $purchaseOrderSection,
                                        'poStatuses' => $poStatuses ?? [],
                                        'channelOptions' => $channelOptions,
                                    ])
                                </div>
                                @forelse($purchaseOrderSection['rules'] as $poRule)
                                    @php
                                        $poRow = $poRule;
                                        $poRow['form_action'] = route('settings.notifications.update', $poRule['id']);
                                        $poRow['delete_action'] = route('settings.notifications.destroy', $poRule['id']);
                                    @endphp
                                    @include('settings.notifications.partials.purchase-order-rule-row', [
                                        'row' => $poRow,
                                        'section' => $purchaseOrderSection,
                                        'poStatuses' => $poStatuses ?? [],
                                        'channelOptions' => $channelOptions,
                                    ])
                                @empty
                                    <div class="p-4 text-sm text-gray-500">هیچ قانون فعالی برای این بخش ثبت نشده است.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr class="text-right">
                                    <th class="px-4 py-3 text-xs font-medium text-gray-500">بخش</th>
                                    <th class="px-4 py-3 text-xs font-medium text-gray-500">رویداد</th>
                                    <th class="px-4 py-3 text-xs font-medium text-gray-500">فعال</th>
                                    <th class="px-4 py-3 text-xs font-medium text-gray-500">کانال‌ها</th>
                                    <th class="px-4 py-3 text-xs font-medium text-gray-500">شروط</th>
                                    <th class="px-4 py-3 text-xs font-medium text-gray-500">متن اعلان</th>
                                    <th class="px-4 py-3 text-xs font-medium text-gray-500">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php $currentModule = null; @endphp
                                @foreach($matrix as $row)
                                    @if($currentModule !== $row['module'])
                                        @php $currentModule = $row['module']; @endphp
                                        <tr class="bg-gray-100">
                                            <td colspan="7" class="px-4 py-2 font-semibold text-right">{{ $row['module_label'] }}</td>
                                        </tr>
                                    @endif
                                    @php
                                        $isPOStatus = $row['module'] === 'purchase_orders' && $row['event'] === 'status.changed';
                                        $rowId = $row['id'] ?? null;
                                        $formAction = $rowId
                                            ? route('settings.notifications.update', $rowId)
                                            : route('settings.notifications.store');
                                    @endphp
                                    <tr x-show="matchesFilter('{{ $row['module_label'] }} {{ $row['event_label'] }}')" @keydown.escape.window="closeEditor()"
                                        x-data="{
                                            showModal: false,
                                            enabled: {{ $row['enabled'] ? 'true' : 'false' }},
                                            channels: @js($row['channels']),
                                            conditions: @js($isPOStatus ? ($row['conditions'] ?? ['from_status' => '', 'to_status' => '']) : null),
                                            subject: @js($row['subject_template']),
                                            body: @js($row['body_template']),
                                            internal: @js($row['internal_template'] ?? ($row['body_template'] ?? '')),
                                            sms: @js($row['sms_template'] ?? ''),
                                            placeholders: @js($row['allowed_placeholders'] ?? $row['placeholders']),
                                            formAction: @js($formAction),
                                            hasId: @js((bool)$rowId),
                                            pQ: '',
                                            selectedIdx: 0,
                                            focusedField: 'body',
                                            get tokens(){ return (this.placeholders||[]).concat(['{form_title}','{sender_name}','{status}','{url}','@{{ url }}','@{{ actor.name }}']); },
                                            filteredTokens(){ const q=(this.pQ||'').toLowerCase(); const list=this.tokens; if(!q) return list; return list.filter(t=> (t||'').toLowerCase().includes(q)); },
                                            insertToken(token){
                                                const field = this.focusedField==='subject' ? this.$refs.subjectArea : (this.focusedField==='sms' ? this.$refs.smsArea : this.$refs.bodyArea);
                                                if(!field) return;
                                                const start=field.selectionStart||0; const end=field.selectionEnd||0; const val=field.value||'';
                                                const newVal=val.substring(0,start)+token+val.substring(end);
                                                field.value=newVal;
                                                field.dispatchEvent(new Event('input',{bubbles:true}));
                                                this.$nextTick(()=>{ const pos=start+token.length; field.setSelectionRange(pos,pos); field.focus(); });
                                            },
                                            copyToken(token){ navigator.clipboard && navigator.clipboard.writeText(token).catch(()=>{}); },
                                            onKeyNav(e){
                                                const len=this.filteredTokens().length; if(!len) return;
                                                if(e.key==='ArrowLeft' || e.key==='ArrowUp'){ e.preventDefault(); this.selectedIdx=(this.selectedIdx-1+len)%len; }
                                                else if(e.key==='ArrowRight' || e.key==='ArrowDown'){ e.preventDefault(); this.selectedIdx=(this.selectedIdx+1)%len; }
                                                else if(e.key==='Enter'){ e.preventDefault(); const tok=this.filteredTokens()[this.selectedIdx]; if(tok) this.insertToken(tok); }
                                            },
                                            previewLocal(){
                                                const dummy = {
                                                    '{po_number}':'PO-1001',
                                                    '{po_subject}':'PO Subject',
                                                    '{from_status}':'from-status',
                                                    '{to_status}':'to-status',
                                                    '{requester_name}':'Requester Name',
                                                    '{proforma_number}':'PF-2001',
                                                    '{customer_name}':'Customer Name',
                                                    '{approver_name}':'Approver Name',
                                                    '{lead_name}':'Lead Name',
                                                    '{old_user}':'Old User',
                                                    '{new_user}':'New User',
                                                    '{note_excerpt}':'Note excerpt...',
                                                    '{mentioned_user}':'Mentioned User',
                                                    '{context}':'Context',
                '{form_title}':'????? ??? ?????',
                                                };
                                                let s = this.subject || '';
                                                let b = this.body || '';
                                                let d = this.internal || '';
                                                let m = this.sms || '';
                                                Object.entries(dummy).forEach(([k,v])=>{ s = s.split(k).join(v); b = b.split(k).join(v); d = d.split(k).join(v); m = m.split(k).join(v); });
                                                s = s.split('@{{ url }}').join('https://crm.local/item');
                                                s = s.split('@{{ actor.name }}').join('Actor Name');
                                                b = b.split('@{{ url }}').join('https://crm.local/item');
                                                b = b.split('@{{ actor.name }}').join('Actor Name');
                                                d = d.split('@{{ url }}').join('https://crm.local/item');
                                                d = d.split('@{{ actor.name }}').join('Actor Name');
                                                m = m.split('@{{ url }}').join('https://crm.local/item');
                                                m = m.split('@{{ actor.name }}').join('Actor Name');
                                                return {subject:s, body:b, internal:d, sms:m};
                                            },
                                            openEditor(){
                                                try {
                                                    this.showModal = true;
                                                    this.renderPreview();
                                                    document.body.style.overflow = 'hidden';
                                                    this.$nextTick(()=>{ this.$refs.bodyArea?.focus(); });
                                                } catch(e){
                                                    this.showModal = false;
                                                    document.body.style.overflow = '';
                                                    try { console.error(e); } catch(_) {}
                                                }
                                            },
                                            closeEditor(){
                                                this.showModal = false;
                                                document.body.style.overflow = '';
                                            },
                                            async saveTemplates(){
                                                try {
                                                    const form=this.$refs.rowForm;
                                                    if(!form) return;
                                                    const fd=new FormData(form);
                                                    fd.set('subject_template', this.subject||'');
                                                    fd.set('body_template', this.body||'');
                                                    fd.set('internal_template', this.internal||'');
                                                    fd.set('sms_template', this.sms||'');
                                                    const method='POST';
                                                    if(this.hasId){ fd.set('_method','PUT'); }
                                                    const res= await fetch(this.formAction, { method, headers: { 'Accept':'application/json' }, body: fd });
                                                    if(!res.ok){
                                                        const data= await res.json().catch(()=>({}));
                                                        throw new Error(data.message||'خطا در ذخیره‌سازی');
                                                    }
                                                    const data= await res.json();
                                                    if(data && data.rule){
                                                        this.subject=data.rule.subject_template||this.subject;
                                                        this.body=data.rule.body_template||this.body;
                                                        this.sms=data.rule.sms_template||this.sms;
                                                    }
                                                    this.closeEditor();
                                                    this.$dispatch('toast', {color:'green', text:'ذخیره شد'});
                                                } catch(err){
                                                    this.$dispatch('toast', {color:'red', text: (err && err.message) ? err.message : 'ذخیره ناموفق بود'});
                                                }
                                            },
                                        }">
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $row['module_label'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $row['event_label'] }}</td>
                                        <td class="px-4 py-3">
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="checkbox" x-model="enabled" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-green-600 relative transition"></div>
                                            </label>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-70ش0">
                                            <div class="flex gap-4">
                                                @foreach($channelOptions as $chKey => $chLabel)
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" name="channels[]" value="{{ $chKey }}" x-model="channels" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" form="rowForm-{{ $rowId ?? ($row['module'].'-'.$row['event']) }}">
                                                        <span>{{ $chLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @if($isPOStatus)
                                                <div class="flex items-center gap-2">
                                                    {{-- از وضعیت: دراپ‌داون با مقادیر مجاز سفارش خرید --}}
                                                    <select x-model="conditions.from_status" class="w-40 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                        <option value="">(همهٔ وضعیت‌ها)</option>
                                                        @foreach(($poStatuses ?? []) as $code => $label)
                                                            <option value="{{ $code }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span>→</span>
                                                    {{-- به وضعیت: دراپ‌داون با مقادیر مجاز سفارش خرید --}}
                                                    <select x-model="conditions.to_status" class="w-40 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                        <option value="">(همهٔ وضعیت‌ها)</option>
                                                        @foreach(($poStatuses ?? []) as $code => $label)
                                                            <option value="{{ $code }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-blue-700">
                                            <button type="button" @click="openEditor()" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 rounded border border-blue-200">ویرایش متن</button>
                                            <div class="mt-1 text-xs text-gray-600">نمونه ذخیره‌شده:</div>
                                                <div class="mt-1 p-2 bg-gray-50 rounded border text-xs whitespace-pre-line">
                                                <div class="font-semibold" x-text="previewLocal().subject"></div>
                                                <div x-text="previewLocal().body"></div>
                                                <template x-if="(channels||[]).includes('database') && (internal||'').trim() !== ''">
                                                    <div class="mt-2 text-gray-700">
                                                        <div class="font-semibold">System:</div>
                                                        <div x-text="previewLocal().internal"></div>
                                                    </div>
                                                </template>
                                                <template x-if="(channels||[]).includes('sms') && (sms||'').trim() !== ''">
                                                    <div class="mt-2 text-gray-700">
                                                        <div class="font-semibold">SMS:</div>
                                                        <div x-text="previewLocal().sms"></div>
                                                    </div>
                                                </template>
                                                </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <form x-ref="rowForm" :action="formAction" method="post" class="inline-block" id="rowForm-{{ $rowId ?? ($row['module'].'-'.$row['event']) }}">
                                                @csrf
                                                @if($rowId)
                                                    @method('PUT')
                                                @endif

                                                @unless($rowId)
                                                    <input type="hidden" name="module" value="{{ $row['module'] }}">
                                                    <input type="hidden" name="event" value="{{ $row['event'] }}">
                                                @endunless

                                                <input type="hidden" name="enabled" :value="enabled ? 1 : 0" x-effect="$el.value = enabled ? 1 : 0">

                                                

                                                @if($isPOStatus)
                                                <template x-if="conditions">
                                                <div>
                                                    {{-- hidden inputs برای ارسال شروط از طریق فرم اکشن --}}
                                                    {{-- Use both flat keys and nested keys; keep values synchronized via x-effect --}}
                                                    <input type="hidden" name="from_status"
                                                           x-effect="$el.value = (conditions.from_status ?? '')"
                                                           :value="conditions.from_status ?? ''">
                                                    <input type="hidden" name="to_status"
                                                           x-effect="$el.value = (conditions.to_status ?? '')"
                                                           :value="conditions.to_status ?? ''">

                                                    {{-- Backward-compatible nested payload for existing validator paths --}}
                                                    <input type="hidden" name="conditions[from_status]"
                                                           x-effect="$el.value = (conditions.from_status ?? '')"
                                                           :value="conditions.from_status ?? ''">
                                                    <input type="hidden" name="conditions[to_status]"
                                                           x-effect="$el.value = (conditions.to_status ?? '')"
                                                           :value="conditions.to_status ?? ''">
                                                </div>
                                                </template>
                                                @endif

                                                <input type="hidden" name="subject_template" :value="subject">
                                                <input type="hidden" name="body_template" :value="body">
                                                <input type="hidden" name="internal_template" :value="internal">
                                                <input type="hidden" name="sms_template" :value="sms">

                                                <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded hover:bg-green-700">ذخیره</button>
                                            </form>

                                            @if($rowId)
                                                <form action="{{ route('settings.notifications.destroy', $rowId) }}" method="post" class="inline-block ml-2" onsubmit="return confirm('حذف این قانون؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded hover:bg-red-700">حذف</button>
                                                </form>
                                            @endif

                                          <!-- Modal (teleported to body for consistent Alpine scope) -->
                                            @include('settings.notifications.partials.notification-template-editor', [
                                                'editorConfig' => [
                                                    'panelClasses' => 'relative max-w-4xl mx-auto my-10 sm:my-16 bg-white border rounded-lg p-4 shadow max-h-[90vh] overflow-y-auto',

                                                    // برچسب‌ها
                                                    'bodyLabel' => 'متن اعلان (ایمیل / اعلان داخلی)',
                                                    'smsLabel' => 'متن پیامک (SMS)',

                                                    // Placeholderها
                                                    'bodyPlaceholder' => 'متن کامل اعلان (ایمیل یا اعلان داخلی) را اینجا وارد کنید. می‌توانید از توکن‌های بالا برای شخصی‌سازی متن استفاده کنید.',
                                                    'smsPlaceholder' => 'متن پیامکی که برای کاربر ارسال می‌شود را اینجا وارد کنید. در صورت نیاز می‌توانید از همان توکن‌ها استفاده کنید.',

                                                    // متن راهنما برای توکن‌ها
                                                    'showTokenHelperText' => true,
                                                    'tokenHelperText' => 'برای شخصی‌سازی اعلان‌ها می‌توانید از توکن‌های زیر استفاده کنید. این توکن‌ها هنگام ارسال، با مقادیر واقعی (مثل نام کاربر، شماره سفارش و ...) جایگزین می‌شوند.',

                                                    // متن انتهایی توکن‌ها
                                                    'showTokenFooter' => true,
                                                    'tokenFooterText' => 'نمونه‌هایی از توکن‌های قابل استفاده در متن اعلان و پیامک:',

                                                    // چینش دکمه‌ها
                                                    'buttonsLayout' => 'text-right',

                                                    // متن دکمه‌ها
                                                    'saveButtonText' => 'ذخیره تغییرات',
                                                ],
                                            ])


                                        </td>
                                    </tr>

                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Toasts -->
                    @if(session('status'))
                        <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" class="fixed bottom-6 left-6 bg-green-600 text-white px-4 py-2 rounded shadow">{{ session('status') }}</div>
                    @endif
                    @if(session('error'))
                        <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4000)" class="fixed bottom-6 left-6 bg-red-600 text-white px-4 py-2 rounded shadow">{{ session('error') }}</div>
                    @endif
                    @if($errors->has('error'))
                        <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,5000)" class="fixed bottom-6 left-6 bg-red-600 text-white px-4 py-2 rounded shadow">
                            {{ $errors->first('error') }}
                            @if(session('request_id'))
                                <div class="text-xs opacity-80 mt-1">کد پیگیری: {{ session('request_id') }}</div>
                            @endif
                        </div>
                    @endif
                    <div x-data="{show:false,text:'',color:'green'}" @toast.window="show=true; text=$event.detail.text||''; color=$event.detail.color||'green'; setTimeout(()=>show=false,3000)" x-show="show" x-cloak :class="color==='red'?'bg-red-600':'bg-green-600'" class="fixed bottom-6 left-6 text-white px-4 py-2 rounded shadow">
                        <span x-text="text"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function notificationsMatrix() {
    return {
        q: '',
        toggleAll: false,
        preview: {subject:'', body:'', internal:'', sms:''},
        init(){},
        matchesFilter(text){
            const t = (this.q||'').trim();
            return t === '' || (text||'').toLowerCase().includes(t.toLowerCase());
        },
        clearSearch(){ this.q=''; },
        applyToggleAll(){
            document.querySelectorAll('tbody tr [type=checkbox].sr-only').forEach(el=>{
                el.checked = this.toggleAll;
                el.dispatchEvent(new Event('change',{bubbles:true}))
            });
        },
        renderPreview(){
            const dummy = {
                '{po_number}':'PO-1001',
                '{po_subject}':'PO Subject',
                '{from_status}':'ایجاد',
                '{to_status}':'تأیید',
                '{requester_name}':'کاربر درخواست‌کننده',
                '{proforma_number}':'PF-2001',
                '{customer_name}':'مشتری نمونه',
                '{approver_name}':'تأییدکننده',
                '{lead_name}':'سرنخ نمونه',
                '{old_user}':'کاربر قبلی',
                '{new_user}':'کاربر جدید',
                '{note_excerpt}':'نمونه متن یادداشت...',
                '{mentioned_user}':'کاربر منشن‌شده',
                '{context}':'زمینه مرتبط',
                '{form_title}':'????? ??? ?????',
            };
            let s = this.subject || '';
            let b = this.body || '';
            let m = this.sms || '';
            Object.entries(dummy).forEach(([k,v])=>{
                s = s.split(k).join(v);
                b = b.split(k).join(v);
                m = m.split(k).join(v);
            });
            s = s.split('@{{ url }}').join('https://crm.local/item');
            s = s.split('@{{ actor.name }}').join('کاربر سیستم');
            b = b.split('@{{ url }}').join('https://crm.local/item');
            b = b.split('@{{ actor.name }}').join('کاربر سیستم');
            m = m.split('@{{ url }}').join('https://crm.local/item');
            m = m.split('@{{ actor.name }}').join('کاربر سیستم');
            this.preview = {subject:s, body:b, sms:m};
        }
    }
}

function notificationRuleRow(config = {}) {
    return {
        showModal: false,
        enabled: config.enabled ?? false,
        channels: config.channels ?? [],
        conditions: config.conditions ?? null,
        subject: config.subject ?? '',
        body: config.body ?? '',
        internal: config.internal ?? (config.body ?? ''),
        sms: config.sms ?? '',
        placeholders: config.placeholders ?? [],
        formAction: config.formAction ?? '',
        hasId: !!config.hasId,
        module: config.module ?? '',
        event: config.event ?? '',
        previewUrl: config.previewUrl ?? '',
        csrfToken: config.csrfToken ?? (document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''),
        isPreviewing: false,
        pQ: '',
        selectedIdx: 0,
        focusedField: 'body',
        preview: {subject:'', body:'', internal:'', sms:''},
        init() {
            this.preview = this.computeFallbackPreview();
            if (this.previewUrl) {
                this.renderPreview();
            }
        },
        get tokens() {
            return (this.placeholders || []).concat(['{form_title}','{sender_name}','{status}','{url}','@{{ url }}','@{{ actor.name }}']);
        },
        filteredTokens() {
            const q = (this.pQ || '').toLowerCase();
            if (!q) return this.tokens;
            return this.tokens.filter(t => (t || '').toLowerCase().includes(q));
        },
        insertToken(token) {
            const field = this.focusedField === 'subject'
                ? this.$refs.subjectArea
                : (this.focusedField === 'sms' ? this.$refs.smsArea : this.$refs.bodyArea);
            if (!field) return;
            const start = field.selectionStart || 0;
            const end = field.selectionEnd || 0;
            const val = field.value || '';
            const newVal = val.substring(0, start) + token + val.substring(end);
            field.value = newVal;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            this.$nextTick(() => {
                const pos = start + token.length;
                field.setSelectionRange(pos, pos);
                field.focus();
            });
        },
        copyToken(token) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(token).catch(() => {});
            }
        },
        onKeyNav(e) {
            const len = this.filteredTokens().length;
            if (!len) return;
            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedIdx = (this.selectedIdx - 1 + len) % len;
            } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectedIdx = (this.selectedIdx + 1) % len;
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const tok = this.filteredTokens()[this.selectedIdx];
                if (tok) this.insertToken(tok);
            }
        },
        computeFallbackPreview() {
            const dummy = {
                '{po_number}':'PO-1001',
                '{po_subject}':'PO Subject',
                '{from_status}':'from-status',
                '{to_status}':'to-status',
                '{requester_name}':'Requester Name',
                '{proforma_number}':'PF-2001',
                '{customer_name}':'Customer Name',
                '{approver_name}':'Approver Name',
                '{lead_name}':'Lead Name',
                '{old_user}':'Old User',
                '{new_user}':'New User',
                '{note_excerpt}':'Note excerpt...',
                '{mentioned_user}':'Mentioned User',
                '{context}':'Context',
                '{form_title}':'عنوان فرم',
            };
            let s = this.subject || '';
            let b = this.body || '';
            let d = this.internal || '';
            let m = this.sms || '';
            Object.entries(dummy).forEach(([k, v]) => {
                s = s.split(k).join(v);
                b = b.split(k).join(v);
                d = d.split(k).join(v);
                m = m.split(k).join(v);
            });
            const replaceGlobals = text => text
                .split('@{{ url }}').join('https://crm.local/item')
                .split('@{{ actor.name }}').join('Actor Name');
            s = replaceGlobals(s);
            b = replaceGlobals(b);
            d = replaceGlobals(d);
            m = replaceGlobals(m);
            return { subject: s, body: b, internal: d, sms: m };
        },
        previewLocal() {
            if (this.preview && (this.preview.subject || this.preview.body || this.preview.sms)) {
                return this.preview;
            }
            return this.computeFallbackPreview();
        },
        async renderPreview() {
            if (!this.previewUrl) {
                this.preview = this.computeFallbackPreview();
                return;
            }
            this.isPreviewing = true;
            try {
                const res = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken || (document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''),
                    },
                    body: JSON.stringify({
                        module: this.module,
                        event: this.event,
                        subject: this.subject || '',
                        body: this.body || '',
                        sms: this.sms || '',
                    }),
                });
                if (!res.ok) {
                    throw new Error('preview_failed');
                }
                const data = await res.json();
                this.preview = {
                    subject: data.preview?.subject ?? (this.subject || ''),
                    body: data.preview?.body ?? (this.body || ''),
                    internal: data.preview?.body ?? (this.internal || this.body || ''),
                    sms: data.preview?.sms ?? (this.sms || ''),
                };
            } catch (e) {
                try { console.error(e); } catch (_) {}
                this.preview = this.computeFallbackPreview();
            } finally {
                this.isPreviewing = false;
            }
        },
        openEditor() {
            try {
                this.showModal = true;
                this.renderPreview();
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => { this.$refs.bodyArea?.focus(); });
            } catch (e) {
                this.showModal = false;
                document.body.style.overflow = '';
                try { console.error(e); } catch (_) {}
            }
        },
        closeEditor() {
            this.showModal = false;
            document.body.style.overflow = '';
        },
        async saveTemplates() {
            if (!this.hasId) {
                this.closeEditor();
                return;
            }
            try {
                const form = this.$refs.rowForm;
                if (!form) return;
                const fd = new FormData(form);
                fd.set('subject_template', this.subject || '');
                fd.set('body_template', this.body || '');
                fd.set('internal_template', this.internal || '');
                fd.set('sms_template', this.sms || '');
                const method = 'POST';
                fd.set('_method', 'PUT');
                const res = await fetch(this.formAction, { method, headers: { 'Accept': 'application/json' }, body: fd });
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || 'خطا در ذخیره‌سازی متن اعلان');
                }
                const data = await res.json();
                if (data && data.rule) {
                    this.subject = data.rule.subject_template || this.subject;
                    this.body = data.rule.body_template || this.body;
                    this.sms = data.rule.sms_template || this.sms;
                }
                this.closeEditor();
                this.$dispatch('toast', { color: 'green', text: 'متن اعلان ذخیره شد' });
            } catch (err) {
                this.$dispatch('toast', { color: 'red', text: (err && err.message) ? err.message : 'خطا در ذخیره‌سازی' });
            }
        },
    };
}
</script>

@endpush
