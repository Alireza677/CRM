<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppSetting;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    /**
     * نمایش صفحه تنظیمات عمومی
     */
    public function general()
    {
        $assetsEmergency = AppSetting::getBool('assets_emergency', config('app.assets_emergency'));

        return view('settings.general', compact('assetsEmergency'));
    }

    public function updateAssetsEmergency(Request $request)
    {
        $validated = $request->validate([
            'assets_emergency' => ['nullable', 'boolean'],
        ]);

        $value = (bool) ($validated['assets_emergency'] ?? false);

        AppSetting::setValue('assets_emergency', $value ? '1' : '0');
        config(['app.assets_emergency' => $value]);

        return back()->with('success', 'وضعیت حالت اضطراری به‌روزرسانی شد.');
    }
}
