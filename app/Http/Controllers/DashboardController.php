<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;   // 👈 اینو اضافه کن

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
            ->latest()
            ->take(10)
            ->get();

        // ۱۰ وظیفه ناقص (مثلاً status ≠ completed)
        $tasks = Activity::where('status', '!=', 'completed')
            ->where('assigned_to_id', $user->id)   // 👈 فقط وظایف مربوط به کاربر جاری
            ->orderBy('due_at', 'asc')
            ->take(10)
            ->get();

        return view('dashboard', compact('notifications', 'tasks'));
    }
}
