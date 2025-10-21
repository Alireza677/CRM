<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        return view('settings.general');
    }
}
