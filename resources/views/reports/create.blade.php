@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'گزارش‌ها', 'url' => route('reports.index')],
        ['title' => 'ایجاد'],
    ];
@endphp

@section('content')
    <div class="py-6" dir="rtl">
        @include('components.toast')
        <h1 class="text-xl font-semibold mb-4">ایجاد گزارش</h1>

        <form action="{{ route('reports.store') }}" method="post" class="bg-white p-4 rounded shadow space-y-4"
              x-data="{ step: 1, go(n){ this.step = n; if(n===3){ this.$nextTick(()=>{ this.$dispatch('build-preview') }) } }, next(){ if(this.step<3){ this.go(this.step+1) } }, prev(){ if(this.step>1){ this.go(this.step-1) } } }"
              x-on:build-preview.window="(function(){ try { const q = document.querySelector('input[name=query_json]')?.value; if(!q) return; fetch('{{ route('reports.preview') }}', { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') }, body: JSON.stringify({ query_json: JSON.parse(q) }) }).then(r=>r.json().then(d=>({ok:r.ok,data:d}))).then(({ok,data})=>{ const ev = new CustomEvent('wizard-preview-ready', { detail: { ok, data } }); window.dispatchEvent(ev); }); } catch(e){} })()">
            @csrf

            <div class="mb-4">
                <div class="flex items-center justify-center gap-12">
                    <div class="flex flex-col items-center">
                        <div :class="step===1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'" class="w-8 h-8 rounded-full flex items-center justify-center">1</div>
                        <div class="mt-1 text-sm">جزئیات گزارش</div>
                    </div>
                    <div class="h-0.5 bg-gray-200 w-24"></div>
                    <div class="flex flex-col items-center">
                        <div :class="step===2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'" class="w-8 h-8 rounded-full flex items-center justify-center">2</div>
                        <div class="mt-1 text-sm">مدل‌ها، ستون‌ها، فیلترها</div>
                    </div>
                    <div class="h-0.5 bg-gray-200 w-24"></div>
                    <div class="flex flex-col items-center">
                        <div :class="step===3 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'" class="w-8 h-8 rounded-full flex items-center justify-center">3</div>
                        <div class="mt-1 text-sm">پیش‌نمایش و تایید</div>
                    </div>
                </div>
            </div>

            <div x-show="step===1" x-cloak>
                @include('reports._form_basic', ['report' => null])
            </div>

            <div x-show="step===2" x-cloak class="bg-white p-4 rounded border">
                <h3 class="font-semibold mb-2">ساخت گزارش</h3>
                @include('reports._builder', ['report' => (object)['query_json' => null]])
                <p class="text-sm text-gray-500 mt-2">نکته: فهرست «ستون‌ها» چندانتخابی است.</p>
            </div>

            <div x-show="step===3" x-cloak class="bg-white p-4 rounded border"
                 x-data="{ ok: false, data: null, error: null, init(){ const self=this; window.addEventListener('wizard-preview-ready', (e)=>{ self.ok = !!e.detail.ok; self.data = e.detail.data; self.error = e.detail.ok ? null : (e.detail.data?.message||'خطا در دریافت پیش‌نمایش'); }); } }">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold">پیش‌نمایش نتیجه گزارش</h3>
                    <button type="button" class="px-3 py-1 bg-gray-100 rounded" @click="$dispatch('build-preview')">بروزرسانی پیش‌نمایش</button>
                </div>
                <template x-if="error">
                    <div class="p-2 bg-red-50 border border-red-200 text-red-700 rounded" x-text="error"></div>
                </template>
                <template x-if="data && data.columns && data.rows">
                    <div class="overflow-auto">
                        <table class="min-w-full text-right">
                            <thead>
                            <tr>
                                <template x-for="c in (data.columns||[])" :key="c">
                                    <th class="px-2 py-1 bg-gray-50 border" x-text="(ReportWizard.labels()[c]||c)"></th>
                                </template>
                            </tr>
                            </thead>
                            <tbody>
                            <template x-for="(row, i) in (data.rows||[])" :key="i">
                                <tr class="border-b">
                                    <template x-for="c in (data.columns||[])" :key="c">
                                        <td class="px-2 py-1" x-text="ReportWizard.formatCell(c, row[c])"></td>
                                    </template>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>
                </template>
                <template x-if="data && data.summary">
                    <div class="mt-3 p-2 bg-gray-50 rounded">
                        <div class="font-semibold mb-1">خلاصه</div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <template x-for="(v,k) in data.summary" :key="k">
                                <div><span class="text-gray-600" x-text="k + ':'"></span> <span x-text="v"></span></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="prev()" x-show="step>1" x-cloak class="px-4 py-2 bg-gray-200 rounded">قبلی</button>
                <button type="button" @click="next()" x-show="step<3" x-cloak class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">بعدی</button>
                <button type="submit" x-show="step===3" x-cloak class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">ایجاد</button>
                <a href="{{ route('reports.index') }}" class="px-4 py-2 bg-gray-100 rounded">لغو</a>
            </div>
        </form>
        <script>
            window.ReportWizard = {
                labels(){ try{ const q=document.querySelector('input[name=query_json]')?.value; if(!q) return {}; const st=JSON.parse(q); const cfg=@js(config('reports.models')); return (cfg[st.model]?.labels)||{}; }catch(e){ return {}; } },
                fields(){ try{ const q=document.querySelector('input[name=query_json]')?.value; if(!q) return {}; const st=JSON.parse(q); const cfg=@js(config('reports.models')); return (cfg[st.model]?.fields)||{}; }catch(e){ return {}; } },
                isDate(c){ const t=this.fields()[c]; return t==='date'||t==='datetime'||t==='timestamp'; },
                pad(n){ return (n<10?'0':'')+n; },
                g2j(gy,gm,gd){ const g_d_m=[0,31,59,90,120,151,181,212,243,273,304,334]; let jy=(gy<=1600)?0:979; gy-=(gy<=1600)?621:1600; let gy2=(gm>2)?(gy+1):gy; let days=365*gy+Math.floor((gy2+3)/4)-Math.floor((gy2+99)/100)+Math.floor((gy2+399)/400)-80+gd+g_d_m[gm-1]; jy+=33*Math.floor(days/12053); days%=12053; jy+=4*Math.floor(days/1461); days%=1461; if(days>365){ jy+=Math.floor((days-1)/365); days=(days-1)%365;} const jm=(days<186)?1+Math.floor(days/31):7+Math.floor((days-186)/30); const jd=1+((days<186)?(days%31):((days-186)%30)); return [jy+(gy<=1600?621:0),jm,jd]; },
                toJalali(v){ if(!v) return ''; const m=String(v).match(/^(\\d{4})-(\\d{2})-(\\d{2})(?:[ T](\\d{2}):(\\d{2})(?::(\\d{2}))?)?/); if(!m) return v; const jy=this.g2j(+m[1],+m[2],+m[3]); const d=`${jy[0]}/${this.pad(jy[1])}/${this.pad(jy[2])}`; if(m[4]) return `${d} ${m[4]}:${m[5]}${m[6]? ':'+m[6]:''}`; return d; },
                formatCell(c,v){ return this.isDate(c) ? this.toJalali(v) : v; }
            };
        </script>
    </div>
@endsection
