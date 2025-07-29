<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(10);
        return view('notifications.index', compact('notifications'));
    }

    public function read($notificationId)
    {
        $notification = auth()->user()->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();
        return redirect(Arr::get($notification->data, 'url', route('notifications.index')));
    }

    public function markAllAsRead()
{
    auth()->user()->unreadNotifications->markAsRead();

    return redirect()->route('notifications.index')->with('success', 'همه اعلان‌ها خوانده شدند.');
}
public function bulkAction(Request $request)
{
    $ids = $request->input('selected', []);
    $action = $request->input('action');

    $notifications = auth()->user()->notifications()->whereIn('id', $ids)->get();

    if ($action === 'markAsRead') {
        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }
        return back()->with('success', 'اعلان‌ها به عنوان خوانده‌شده علامت‌گذاری شدند.');
    }

    if ($action === 'delete') {
        foreach ($notifications as $notification) {
            $notification->delete();
        }
        return back()->with('success', 'اعلان‌های انتخاب‌شده حذف شدند.');
    }

    return back()->with('error', 'هیچ عملیاتی انتخاب نشد.');
}

}
