<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesLead as Lead;
use Spatie\Activitylog\Models\Activity;


class LeadController extends Controller
{
    //
    public function show(Lead $lead)
{
    return view('marketing.leads.show', compact('lead'));
}


//
public function loadTab(Lead $lead, $tab)
{
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
            // فرض می‌کنیم Lead رابطه opportunities داشته باشه
            $opportunities = $lead->opportunities ?? [];
            return view('marketing.leads.tabs.related', compact('lead', 'opportunities'));

            case 'notes':
                $notes = $lead->notes()->latest()->get();
            
                // اضافه کردن لیست کاربران برای انتخاب در فرم منشن
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

