@php
    $models = config('reports.models');
    $initial = old('query_json', $report->query_json ?? [ 'model' => null, 'selects' => [], 'filters' => [], 'group_by' => [], 'aggregates' => [], 'sorts' => [], 'limit' => 15, 'page' => 1 ]);
@endphp

<div x-data="reportBuilder()" x-init="init(@js($models), @js($initial))" class="space-y-4" dir="rtl">
    <input type="hidden" name="query_json" :value="jsonString">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label class="block mb-1">مدل</label>
            <select x-model="state.model" @change="onModelChange" class="w-full border rounded p-2">
                <option value="">— انتخاب مدل —</option>
                @foreach($models as $key => $cfg)
                    <option value="{{ $key }}">{{ $cfg['label'] ?? $key }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block mb-1">ستون‌ها (Select)</label>
            <div class="flex items-center gap-2">
                <select x-model="pickers.select" @change="addSelect()" class="border rounded p-2">
                    <option value="">افزودن ستون...</option>
                    <template x-for="f in availableFields()" :key="'s-'+f">
                        <option :value="f" x-text="labelFor(f)"></option>
                    </template>
                </select>
                <button type="button" @click="clearSelects()" class="text-xs px-2 py-1 bg-gray-100 rounded">حذف همه</button>
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
                <template x-for="(f, i) in state.selects" :key="'sel-'+f">
                    <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded">
                        <span x-text="labelFor(f)"></span>
                        <button type="button" class="hover:text-blue-900" @click="removeSelect(i)">×</button>
                    </span>
                </template>
            </div>
            <div class="mt-2 text-xs text-gray-500">برای افزودن سریع می‌توانید از لیست بالا انتخاب کنید. ستون‌ها چندانتخابی هستند.</div>
        </div>
    </div>

    <div>
        <div class="flex items-center  gap-2 mb-2">
            <label class="font-semibold">فیلترها</label>
            <button type="button" @click="addFilter" class="px-2 py-1 text-sm bg-gray-100 rounded">افزودن</button>
        </div>
        <div class="space-y-2">
            <template x-for="(f, idx) in state.filters" :key="idx">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center" x-init="setupFilterRow($el, f)">
                    <select x-model="f.field" class="border rounded p-2">
                        <option value="">فیلد</option>
                        <template x-for="(type, field) in fields()" :key="field">
                            <option :value="field" x-text="labelFor(field)"></option>
                        </template>
                    </select>
                    <!-- Jalali date inputs (rendered but toggled via setupFilterRow) -->
                    <template x-if="isDateField(f.field) && f.operator === 'between'">
                        <div class="flex gap-2">
                            <input type="text" class="border rounded p-2 persian-datepicker js-dp-from"
                                   placeholder="از تاریخ"
                                   x-ref="from"
                                   x-init="initFilterDatepicker($refs.from, f, 'from')">
                            <input type="text" class="border rounded p-2 persian-datepicker js-dp-to"
                                   placeholder="تا تاریخ"
                                   x-ref="to"
                                   x-init="initFilterDatepicker($refs.to, f, 'to')">
                        </div>
                    </template>
                    <template x-if="isDateField(f.field) && f.operator !== 'between'">
                        <input type="text" class="border rounded p-2 persian-datepicker js-dp-single"
                               placeholder="تاریخ"
                               x-ref="single"
                               x-init="initFilterDatepicker($refs.single, f, null)">
                    </template>
                    <select x-model="f.operator" class="border rounded p-2">
                        <option value="">عملگر</option>
                        <template x-if="f.field">
                            <template x-for="op in operatorsFor(f.field)" :key="op">
                                <option :value="op" x-text="opLabel(op)"></option>
                            </template>
                        </template>
                    </select>
                    <input type="text" x-model="f.value" class="border rounded p-2" placeholder="مقدار (in/between با کاما، بین دو مقدار)">
                    <button type="button" @click="removeFilter(idx)" class="px-2 py-1 bg-red-100 text-red-700 rounded">حذف</button>
                </div>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label class="block mb-1">Group By</label>
            <div class="flex items-center gap-2">
                <select x-model="pickers.group" @change="addGroup()" class="border rounded p-2">
                    <option value="">افزودن گروه‌بندی...</option>
                    <template x-for="f in availableFields()" :key="'g-'+f">
                        <option :value="f" x-text="labelFor(f)" :disabled="state.group_by.includes(f)"></option>
                    </template>
                </select>
                <button type="button" @click="state.group_by=[]; refreshJson();" class="text-xs px-2 py-1 bg-gray-100 rounded">حذف همه</button>
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
                <template x-for="(f,i) in state.group_by" :key="'grp-'+f">
                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-2 py-1 rounded">
                        <span x-text="labelFor(f)"></span>
                        <button type="button" class="hover:text-emerald-900" @click="state.group_by.splice(i,1); refreshJson();">×</button>
                    </span>
                </template>
            </div>
        </div>
        <div class="md:col-span-2">
            <label class="block mb-1">Aggregates</label>
            <div class="space-y-2">
                <template x-for="(ag, idx) in state.aggregates" :key="idx">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center">
                        <select x-model="ag.fn" class="border rounded p-2">
                            <template x-for="fn in aggFns" :key="fn">
                                <option :value="fn" x-text="fn"></option>
                            </template>
                        </select>
                        <select x-model="ag.field" class="border rounded p-2">
                            <option value="">*</option>
                            <template x-for="(type, field) in fields()" :key="field">
                                <option :value="field" x-text="labelFor(field)"></option>
                            </template>
                        </select>
                        <input type="text" x-model="ag.as" class="border rounded p-2" placeholder="نام مستعار (اختیاری)">
                        <button type="button" @click="removeAggregate(idx)" class="px-2 py-1 bg-red-100 text-red-700 rounded">حذف</button>
                    </div>
                </template>
                <button type="button" @click="addAggregate" class="px-2 py-1 text-sm bg-gray-100 rounded">افزودن Aggregate</button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label class="block mb-1">مرتب‌سازی</label>
            <div class="space-y-2">
                <template x-for="(s, idx) in state.sorts" :key="idx">
                    <div class="bg-gray-50 p-2 rounded">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600">#<span x-text="idx+1"></span></span>
                            <button type="button" @click="state.sorts.splice(idx,1)" class="text-xs px-2 py-0.5 bg-red-100 text-red-700 rounded">حذف</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <select x-model="s.field" class="border rounded p-2">
                                <template x-for="(type, field) in fields()" :key="field">
                                    <option :value="field" x-text="field"></option>
                                </template>
                            </select>
                            <select x-model="s.dir" class="border rounded p-2">
                                <option value="asc">صعودی</option>
                                <option value="desc">نزولی</option>
                            </select>
                        </div>
                    </div>
                </template>
                <button type="button" @click="addSort" class="px-2 py-1 text-sm bg-gray-100 rounded">افزودن مرتب‌سازی</button>
            </div>
        </div>
        <div>
            <label class="block mb-1">Limit</label>
            <input type="number" min="1" max="200" x-model.number="state.limit" class="w-full border rounded p-2">
        </div>
        <div class="flex items-end">
            <button type="button" @click="preview" class="px-4 py-2 bg-blue-600 text-white rounded">پیش‌نمایش</button>
        </div>
    </div>

    <div x-show="previewData" class="bg-white rounded shadow p-3">
        <template x-if="previewError">
            <div class="text-red-600" x-text="previewError"></div>
        </template>
        <template x-if="!previewError && previewData">
            <div>
                <div class="mb-2 text-sm text-gray-600">خروجی (زمان اجرا: <span x-text="(previewData.meta?.exec_ms || 0) + 'ms'"></span>)</div>
                <div class="overflow-auto">
                    <table class="min-w-full text-right">
                        <thead>
                        <tr>
                            <template x-for="c in previewData.columns" :key="c">
                                <th class="px-2 py-1 bg-gray-50 border" x-text="labelFor(c)"></th>
                            </template>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="(row, i) in previewData.rows" :key="i">
                            <tr class="border-b">
                                <template x-for="c in previewData.columns" :key="c">
                                    <td class="px-2 py-1" x-text="formatCell(c, row[c])"></td>
                                </template>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
                <template x-if="previewData.summary">
                    <div class="mt-3 p-2 bg-gray-50 rounded">
                        <div class="font-semibold mb-1">خلاصه</div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <template x-for="(v,k) in previewData.summary" :key="k">
                                <div><span class="text-gray-600" x-text="k + ':'"></span> <span x-text="v"></span></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <script>
        function reportBuilder(){
            return {
                modelsCfg: {},
                state: { model: '', selects: [], filters: [], group_by: [], aggregates: [], sorts: [], limit: 15, page: 1 },
                pickers: { select: '', group: '' },
                aggFns: ['sum','avg','count','max','min'],
                jsonString: '',
                previewData: null,
                previewError: null,
                init(models, initial){ this.modelsCfg = models; this.state = Object.assign(this.state, initial||{}); this.opLabel = (op)=>{ const m = { eq:"برابر", neq:"نابرابر", like:"شبیه به", starts_with:"شروع با", ends_with:"پایان با", in:"در میان", gt:"بزرگ‌تر از", gte:"بزرگ‌تر یا مساوی", lt:"کوچک‌تر از", lte:"کوچک‌تر یا مساوی", between:"بین" }; return m[op] || op; }; this.refreshJson(); },
                fields(){ if (!this.state.model) return {}; return this.modelsCfg[this.state.model]?.fields || {}; },
                labelFor(field){ if(!this.state.model) return field; const labels = this.modelsCfg[this.state.model]?.labels || {}; return labels[field] || field; },
                fieldNames(){ return Object.keys(this.fields()); },
                availableFields(){ const chosen = new Set(this.state.selects); return this.fieldNames().filter(f=>!chosen.has(f)); },
                operatorsFor(field){ const type = this.fields()[field]; const ops = @js(config('reports.operators')); return ops[type] || []; },
                opLabel(op){ const m = { eq:'برابر', neq:'نابرابر', like:'شامل', starts_with:'شروع با', ends_with:'پایان با', in:'در لیست', gt:'بزرگتر از', gte:'بزرگتر یا مساوی', lt:'کوچکتر از', lte:'کوچکتر یا مساوی', between:'بین' }; return m[op] || op; },
                onModelChange(){ this.state.selects = []; this.state.filters = []; this.state.group_by = []; this.state.aggregates = []; this.state.sorts = []; this.refreshJson(); },
                // Selects UI
                addSelect(){ const f = this.pickers.select; if(!f) return; if(!this.state.selects.includes(f)) this.state.selects.push(f); this.pickers.select=''; this.refreshJson(); },
                removeSelect(i){ this.state.selects.splice(i,1); this.refreshJson(); },
                clearSelects(){ this.state.selects = []; this.refreshJson(); },
                // Filters
                addFilter(){ this.state.filters.push({field:'',operator:'',value:''}); this.refreshJson(); },
                removeFilter(i){ this.state.filters.splice(i,1); this.refreshJson(); },
                // Aggregates
                addAggregate(){ this.state.aggregates.push({fn:'count',field:'',as:''}); this.refreshJson(); },
                removeAggregate(i){ this.state.aggregates.splice(i,1); this.refreshJson(); },
                // Group by UI
                addGroup(){ const f = this.pickers.group; if(!f) return; if(!this.state.group_by.includes(f)) this.state.group_by.push(f); this.pickers.group=''; this.refreshJson(); },
                // Sorts
                addSort(){ this.state.sorts.push({field:'id',dir:'desc'}); this.refreshJson(); },
                // Misc
                refreshJson(){ this.jsonString = JSON.stringify(this.state); },
                // Formatting helpers (Jalali)
                isDateField(col){ const t = this.fields()[col]; return t === 'date' || t === 'datetime' || t === 'timestamp'; },
                pad(n){ return (n<10? '0':'') + n; },
                g2j(gy, gm, gd){
                    const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
                    let jy = (gy<=1600) ? 0 : 979; gy -= (gy<=1600) ? 621 : 1600; let gy2 = (gm>2)?(gy+1):gy;
                    let days = 365*gy + Math.floor((gy2+3)/4) - Math.floor((gy2+99)/100) + Math.floor((gy2+399)/400) - 80 + gd + g_d_m[gm-1];
                    jy += 33*Math.floor(days/12053); days %= 12053; jy += 4*Math.floor(days/1461); days %= 1461;
                    if (days > 365) { jy += Math.floor((days-1)/365); days = (days-1)%365; }
                    const jm = (days < 186) ? 1+Math.floor(days/31) : 7+Math.floor((days-186)/30);
                    const jd = 1 + ((days < 186) ? (days%31) : ((days-186)%30));
                    return [jy+ (gy<=1600? 621:0), jm, jd];
                },
                toJalaliString(val){ if(!val) return ''; const m = String(val).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/); if(!m) return val; const jy=this.g2j(+m[1],+m[2],+m[3]); const date = `${jy[0]}/${this.pad(jy[1])}/${this.pad(jy[2])}`; if(m[4]){ return `${date} ${m[4]}:${m[5]}${m[6]? ':'+m[6]:''}`; } return date; },
                formatCell(col, val){ if(this.isDateField(col)) return this.toJalaliString(val); return val; },

                // Initialize a Persian datepicker and sync to filter value (Gregorian)
                initFilterDatepicker(el, f, part){
                    if (!el) return;
                    const self = this;
                    try { $(el).persianDatepicker('destroy'); } catch(e){}
                    $(el).persianDatepicker({
                        format: 'YYYY/MM/DD',
                        initialValue: false,
                        autoClose: true,
                        observer: true,
                        calendar: { persian: { locale: 'fa', leapYearMode: 'astronomical' }, gregorian: { locale: 'en' } },
                        onSelect(unix){
                            try{
                                const g = new persianDate(unix).toCalendar('gregorian').toLocale('en').format('YYYY-MM-DD');
                                if(part === 'from'){
                                    const parts = String(f.value||'').split(',');
                                    f.value = `${g}${parts[1] ? ','+parts[1] : ''}`;
                                }else if(part === 'to'){
                                    const parts = String(f.value||'').split(',');
                                    f.value = `${parts[0]||''}${parts[0]? ',':''}${g}`;
                                }else{
                                    f.value = g;
                                }
                                self.refreshJson();
                            }catch(e){}
                        }
                    });

                    // Set initial visible value from existing Gregorian stored in f.value
                    const setInitial = () => {
                        const val = String(f.value||'');
                        let g = null;
                        if(part === 'from') g = val.split(',')[0] || null;
                        else if(part === 'to') g = val.split(',')[1] || null;
                        else g = val || null;
                        const m = g ? g.match(/^(\d{4})-(\d{2})-(\d{2})/) : null;
                        if(m){
                            try{
                                const j = new persianDate([+m[1], +m[2], +m[3]]).calendar('gregorian').toCalendar('persian');
                                $(el).val(j.format('YYYY/MM/DD'));
                            }catch(e){}
                        }
                    };
                    setInitial();

                    // Manual typing support
                    $(el).on('change blur', function(){
                        const raw = ($(el).val()||'').trim();
                        const m = raw.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
                        if(!m) return;
                        try{
                            const g = new persianDate([+m[1], +m[2], +m[3]])
                                .calendar('persian').toCalendar('gregorian').toLocale('en').format('YYYY-MM-DD');
                            if(part === 'from'){
                                const parts = String(f.value||'').split(',');
                                f.value = `${g}${parts[1] ? ','+parts[1] : ''}`;
                            }else if(part === 'to'){
                                const parts = String(f.value||'').split(',');
                                f.value = `${parts[0]||''}${parts[0]? ',':''}${g}`;
                            }else{
                                f.value = g;
                            }
                            self.refreshJson();
                        }catch(e){}
                    });
                },

                // Hide plain text input when a date field is selected; date inputs are shown via x-if
                setupFilterRow(el, f){
                    const plain = el.querySelector('input[x-model="f.value"]');
                    if(!plain) return;
                    const apply = () => { plain.style.display = this.isDateField(f.field) ? 'none' : ''; };
                    apply();
                    this.$watch(() => f.field, apply);
                    this.$watch(() => f.operator, apply);
                },
                async preview(){
                    this.previewError = null; this.previewData = null; this.refreshJson();
                    try{
                        const resp = await fetch(@js(route('reports.preview')), { method:'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') }, body: JSON.stringify({ query_json: this.state }) });
                        const data = await resp.json();
                        if(!resp.ok){ this.previewError = data.message || 'خطا در دریافت پیش‌نمایش'; return; }
                        this.previewData = data;
                    }catch(e){ this.previewError = 'خطای شبکه/سرور در پیش‌نمایش'; }
                },
                $watch: { state: { deep: true, handler(){ this.refreshJson(); } } }
            }
        }
    </script>
</div>
