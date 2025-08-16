@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'تنظیمات'],
        ['title' => 'اتوماسیون تأیید']
    ];

    // با معماری ستون‌های تکی
    $approver1 = old('approver_1', $rule->approver_1 ?? null);
    $approver2 = old('approver_2', $rule->approver_2 ?? null);
    $emergency = old('emergency_approver_id', $rule->emergency_approver_id ?? null);
@endphp

<div class="automation-container">
    <div class="automation-title">تنظیمات اتوماسیون تأیید پیش‌فاکتور</div>

    @if(session('success'))
        <div class="automation-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="automation-error">
            <ul style="margin:0; padding-inline-start: 18px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('settings.automation.update') }}" method="POST">
        @csrf

        <div class="automation-form-row">
            <div>
                <label>شرط</label>
                <input type="text" value="مرحله پیش‌فاکتور" readonly>
            </div>

            <div>
                <label>عملگر</label>
                <select name="operator">
                    <option value="="  @selected(old('operator', $rule->operator ?? '=') === '=')>=</option>
                    <option value="!=" @selected(old('operator', $rule->operator ?? '=') === '!=')>≠</option>
                </select>
            </div>

            <div>
                <label>مقدار</label>
                <select name="value">
                    @foreach($stages as $val => $label)
                        <option value="{{ $val }}" @selected(old('value', $rule->value ?? 'send_for_approval') == $val)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="approvers-container">
            <label style="font-weight:600; color:#333; margin-bottom:8px;">تأییدکننده‌ها</label>

            <div id="approver-fields">
                <div class="approver-row" style="margin-bottom:10px;">
                    <label for="approver_1">تأییدکننده اول</label>
                    <select name="approver_1" id="approver_1" class="approver-select">
                        <option value="">انتخاب کاربر</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string)$approver1 === (string)$user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="approver-row" style="margin-bottom:10px;">
                    <label for="approver_2">تأییدکننده دوم</label>
                    <select name="approver_2" id="approver_2" class="approver-select">
                        <option value="">انتخاب کاربر</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string)$approver2 === (string)$user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="approver-row" style="margin-bottom:10px;">
                    <label for="emergency_approver_id">تأییدکننده جایگزین (ادمین)</label>
                    <select name="emergency_approver_id" id="emergency_approver_id" class="approver-select">
                        <option value="">— انتخاب کنید —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((string)$emergency === (string)$u->id)>
                                {{ $u->name }} @if(method_exists($u,'role') && $u->role) ({{ $u->role->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs" style="color:#6b7280; margin-top:6px;">این نفر می‌تواند به‌جای هر دو مرحله، تأیید نهایی را انجام دهد.</p>
                </div>
            </div>
        </div>

        <button type="submit" class="automation-submit-btn">ذخیره تنظیمات</button>
    </form>

    <form action="{{ route('settings.automation.destroyAll') }}" method="POST" style="display:inline-block; margin-right: 10px;">
        @csrf
        @method('DELETE')
        <button type="submit" class="automation-submit-btn" style="background-color:#e53935;">حذف قانون</button>
    </form>

    <h3 style="margin-top: 38px; color:#1976d2; text-align:center; font-size:1.1rem;">قانون فعلی</h3>
    <table class="automation-table">
        <thead>
            <tr>
                <th>#</th>
                <th>شرط</th>
                <th>عملگر</th>
                <th>مقدار</th>
                <th>تأییدکننده اول</th>
                <th>تأییدکننده دوم</th>
                <th>ادمین جایگزین</th>
            </tr>
        </thead>
        <tbody>
        @forelse(($rules ?? collect()) as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>مرحله پیش‌فاکتور</td>
                    <td>{{ $item->operator }}</td>
                    <td>{{ $stages[$item->value] ?? $item->value }}</td>
                    <td>{{ optional($item->approvers->first())->user->name ?? '—' }}</td>
                    <td>{{ optional($item->approvers->get(1))->user->name ?? '—' }}</td>
                    <td>{{ optional($item->emergencyApprover)->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">قانونی ثبت نشده است.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('style')
<style>
.automation-container {
    max-width: 760px;
    margin: 40px auto;
    padding: 30px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
    font-family: "IRANSans", sans-serif;
}
.automation-title {
    font-size: 1.35rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 24px;
    text-align: center;
}
.automation-success {
    background-color: #e0f7e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}
.automation-error {
    background-color: #fdecea;
    color: #c62828;
    border: 1px solid #f5c6cb;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: right;
}
.automation-form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 24px;
    justify-content: space-between;
}
.automation-form-row > div,
.approver-row {
    flex: 1 1 220px;
    display: flex;
    flex-direction: column;
}
.automation-form-row label,
.approvers-container label,
.approver-row label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}
.automation-form-row select,
.automation-form-row input[type="text"],
.approver-select {
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 0.95rem;
    background-color: #f9fafb;
    transition: border-color 0.2s;
}
.automation-form-row select:focus,
.automation-form-row input[type="text"]:focus,
.approver-select:focus {
    border-color: #1976d2;
    background-color: #fff;
    outline: none;
}
.approvers-container {
    margin-bottom: 24px;
}
#approver-fields .approver-row { margin-bottom: 16px; }
#approver-fields .approver-select { width: 100%; }
.automation-submit-btn {
    background-color: #1976d2;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-left: 10px;
}
.automation-submit-btn:hover { background-color: #0d47a1; }
.automation-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.automation-table th,
.automation-table td {
    border: 1px solid #ddd;
    padding: 10px 12px;
    font-size: 0.9rem;
    text-align: center;
}
.automation-table th { background-color: #f2f2f2; color: #333; }
.automation-table td { background-color: #fafafa; }
</style>
@endpush
