<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\SalesLead;
use App\Models\Opportunity;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        // Notifications
        $notifications = $user->notifications()
            ->latest()
            ->take(10)
            ->get();

        // Incomplete activities (tasks)
        $tasks = Activity::where('status', '!=', 'completed')
            ->where('assigned_to_id', $user->id)
            ->orderBy('due_at', 'asc')
            ->take(10)
            ->get();

        // Today's follow-ups (from leads + opportunities assigned to the user)
        $today = now()->toDateString();

        $leadFollowUps = SalesLead::query()
            ->where('assigned_to', $user->id)
            ->whereDate('next_follow_up_date', $today)
            ->get()
            ->map(function (SalesLead $lead) {
                return [
                    'type'  => 'lead',
                    'title' => method_exists($lead, 'getNotificationTitle') ? $lead->getNotificationTitle() : ($lead->full_name ?: ($lead->company ?: (__('Lead') . ' #' . $lead->id))),
                    'date'  => $lead->next_follow_up_date,
                    'url'   => route('marketing.leads.show', $lead->id),
                ];
            });

        $opFollowUps = Opportunity::query()
            ->where('assigned_to', $user->id)
            ->whereDate('next_follow_up', $today)
            ->get()
            ->map(function (Opportunity $opportunity) {
                return [
                    'type'  => 'opportunity',
                    'title' => method_exists($opportunity, 'getNotificationTitle') ? $opportunity->getNotificationTitle() : ($opportunity->name ?: (__('Opportunity') . ' #' . $opportunity->id)),
                    'date'  => $opportunity->next_follow_up,
                    'url'   => route('sales.opportunities.show', $opportunity->id),
                ];
            });

        $todayFollowUps = $leadFollowUps->concat($opFollowUps)->sortBy('date')->values();

        return view('dashboard', compact('notifications', 'tasks', 'todayFollowUps'));
    }
}