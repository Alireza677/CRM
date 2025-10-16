<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Opportunity;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Spatie\Activitylog\Models\Activity;
use Morilog\Jalali\Jalalian;
use Illuminate\Validation\Rule;
use App\Helpers\FormOptionsHelper;


class OpportunityController extends Controller
{
    public function index(Request $request)
{
    $query = Opportunity::with(['contact', 'assignedUser', 'organization']);

    if ($request->filled('name')) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    if ($request->filled('contact')) {
        $query->whereHas('contact', function ($q) use ($request) {
            $q->where('first_name', 'like', '%' . $request->contact . '%')
              ->orWhere('last_name', 'like', '%' . $request->contact . '%')
              ->orWhereRaw("(first_name || ' ' || last_name) LIKE ?", ['%' . $request->contact . '%']);
        });
    }

    if ($request->filled('source')) {
        $query->where('source', 'like', '%' . $request->source . '%');
    }

    if ($request->filled('building_usage')) {
        $query->where('building_usage', 'like', '%' . $request->building_usage . '%');
    }

    if ($request->filled('assigned_to')) {
        $query->whereHas('assignedUser', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->assigned_to . '%');
        });
    }

    if ($request->filled('stage')) {
        $query->where('stage', 'like', '%' . $request->stage . '%');
    }

    if ($request->filled('created_at')) {
        $query->whereDate('created_at', $request->created_at);
    }

    $opportunities = $query->latest()->paginate(15)->withQueryString();

    return view('sales.opportunities.index', compact('opportunities'));
}



    public function create(Request $request)
    {
        $organizations = Organization::all();
        $contacts = Contact::all();
        $users = User::all();

        $contactId = $request->input('contact_id');
        $defaultContact = $contactId ? Contact::find($contactId) : null;

        return view('sales.opportunities.create', compact(
            'organizations',
            'contacts',
            'users',
            'defaultContact'
        ));
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'organization_id' => 'nullable|exists:organizations,id',
        'contact_id' => 'nullable|exists:contacts,id',
        'type' => 'required|string|in:کسب و کار موجود,کسب و کار جدید',
        'source' => 'required|string|max:255',
        'building_usage' => 'required|string|in:کارگاه و یا کارخانه,فضای باز و رستوران,تعمیرگاه و سالن صنعتی,گلخانه و پرورش گیاه,مرغداری و پرورش دام و طیور,فروشگاه و مراکز خرید,سالن و باشگاه های ورزشی,سالن های نمایش,مدارس و محیط های آموزشی,سایر',
        'assigned_to' => 'nullable|exists:users,id',
        'success_rate' => 'required|numeric|min:0|max:100',
        'next_follow_up' => 'required|date',
        'description' => 'nullable|string',
        'stage' => 'nullable|string|max:255',
    ]);
    

    $opportunity = Opportunity::create($validated);
    $opportunity->notifyIfAssigneeChanged(null);

    return redirect()->route('sales.opportunities.index')
        ->with('success', 'فرصت فروش با موفقیت ایجاد شد.');
}


    
public function show(Opportunity $opportunity)
{
    $breadcrumb = [
        ['title' => 'خانه', 'url' => url('/dashboard')],
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => $opportunity->name ?? ('فرصت #' . $opportunity->id)],
    ];

    $activities = Activity::where('subject_type', Opportunity::class)
        ->where('subject_id', $opportunity->id)
        ->latest()
        ->get();

    $opportunity->load([
        'proformas' => function ($q) use ($opportunity) {
            $q->select(
                'id',
                'opportunity_id',
                'proforma_number',
                'proforma_date',
                'approval_stage',
                'proforma_stage',
                'total_amount'
            )
            ->where('opportunity_id', $opportunity->id)
            ->orderByDesc('proforma_date');
        },
    ]);

    return view('sales.opportunities.show', compact('opportunity', 'breadcrumb', 'activities'));
}


public function edit(Opportunity $opportunity)
{
    $opportunity->loadMissing(['organization', 'contact']);

    $organizations = Organization::orderBy('name')->get(['id','name','phone']);
    $contacts      = Contact::orderBy('last_name')->get(['id','first_name','last_name','mobile']);
    $users         = User::orderBy('name')->get(['id','name']);

    $nextFollowUpDate = '';
    if (!empty($opportunity->next_follow_up)) {
        try {
            $nextFollowUpDate = \Morilog\Jalali\Jalalian::fromDateTime($opportunity->next_follow_up)->format('Y/m/d');
        } catch (\Throwable $e) {
            $nextFollowUpDate = '';
        }
    }

    return view('sales.opportunities.edit', compact(
        'opportunity', 'organizations', 'contacts', 'users', 'nextFollowUpDate'
    ));
}

    


public function update(Request $request, Opportunity $opportunity)
{
    if ($request->filled('source')) {
        $request->merge([
            'source' => \App\Helpers\FormOptionsHelper::getLeadSourceLabel($request->input('source')),
        ]);
    }
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'organization_id' => 'nullable|exists:organizations,id',
        'contact_id' => 'nullable|exists:contacts,id',
        'type' => 'nullable|string|max:255',
        'source' => 'nullable|string|max:255',
        'building_usage' => 'nullable|string|in:کارگاه و یا کارخانه,فضای باز و رستوران,تعمیرگاه و سالن صنعتی,گلخانه و پرورش گیاه,مرغداری و پرورش دام و طیور,فروشگاه و مراکز خرید,سالن و باشگاه های ورزشی,سالن های نمایش,مدارس و محیط های آموزشی,سایر',
        'assigned_to' => 'nullable|exists:users,id',
        'success_rate' => 'nullable|numeric|min:0|max:100',
        'next_follow_up' => 'nullable|date',
        'description' => 'nullable|string',
        'stage' => 'nullable|string|max:255',
    ]);

    $oldAssignedTo = $opportunity->assigned_to;

    $opportunity->update($validated);
    $opportunity->notifyIfAssigneeChanged($oldAssignedTo);

    return redirect()->route('sales.opportunities.show', $opportunity)
        ->with('success', 'فرصت فروش با موفقیت بروزرسانی شد.');
}


    public function documents()
    {
        return $this->hasMany(Document::class);
    }


    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();

        return redirect()->route('sales.opportunities.index')
            ->with('success', 'فرصت فروش با موفقیت حذف شد.');
    }

    public function loadTab(Opportunity $opportunity, $tab)
{
    $view = "sales.opportunities.tabs.$tab";

    if (!view()->exists($view)) {
        abort(404);
    }

    $data = ['opportunity' => $opportunity];

    // برای تب یادداشت‌ها، کاربران را لود کن
    if ($tab === 'notes') {
        $data['allUsers'] = \App\Models\User::whereNotNull('username')->get();
    }

    // برای تب بروزرسانی‌ها، فعالیت‌ها را لود کن
    if ($tab === 'updates') {
        $data['activities'] = \Spatie\Activitylog\Models\Activity::where('subject_type', \App\Models\Opportunity::class)
            ->where('subject_id', $opportunity->id)
            ->latest()
            ->get();
    }

    return view($view, $data);
}

    
    public function ajaxTab(Opportunity $opportunity, $tab)
    {
        switch ($tab) {
            case 'info':
                return view('sales.opportunities.tabs.info', compact('opportunity'));
            case 'notes':
                return view('sales.opportunities.tabs.notes', compact('opportunity'));
            case 'calls':
                return view('sales.opportunities.tabs.calls', compact('opportunity'));
            case 'updates':
                $activities = Activity::where('subject_type', Opportunity::class)
                    ->where('subject_id', $opportunity->id)
                    ->latest()
                    ->get();
                return view('sales.opportunities.tabs.updates', compact('opportunity', 'activities'));
            case 'activities':
                return view('sales.opportunities.tabs.activities', compact('opportunity'));
                case 'proformas':
                    $opportunity->load('proformas');
                    return view('sales.opportunities.tabs.proformas', compact('opportunity'));
                
            case 'approvals':
                return view('sales.opportunities.tabs.approvals', compact('opportunity'));
            case 'documents':
                $opportunity->load('documents');
                return view('sales.opportunities.tabs.documents', compact('opportunity'));
                case 'contacts':
                $opportunity->load('contact', 'contact.organization');
                return view('sales.opportunities.tabs.contacts', compact('opportunity'));
                
            case 'orders':
                return view('sales.opportunities.tabs.orders', compact('opportunity'));
            default:
                abort(404);
        }
    }
}
