<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Opportunity;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;

class OpportunityController extends Controller
{
    // نمایش فرم ایجاد فرصت جدید
    public function create()
    {
        $organizations = Organization::all();
        $contacts = Contact::all();
        $users = User::all();

        return view('sales.opportunities.create', compact('organizations', 'contacts', 'users'));
    }

    // ذخیره فرصت فروش جدید
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'contact_id' => 'required|exists:contacts,id',
            'type' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'success_rate' => 'nullable|numeric|min:0|max:100',
            'amount' => 'nullable|numeric|min:0',
            'next_follow_up' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        Opportunity::create($validated);

        return redirect()->route('sales.opportunities.index')->with('success', 'فرصت فروش با موفقیت ثبت شد.');
    }
}
