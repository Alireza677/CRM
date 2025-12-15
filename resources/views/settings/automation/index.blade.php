@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'OAÝU+O,UOU.OOAÝ'],
        ['title' => 'OOAÝU^U.OO3UOU^U+ OAÝO"UOUOO_']
    ];

    $approver1 = old('approver_1', $rule->approver_1 ?? null);
    $approver2 = old('approver_2', $rule->approver_2 ?? null);
    $emergency = old('emergency_approver_id', $rule->emergency_approver_id ?? null);
@endphp

<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">OAÝU+O,UOU.OOAÝ OOAÝU^U.OO3UOU^U+ OAÝO"UOUOO_ U_UOO'’'?OU?OUcOAÝU^OAñ</h1>
            <p class="mt-1 text-sm text-gray-600">O"O OAÝO"UOUOO_UcU+U+O_UA~ O+U.OAÝO3 U?OAñU. O"UA~ O1U.U,U_OAñUO OOA_O>OAø O"O3OUA«.</p>
        </div>
        <a href="{{ route('settings.automation.leads.roundrobin.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-2 font-semibold text-indigo-700 hover:bg-indigo-100">
            Round-Robin U?O1U,UO
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">OAÝU+O,UOU.OOAÝ</h2>
            <p class="mt-1 text-sm text-gray-600">O"O U?U,OA§ UOUc U,OU+U^U+ U"O3O" UcU+O_UA~ O"O U_UOO'’'?OU?OUcOAÝU^OAñ O_OAñ.</p>
        </div>

        <div class="px-6 py-5 space-y-4">
            <form action="{{ route('settings.automation.update') }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col space-y-2">
                        <label class="text-sm font-medium text-gray-700">O'OAñOA§</label>
                        <input type="text" value="U.OAñO-U,UA~ U_UOO'’'?OU?OUcOAÝU^OAñ" readonly class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                    </div>

                    <div class="flex flex-col space-y-2">
                        <label class="text-sm font-medium text-gray-700">O1U.U,U_OAñ</label>
                        <select name="operator" class="rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="="  @selected(old('operator', $rule->operator ?? '=') === '=')>=</option>
                            <option value="!=" @selected(old('operator', $rule->operator ?? '=') === '!=')>’'%A¨</option>
                        </select>
                    </div>

                    <div class="flex flex-col space-y-2">
                        <label class="text-sm font-medium text-gray-700">U.U,O_OOAñ</label>
                        <select name="value" class="rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($stages as $val => $label)
                                <option value="{{ $val }}" @selected(old('value', $rule->value ?? 'send_for_approval') == $val)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-sm font-semibold text-gray-800">OAÝO"UOUOO_UcU+U+O_UA~’'?OUA~O</label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col space-y-2">
                            <label for="approver_1" class="text-sm font-medium text-gray-700">OAÝO"UOUOO_UcU+U+O_UA~ OU^U,</label>
                            <select name="approver_1" id="approver_1" class="rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">OU+OAÝOrOO" UcOOAñO"OAñ</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected((string)$approver1 === (string)$user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col space-y-2">
                            <label for="approver_2" class="text-sm font-medium text-gray-700">OAÝO"UOUOO_UcU+U+O_UA~ O_U^U.</label>
                            <select name="approver_2" id="approver_2" class="rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">OU+OAÝOrOO" UcOOAñO"OAñ</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected((string)$approver2 === (string)$user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col space-y-2 md:col-span-2">
                            <label for="emergency_approver_id" class="text-sm font-medium text-gray-700">OAÝO"UOUOO_UcU+U+O_UA~ OA¦OUOU_OA«UOU+ (OO_U.UOU+)</label>
                            <select name="emergency_approver_id" id="emergency_approver_id" class="rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">’'?" OU+OAÝOrOO" UcU+UOO_ ’'?"</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" @selected((string)$emergency === (string)$u->id)>
                                        {{ $u->name }} @if(method_exists($u,'role') && $u->role) ({{ $u->role->name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500">OUOU+ U+U?OAñ U.UO’'?OOAÝU^OU+O_ O"UA~’'?OOA¦OUO UA~OAñ O_U^ U.OAñO-U,UA~OO OAÝO"UOUOO_ U+UA~OUOUO OAñO OU+OA¦OU. O_UA~O_.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        OA,OrUOOAñUA~ OAÝU+O,UOU.OOAÝ
                    </button>
                </div>
            </form>

            <form action="{{ route('settings.automation.destroyAll') }}" method="POST" class="inline-flex">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    O-OA,U? U,OU+U^U+
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">U,OU+U^U+ U?O1U,UO</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">#</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">O'OAñOA§</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">O1U.U,U_OAñ</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">U.U,O_OOAñ</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">OAÝO"UOUOO_UcU+U+O_UA~ OU^U,</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">OAÝO"UOUOO_UcU+U+O_UA~ O_U^U.</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">OO_U.UOU+ OA¦OUOU_OA«UOU+</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse(($rules ?? collect()) as $item)
                        <tr class="bg-white">
                            <td class="px-4 py-3 text-gray-700">{{ $item->id }}</td>
                            <td class="px-4 py-3 text-gray-800">U.OAñO-U,UA~ U_UOO'’'?OU?OUcOAÝU^OAñ</td>
                            <td class="px-4 py-3 text-gray-800">{{ $item->operator }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $stages[$item->value] ?? $item->value }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ optional($item->approvers->first())->user->name ?? '’'?"' }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ optional($item->approvers->get(1))->user->name ?? '’'?"' }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ optional($item->emergencyApprover)->name ?? '’'?"' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-600">U,OU+U^U+UO OArO"OAÝ U+O'O_UA~ OO3OAÝ.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
