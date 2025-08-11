<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesLead as Lead;
use Spatie\Activitylog\Models\Activity;

class LeadController extends Controller
{
    public function show(Lead $lead)
    {
        // شمارش یادداشت‌ها را به مدل تزریق‌شده اضافه کن
        $lead->loadCount('notes'); // اگر لازم بود: ->loadCount(['notes','opportunities'])
        return view('marketing.leads.show', compact('lead'));
    }

    public function loadTab(Lead $lead, $tab)
    {
        // امنه که همیشه اینو لود کنیم (حجمش ناچیزه) تا تو هر تب به notes_count دسترسی داشته باشی
        $lead->loadCount('notes');

        switch ($tab) {
            case 'overview':
                return view('marketing.leads.tabs.overview', compact('lead'));

            case 'info':
                return view('marketing.leads.tabs.info', compact('lead'));

            case 'updates':
                $activities = Activity::where('subject_type', \App\Models\SalesLead::class)
                    ->where('subject_id', $lead->id)
                    ->latest()
                    ->get();

                return view('marketing.leads.tabs.updates', compact('lead', 'activities'));

            case 'related':
                $opportunities = $lead->opportunities ?? [];
                return view('marketing.leads.tabs.related', compact('lead', 'opportunities'));

            case 'notes':
                $notes = $lead->notes()->latest()->get();

                $allUsers = \App\Models\User::select('id', 'name', 'username')
                    ->whereNotNull('username')
                    ->where('id', '!=', auth()->id())
                    ->get();

                return view('marketing.leads.tabs.notes', compact('lead', 'notes', 'allUsers'));

            default:
                abort(404);
        }
    }
}
