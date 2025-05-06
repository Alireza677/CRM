<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function create()
    {
        $organizations = \App\Models\Organization::all();
        return view('sales.contacts.create', compact('organizations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:contacts',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        $contact = new Contact();
        $contact->first_name = $validated['first_name'];
        $contact->last_name = $validated['last_name'];
        $contact->email = $validated['email'];
        $contact->phone = $validated['phone'] ?? null;
        $contact->mobile = $validated['mobile'] ?? null;
        $contact->address = $validated['address'] ?? null;
        $contact->organization_id = $validated['organization_id'] ?? null;
        $contact->save();

        return redirect()->route('sales.contacts.index')
            ->with('success', 'مخاطب با موفقیت ایجاد شد.');
    }
} 