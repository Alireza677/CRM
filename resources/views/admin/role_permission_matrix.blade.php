@extends('layouts.app')

@section('content')
    @php
        $actionLabels = [
            'view' => 'مشاهده',
            'create' => 'ایجاد',
            'update' => 'ویرایش',
            'delete' => 'حذف',
            'reassign' => 'واگذاری مجدد',
            'export' => 'خروجی',
            'download' => 'دانلود',
            'manage' => 'مدیریت',
            'sales' => 'گزارش فروش',
            'finance' => 'گزارش مالی',
        ];
        $scopeLabels = \App\Helpers\FormOptionsHelper::permissionScopeLabels();
        // Helpers to localize role and module labels with fallback
        $roleLabel = function ($name) {
            $key = 'roles.' . $name;
            $t = __($key);
            if ($t !== $key) return $t;
            $key2 = 'roles.' . strtolower((string) $name);
            $t2 = __($key2);
            return $t2 !== $key2 ? $t2 : $name;
        };
        $moduleLabel = function ($module) {
            $key = 'modules.' . $module;
            $t = __($key);
            return $t !== $key ? $t : $module;
        };
    @endphp

    <div class="py-6" dir="rtl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-semibold text-xl text-gray-800">ماتریس دسترسی نقش‌ها</h2>

                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('roles.matrix') }}" class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">نقش:</label>
                        <select name="role_id" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}" @selected($role && $role->id === $r->id)>{{ $roleLabel($r->name) }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="dynamic" value="{{ $dynamic ? 1 : 0 }}">
                    </form>
                    <a class="px-3 py-1.5 border rounded text-sm" href="{{ route('roles.matrix.export', ['role_id' => $role?->id]) }}">خروجی JSON</a>
                    <form method="POST" action="{{ route('roles.matrix.import') }}" onsubmit="return confirm('Import JSON? این عمل مجوزهای نقش را جایگزین می‌کند.')">
                        @csrf
                        <input type="hidden" name="role_id" value="{{ $role?->id }}">
                        <input type="file" name="file" accept="application/json" class="hidden" id="jsonFileInput" onchange="importJsonFile(event)">
                        <button type="button" class="px-3 py-1.5 border rounded text-sm" onclick="document.getElementById('jsonFileInput').click()">درون‌ریزی JSON</button>
                    </form>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 text-green-800 p-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 text-red-800 p-3 text-sm">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg h-[calc(100vh-12rem)] overflow-hidden">
                <div class="p-4 h-full">
                    <form method="POST" action="{{ route('roles.matrix.store') }}" x-data="permissionMatrix()" x-init="init()" @submit="dirty=false" class="flex flex-col h-full min-h-0">
                        @csrf
                        <input type="hidden" name="role_id" value="{{ $role?->id }}">
                        <input type="hidden" name="version" value="{{ $version }}">
                        <input type="hidden" name="dynamic" value="{{ $dynamic ? 1 : 0 }}">

                        <div class="flex items-center gap-3 mb-3">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" class="rounded border-gray-300" x-model="hideEmpty"> مخفی‌سازی ستون‌های بدون مجوز
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                جست‌وجوی ماژول:
                                <input type="text" class="rounded border-gray-300 text-sm" x-model="query" placeholder="ماژول">
                            </label>
                        </div>

                        <div class="flex-1 min-h-0 overflow-x-auto overflow-y-auto">
                            <table class="min-w-full border divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-right text-sm font-semibold text-gray-700">ماژول</th>
                                    @foreach($actions as $action)
                                        <th class="px-3 py-2 text-center text-sm font-semibold text-gray-700" :class="{ 'hidden': hideEmpty && isEmptyColumn('{{ $action }}') }">
                                            <div class="flex items-center justify-center gap-2">
                                                <span>{{ $actionLabels[$action] ?? $action }}</span>
                                                <div class="relative" x-data="{open:false}">
                                                    <button type="button" class="px-1.5 py-0.5 text-xs rounded bg-gray-100 hover:bg-gray-200" @click="open = !open">همه</button>
                                                    <div class="absolute z-10 mt-1 w-36 bg-white border rounded shadow text-sm" x-show="open" @click.outside="open=false">
                                                        <button type="button" class="w-full text-right px-3 py-1.5 hover:bg-gray-50" @click="setColumn('{{ $action }}', '')">— پاک‌سازی</button>
                                                        <template x-for="opt in getColumnOptions('{{ $action }}')" :key="opt">
                                                            <button type="button" class="w-full text-right px-3 py-1.5 hover:bg-gray-50" @click="setColumn('{{ $action }}', opt)"><span x-text="label(opt)"></span></button>
                                                        </template>
                                                        <button type="button" class="w-full text-right px-3 py-1.5 hover:bg-gray-50" @click="setColumn('{{ $action }}', true)">فعال (چک‌باکس)</button>
                                                        <button type="button" class="w-full text-right px-3 py-1.5 hover:bg-gray-50" @click="setColumn('{{ $action }}', false)">غیرفعال (چک‌باکس)</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100" x-ref="tbody">
                                @foreach($modules as $module)
                                    <tr class="hover:bg-gray-50" x-show="match('{{ $module }}')">
                                        <td class="px-3 py-2 text-sm text-gray-800 whitespace-nowrap">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-mono">{{ $moduleLabel($module) }}</span>
                                                <div class="relative" x-data="{open:false}">
                                                    <button type="button" class="px-1.5 py-0.5 text-xs rounded bg-gray-100 hover:bg-gray-200" @click="open = !open">ردیف</button>
                                                    <div class="absolute left-0 z-10 mt-1 w-36 bg-white border rounded shadow text-sm" x-show="open" @click.outside="open=false">
                                                        <button type="button" class="w-full text-right px-3 py-1.5 hover:bg-gray-50" @click="setRow('{{ $module }}', '')">— پاک‌سازی</button>
                                                        <template x-for="opt in getRowOptions('{{ $module }}')" :key="opt">
                                                            <button type="button" class="w-full text-right px-3 py-1.5 hover:bg-gray-50" @click="setRow('{{ $module }}', opt)"><span x-text="label(opt)"></span></button>
                                                        </template>
                                                        <button type="button" class="w-full text-right px-3 py-1.5 hover:bg-gray-50" @click="setRow('{{ $module }}', true)">فعال (چک‌باکس)</button>
                                                        <button type="button" class="w-full text-right px-3 py-1.5 hover:bg-gray-50" @click="setRow('{{ $module }}', false)">غیرفعال (چک‌باکس)</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        @foreach($actions as $action)
                                            @php($def = $definitions[$module][$action] ?? null)
                                            <td class="px-3 py-2 text-center text-sm whitespace-nowrap" :class="{ 'hidden': hideEmpty && isEmptyColumn('{{ $action }}') }">
                                                @if(!$def)
                                                    <span class="text-gray-300">—</span>
                                                @elseif($def['type'] === 'boolean')
                                                    <input type="checkbox"
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                           name="perm[{{ $module }}.{{ $action }}]"
                                                           data-module="{{ $module }}"
                                                           data-action="{{ $action }}"
                                                           @checked(($current[$module][$action] ?? false) === true)
                                                           @change="dirty=true"
                                                    >
                                                @else
                                                    <select name="perm[{{ $module }}.{{ $action }}]"
                                                            class="rounded border-gray-300 text-sm"
                                                            data-module="{{ $module }}"
                                                            data-action="{{ $action }}" @change="dirty=true">
                                                        <option value="">—</option>
                                                        @foreach($def['options'] as $opt)
                                                            <option value="{{ $opt }}" @selected(($current[$module][$action] ?? null) === $opt)>{{ $scopeLabels[$opt] ?? $opt }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-3">
                            <a href="{{ route('admin.role-permissions') }}" class="px-3 py-2 rounded border text-sm">گزارش</a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">ذخیره تغییرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('beforeunload', function (e) {
            const fm = document.querySelector('form[action*="roles/matrix"]');
            if (fm && fm.__vm && fm.__vm.dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        async function importJsonFile(evt) {
            const file = evt.target.files[0];
            if (!file) return;
            const text = await file.text();
            let payload;
            try { payload = JSON.parse(text); } catch (e) { alert('JSON نامعتبر است'); return; }
            const form = evt.target.closest('form');
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'permissions[]';
            // clear previous
            form.querySelectorAll('input[name="permissions[]"]').forEach(el => el.remove());
            if (Array.isArray(payload.permissions)) {
                payload.permissions.forEach(name => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'permissions[]'; i.value = name; form.appendChild(i);
                });
            } else if (Array.isArray(payload)) {
                payload.forEach(name => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'permissions[]'; i.value = name; form.appendChild(i);
                });
            } else {
                alert('JSON باید شامل آرایه permissions باشد'); return;
            }
            if (payload.version) {
                const v = document.createElement('input'); v.type = 'hidden'; v.name = 'version'; v.value = payload.version; form.appendChild(v);
            }
            form.submit();
        }
        function permissionMatrix() {
            const labelMap = @json($scopeLabels);
            return {
                dirty: false,
                query: '',
                hideEmpty: false,
                init() { this.$root.__vm = this; },
                match(module) { return this.query.trim() === '' || module.toLowerCase().includes(this.query.toLowerCase()); },
                label(val) {
                    if (val === '' || val === '-') return '—';
                    if (val === true) return 'فعال';
                    if (val === false) return 'غیرفعال';
                    return labelMap[val] ?? val;
                },
                // Set all controls in a column (action)
                setColumn(action, value) {
                    // selects
                    document.querySelectorAll('select[data-action="' + action + '"]').forEach((el) => {
                        if (typeof value === 'string') el.value = value; // scope or ''
                    });
                    // checkboxes
                    document.querySelectorAll('input[type="checkbox"][data-action="' + action + '"]').forEach((el) => {
                        if (typeof value === 'boolean') el.checked = value;
                        if (value === '' || value === '-') el.checked = false;
                    });
                },
                // Set all controls in a row (module)
                setRow(module, value) {
                    document.querySelectorAll('[data-module="' + module + '"]').forEach((el) => {
                        if (el.tagName === 'SELECT' && typeof value === 'string') {
                            el.value = value;
                        } else if (el.type === 'checkbox') {
                            if (typeof value === 'boolean') el.checked = value;
                            if (value === '' || value === '-') el.checked = false;
                        }
                    });
                },
                // Helper: which scoped options exist for a column
                getColumnOptions(action) {
                    const opts = new Set();
                    document.querySelectorAll('select[data-action="' + action + '"] option').forEach((o) => {
                        if (o.value) opts.add(o.value);
                    });
                    return Array.from(opts);
                },
                // Helper: options present in a row
                getRowOptions(module) {
                    const opts = new Set();
                    document.querySelectorAll('select[data-module="' + module + '"] option').forEach((o) => {
                        if (o.value) opts.add(o.value);
                    });
                    return Array.from(opts);
                },
                // Is a column completely empty (no select/checkbox definitions)?
                isEmptyColumn(action) {
                    const hasSelect = document.querySelector('select[data-action="' + action + '"]') !== null;
                    const hasCb = document.querySelector('input[type="checkbox"][data-action="' + action + '"]') !== null;
                    return !(hasSelect || hasCb);
                },
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const importForm = document.querySelector('form[action*="roles.matrix.import"]');
            if (importForm) {
                importForm.setAttribute('onsubmit', "return confirm('درون‌ریزی JSON؟ این عمل مجوزهای نقش را جایگزین می‌کند.')");
            }
        });
        // Override importJsonFile with fully Persian messages
        async function importJsonFile(evt) {
            const file = evt.target.files[0];
            if (!file) return;
            const text = await file.text();
            let payload;
            try { payload = JSON.parse(text); } catch (e) { alert('JSON نامعتبر است'); return; }
            const form = evt.target.closest('form');
            // clear previous
            form.querySelectorAll('input[name="permissions[]"]').forEach(el => el.remove());
            if (Array.isArray(payload.permissions)) {
                payload.permissions.forEach(name => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'permissions[]'; i.value = name; form.appendChild(i);
                });
            } else if (Array.isArray(payload)) {
                payload.forEach(name => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'permissions[]'; i.value = name; form.appendChild(i);
                });
            } else {
                alert('ساختار JSON نامعتبر است؛ کلید permissions یافت نشد.'); return;
            }
            if (payload.version) {
                const v = document.createElement('input'); v.type = 'hidden'; v.name = 'version'; v.value = payload.version; form.appendChild(v);
            }
            form.submit();
        }
    </script>
@endsection
