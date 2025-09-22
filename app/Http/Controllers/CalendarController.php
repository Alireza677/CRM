<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    // فید ایونت برای تقویم
    public function events(Request $request)
    {
        $user  = $request->user();
        $start = $request->query('start'); // ISO8601
        $end   = $request->query('end');

        $q = Activity::query()->visibleTo($user);

        if ($start) $q->where('start_at', '>=', $start);
        if ($end)   $q->where(function($qq) use ($end) {
            $qq->whereNull('due_at')->orWhere('due_at', '<=', $end);
        });

        return response()->json($q->get()->map->toCalendarEvent()->values());
    }
}
