<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::query()->orderByDesc('date')->paginate(20);
        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'   => ['required','string'], // accepts Jalali (Y/m/d) or Gregorian (Y-m-d)
            'title'  => ['nullable','string','max:255'],
            'notify' => ['sometimes','boolean'],
        ]);

        $data['notify'] = (bool)($data['notify'] ?? false);
        $data['date'] = $this->parseDateToYmd($data['date']);
        $data['created_by_id'] = Auth::id();

        Holiday::create($data);

        return back()->with('status', 'تعطیلی ثبت شد.');
    }

    public function edit(Holiday $holiday)
    {
        return view('holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $data = $request->validate([
            'date'   => ['required','string'],
            'title'  => ['nullable','string','max:255'],
            'notify' => ['sometimes','boolean'],
        ]);

        $data['notify'] = (bool)($data['notify'] ?? false);
        $data['date'] = $this->parseDateToYmd($data['date']);

        $holiday->update($data);

        return redirect()->route('holidays.index')->with('status', 'ویرایش ذخیره شد.');
    }

    protected function parseDateToYmd(string $value): string
    {
        $v = $this->toEnDigits(trim($value));
        // Try Gregorian Y-m-d first
        try {
            return Carbon::createFromFormat('Y-m-d', $v)->toDateString();
        } catch (\Throwable $e) { /* continue */ }

        // Accept Jalali Y/m/d (with '-' accepted)
        $v2 = str_replace('-', '/', $v);
        try {
            return Jalalian::fromFormat('Y/m/d', $v2)->toCarbon()->toDateString();
        } catch (\Throwable $e) { /* continue */ }

        // Fallback to Carbon parse if very permissive input provided
        return Carbon::parse($v)->toDateString();
    }

    protected function toEnDigits(?string $s): ?string
    {
        if ($s === null) return null;
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($ar, $en, str_replace($fa, $en, $s));
    }
}

