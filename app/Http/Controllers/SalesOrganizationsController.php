<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Http\Request;

class SalesOrganizationsController extends Controller
{
    public function index()
    {
        return view('sales.organizations.index');
    }

    public function create()
    {
        $users = User::all();
        $contacts = Contact::all();

        return view('sales.organizations.create', compact('users', 'contacts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        Organization::create($validated);

        return redirect()->route('sales.organizations.index')->with('success', 'سازمان با موفقیت ایجاد شد.');
    }

    public function edit(Organization $organization)
    {
        $this->authorize('update', $organization);
        $users = User::all();
        $contacts = Contact::all();

        return view('sales.organizations.edit', compact('organization', 'users', 'contacts'));
    }

    public function update(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        $organization->update($validated);

        return redirect()->route('sales.organizations.index')->with('success', 'سازمان با موفقیت ویرایش شد.');
    }
}
