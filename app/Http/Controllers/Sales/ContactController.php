<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Opportunity;
use App\Models\Organization;



class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query()
            ->select('contacts.*', 'organizations.name as organization_name', 'users.name as assigned_to_name')
            ->leftJoin('organizations', 'contacts.organization_id', '=', 'organizations.id')
            ->leftJoin('users', 'contacts.assigned_to', '=', 'users.id');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contacts.first_name', 'like', "%{$search}%")
                  ->orWhere('contacts.last_name', 'like', "%{$search}%")
                  ->orWhere('contacts.mobile', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Handle special cases for sorting
        if ($sortField === 'organization_name') {
            $query->orderBy('organizations.name', $sortDirection);
        } elseif ($sortField === 'assigned_to_name') {
            $query->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy("contacts.{$sortField}", $sortDirection);
        }

        $contacts = $query->paginate(10)->withQueryString();

        return view('sales.contacts.index', compact('contacts'));
    }

    public function create(Request $request)
    {
        $organizations = \App\Models\Organization::all();
        $opportunityId = $request->get('opportunity_id'); // دریافت opportunity_id از URL
    
        return view('sales.contacts.create', compact('organizations', 'opportunityId'));
    }
    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => 'nullable|string|email|max:255|unique:contacts',
            'phone'      => 'nullable|string|max:20',
            'mobile'     => 'nullable|string|max:20',
            'address'    => 'nullable|string',
            'company'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'opportunity_id'  => 'nullable|exists:opportunities,id',
            'do_not_send_email' => 'nullable|boolean',
            'is_portal_user'    => 'nullable|boolean',
        ]);

        // ساخت سازمان جدید در صورت نیاز
        if ($request->filled('company') && !$request->filled('organization_id')) {
            $organization = Organization::firstOrCreate([
                'name' => $request->input('company'),
            ]);
            $validated['organization_id'] = $organization->id;
        }

        $validated['do_not_send_email'] = $request->has('do_not_send_email');
        $validated['is_portal_user'] = $request->has('is_portal_user');

        $contact = Contact::create($validated);

        if ($request->filled('opportunity_id')) {
            Opportunity::where('id', $request->opportunity_id)
                ->update(['contact_id' => $contact->id]);

            return redirect()
                ->route('sales.opportunities.show', $request->opportunity_id)
                ->with('success', 'مخاطب با موفقیت ایجاد و به فرصت فروش متصل شد.');
        }

        return redirect()->route('sales.contacts.index')->with('success', 'مخاطب با موفقیت ایجاد شد.');
    }


    public function show(Contact $contact)
    {
        return view('sales.contacts.show', compact('contact'));
    }

    public function edit(Contact $contact)
    {
        $organizations = \App\Models\Organization::all();
        return view('sales.contacts.edit', compact('contact', 'organizations'));
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => 'nullable|email|max:255|unique:contacts,email,' . $contact->id,
            'phone'      => 'nullable|string|max:20',
            'mobile'     => 'nullable|string|max:20',
            'company'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'opportunity_id'  => 'nullable|exists:opportunities,id',
            
        ]);

        if ($request->filled('company') && !$request->filled('organization_id')) {
            $organization = Organization::firstOrCreate([
                'name' => $request->input('company'),
            ]);
            $validated['organization_id'] = $organization->id;
        }

        $validated['do_not_send_email'] = $request->has('do_not_send_email');
        $validated['is_portal_user'] = $request->has('is_portal_user');

        $contact->update($validated);

        return redirect()->route('sales.contacts.index')->with('success', 'مخاطب با موفقیت ویرایش شد.');
    }


}
