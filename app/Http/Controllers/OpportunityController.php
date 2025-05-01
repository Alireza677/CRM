<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Contact;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index()
    {
        $opportunities = Opportunity::all();
        return view('sales.opportunities.index', compact('opportunities'));
    }

    public function create()
    {
        return view('sales.opportunities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'organization' => 'nullable|string',
            'contact' => 'nullable|string',
            'type' => 'nullable|string',
            'source' => 'nullable|string',
            'assigned_to' => 'nullable|string',
            'success_rate' => 'nullable|integer',
            'amount' => 'nullable|numeric',
            'next_follow_up' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        Opportunity::create($validated);

        return redirect()->route('sales.opportunities.index')
            ->with('success', 'Opportunity created successfully.');
    }

    public function show(Opportunity $opportunity)
    {
        return view('sales.opportunities.show', compact('opportunity'));
    }

    public function edit(Opportunity $opportunity)
    {
        return view('sales.opportunities.edit', compact('opportunity'));
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'organization' => 'nullable|string',
            'contact' => 'nullable|string',
            'type' => 'nullable|string',
            'source' => 'nullable|string',
            'assigned_to' => 'nullable|string',
            'success_rate' => 'nullable|integer',
            'amount' => 'nullable|numeric',
            'next_follow_up' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $opportunity->update($validated);

        return redirect()->route('sales.opportunities.index')
            ->with('success', 'Opportunity updated successfully.');
    }

    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();

        return redirect()->route('sales.opportunities.index')
            ->with('success', 'Opportunity deleted successfully.');
    }
} 