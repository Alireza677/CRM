<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Opportunity;
use App\Models\Contact;

class OpportunityController extends Controller
{
    public function create()
    {
        return view('opportunity.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'organization' => 'nullable|string',
            'contact' => 'required|string',
            'type' => 'nullable|string',
            'source' => 'nullable|string',
            'assigned_to' => 'nullable|string',
            'success_rate' => 'nullable|numeric',
            'amount' => 'nullable|numeric',
            'next_follow_up' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $contact = Contact::where('name', $request->input('contact'))->first();

        if (!$contact) {
            return back()->withErrors(['contact' => 'مخاطب واردشده یافت نشد.']);
        }

        $data = $request->except('contact');
        $data['contact_id'] = $contact->id;

        Opportunity::create($data);

        return redirect()->route('opportunity.index')->with('success', 'فرصت فروش با موفقیت ثبت شد.');
    }
}
