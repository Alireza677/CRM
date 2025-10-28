@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'تنظیمات', 'url' => route('settings.index')],
            ['title' => 'تنظیمات اعلان‌ها'],
        ];
    @endphp

    <div class="py-12" x-data="notificationsMatrix()" x-init="init()" dir="rtl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-2xl font-semibold text-right">ماتریس تنظیمات اعلان‌ها</h1>
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
                                            conditions: @js($row['conditions'] ?? ['from_status' => '', 'to_status' => '']),
                                            subject: @js($row['subject_template']),
                                            body: @js($row['body_template']),
                                            placeholders: @js($row['allowed_placeholders'] ?? $row['placeholders']),
                                            formAction: @js($formAction),
                                            hasId: @js((bool)$rowId),
                                            pQ: '',
                                            selectedIdx: 0,
                                            focusedField: 'body',
                                            get tokens(){ return (this.placeholders||[]).concat(['@{{ url }}','@{{ actor.name }}']); },
                                            filteredTokens(){ const q=(this.pQ||'').toLowerCase(); const list=this.tokens; if(!q) return list; return list.filter(t=> (t||'').toLowerCase().includes(q)); },
                                            insertToken(token){
                                                const field=this.focusedField==='subject' ? this.$refs.subjectArea : this.$refs.bodyArea;
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
                                                };
                                                let s = this.subject || '';
                                                let b = this.body || '';
                                                Object.entries(dummy).forEach(([k,v])=>{ s = s.split(k).join(v); b = b.split(k).join(v); });
                                                s = s.split('@{{ url }}').join('https://crm.local/item');
                                                s = s.split('@{{ actor.name }}').join('Actor Name');
                                                b = b.split('@{{ url }}').join('https://crm.local/item');
                                                b = b.split('@{{ actor.name }}').join('Actor Name');
                                                return {subject:s, body:b};
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
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <div class="flex gap-4">
                                                @foreach($channelOptions as $chKey => $chLabel)
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" value="{{ $chKey }}" x-model="channels" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        <span>{{ $chLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @if($isPOStatus)
                                                <div class="flex items-center gap-2">
                                                    <input type="text" x-model="conditions.from_status" placeholder="از وضعیت" class="w-28 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <span>→</span>
                                                    <input type="text" x-model="conditions.to_status" placeholder="به وضعیت" class="w-28 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <form x-ref="rowForm" :action="formAction" method="post" class="inline-block">
                                                @csrf
                                                @if($rowId)
                                                    @method('PUT')
                                                @endif

                                                @unless($rowId)
                                                    <input type="hidden" name="module" value="{{ $row['module'] }}">
                                                    <input type="hidden" name="event" value="{{ $row['event'] }}">
                                                @endunless

                                                <input type="hidden" name="enabled" :value="enabled ? 1 : 0">

                                                <template x-for="ch in channels" :key="ch">
                                                    <input type="hidden" name="channels[]" :value="ch">
                                                </template>

                                                <template x-if="conditions">
                                                    <div>
                                                        <input type="hidden" name="conditions[from_status]" :value="conditions.from_status ?? ''">
                                                        <input type="hidden" name="conditions[to_status]" :value="conditions.to_status ?? ''">
                                                    </div>
                                                </template>

                                                <input type="hidden" name="subject_template" :value="subject">
                                                <input type="hidden" name="body_template" :value="body">

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
                                            <template x-teleport="body">
                                                <div x-show="showModal" x-cloak class="fixed inset-0 z-50" aria-modal="true" role="dialog">
                                                    <div class="absolute inset-0 bg-black/30" @click="closeEditor()" x-transition.opacity></div>
                                                    <div class="relative max-w-4xl mx-auto mt-10 sm:mt-16 bg-white border rounded-lg p-4 shadow"
                                                         dir="rtl"
                                                         x-show="showModal"
                                                         x-transition.opacity.scale.origin-top>
                                                        <div class="flex items-center justify-between mb-3">
                                                            <h3 class="text-lg font-semibold">ویرایش قالب اعلان</h3>
                                                            <button type="button" class="text-gray-500 hover:text-gray-700" @click="closeEditor()">بستن</button>
                                                        </div>
                                                        <div class="grid grid-cols-1 gap-3">
                                                            <div>
                                                                <label class="block text-sm text-gray-700 mb-1">عنوان</label>
                                                                <input type="text" x-ref="subjectArea" @focus="focusedField='subject'" x-model="subject" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm text-gray-700 mb-1">متن</label>
                                                                <textarea x-ref="bodyArea" @focus="focusedField='body'" x-model="body" rows="8" placeholder="برای شروع می‌توانید از کلیدواژه‌ها استفاده کنید" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                                            </div>
                                                            <div class="text-xs text-gray-500 mb-1">از کلیدواژه‌ها برای جای‌گذاری خودکار استفاده کنید.</div>
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <input type="text" x-model="pQ" @keydown="onKeyNav($event)" placeholder="جستجو در کلیدواژه‌ها..." class="w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                            </div>
                                                            <div class="flex flex-wrap gap-2" @keydown="onKeyNav($event)" tabindex="0">
                                                                <template x-for="(tok, idx) in filteredTokens()" :key="tok">
                                                                    <div :class="'px-2 py-1 rounded border text-xs cursor-pointer flex items-center gap-2 '+(idx===selectedIdx?'bg-blue-50 border-blue-300':'bg-gray-50 border-gray-200')" @click="insertToken(tok)" @mouseenter="selectedIdx=idx">
                                                                        <span x-text="tok"></span>
                                                                        <button type="button" class="text-gray-400 hover:text-gray-600" @click.stop="copyToken(tok)">کپی</button>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                از کلیدواژه‌ها برای جای‌گذاری خودکار استفاده کنید:
                                                                <span x-text="placeholders.join('، ')"></span>،
                                                                <code>{url}</code> یا <code>@{{ url }}</code>، <code>@{{ actor.name }}</code>
                                                            </div>
                                                            <div class="text-right mt-2">
                                                                <button type="button" class="px-4 py-2 bg-gray-200 rounded mr-2" @click="renderPreview()">به‌روزرسانی پیش‌نمایش</button>
                                                                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" @click="saveTemplates()">ذخیره</button>
                                                                <button type="button" class="px-4 py-2 bg-gray-200 rounded mr-2" @click="closeEditor()">انصراف</button>
                                                            </div>

                                                            <div class="mt-2">
                                                                <div class="text-sm font-medium mb-1">پیش‌نمایش</div>
                                                                <div class="p-3 bg-gray-50 rounded border text-sm whitespace-pre-line">
                                                                    <div class="font-semibold" x-text="preview.subject"></div>
                                                                    <div x-text="preview.body"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
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
        preview: {subject:'', body:''},
        init(){},
        matchesFilter(text){
            const t = (this.q||'').trim();
            return t === '' || (text||'').toLowerCase().includes(t.toLowerCase());
        },
        clearSearch(){ this.q=''; },
        applyToggleAll(){
            document.querySelectorAll('tbody tr [type=checkbox].sr-only').forEach(el=>{
                el.checked = this.toggleAll;
                el.dispatchEvent(new Event('input',{bubbles:true}))
            });
        },
        renderPreview(){
            const dummy = {
                '{po_number}':'PO-1001',
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
            };
            let s = this.subject || '';
            let b = this.body || '';
            Object.entries(dummy).forEach(([k,v])=>{
                s = s.split(k).join(v);
                b = b.split(k).join(v);
            });
            s = s.split('@{{ url }}').join('https://crm.local/item');
            s = s.split('@{{ actor.name }}').join('کاربر سیستم');
            b = b.split('@{{ url }}').join('https://crm.local/item');
            b = b.split('@{{ actor.name }}').join('کاربر سیستم');
            this.preview = {subject:s, body:b};
        }
    }
}
</script>
@endpush
