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

    public function create()
    {
        $organizations = Organization::all();
        $contacts = Contact::all();
        $users = User::all();

        return view('sales.opportunities.create', compact('organizations', 'contacts', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'type' => 'required|string|in:کسب و کار موجود,کسب و کار جدید',
            'source' => 'required|string|in:وب سایت,مشتریان قدیمی,نمایشگاه,بازاریابی حضوری',
            'assigned_to' => 'nullable|exists:users,id',
            'success_rate' => 'required|numeric|min:0|max:100',
            'amount' => 'required|numeric|min:0',
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
        ['title' => $opportunity->name ?? 'فرصت #' . $opportunity->id],
    ];

    return view('sales.opportunities.show', compact('opportunity', 'breadcrumb'));
}


    public function edit(Opportunity $opportunity)
    {
        $organizations = Organization::all();
        $contacts = Contact::all();
        $users = User::all();

        return view('sales.opportunities.edit', compact('opportunity', 'organizations', 'contacts', 'users'));
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'type' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'success_rate' => 'nullable|numeric|min:0|max:100',
            'amount' => 'nullable|numeric|min:0',
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
        $view = 'sales.opportunities.tabs.' . $tab;

        if (!View::exists($view)) {
            abort(404);
        }

        if ($tab === 'updates') {
            $activities = $opportunity->activities()->latest()->get();


            return view($view, compact('opportunity', 'activities'));
        }

        return view($view, compact('opportunity'));
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
