<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        // مطمئن می‌شیم فقط کاربر لاگین‌کرده دسترسی داره
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        // ۱۰ اعلان آخر کاربر
        $notifications = $user->notifications()
            ->latest()     // مرتب‌سازی بر اساس created_at
            ->take(10)
            ->get();

        return view('dashboard', compact('notifications'));
    }
}
