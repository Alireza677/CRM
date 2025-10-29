<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Support\Arr;

class AjaxCreateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function contact(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => 'nullable|email|max:255|unique:contacts,email',
            'mobile'     => 'nullable|string|max:20',
            'phone'      => 'nullable|string|max:20',
            'state'      => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        $contact = Contact::create($validated);
        $contact->load('organization');

        return response()->json([
            'id' => $contact->id,
            'first_name' => $contact->first_name,
            'last_name'  => $contact->last_name,
            'full_name'  => trim(($contact->first_name.' '.$contact->last_name)),
            'mobile'     => $contact->mobile,
            'organization' => $contact->organization ? [
                'id' => $contact->organization->id,
                'name' => $contact->organization->name,
            ] : null,
        ], 201);
    }

    public function organization(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'website'     => 'nullable|url|max:255',
            'state'       => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:255',
            'contact_id'  => 'nullable|exists:contacts,id',
        ]);

        $org = Organization::create(Arr::except($validated, ['contact_id']));
        if (!empty($validated['contact_id'])) {
            Contact::whereKey($validated['contact_id'])->update(['organization_id' => $org->id]);
        }

        return response()->json([
            'id'   => $org->id,
            'name' => $org->name,
        ], 201);
    }
}

