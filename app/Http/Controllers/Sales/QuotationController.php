<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\Item;
use App\Models\PriceList;
use App\Models\Unit;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index()
    {
        $quotations = Quotation::with(['contact', 'organization', 'opportunity', 'assignedTo', 'productManager'])
            ->latest()
            ->paginate(10);

        return view('sales.quotations.index', compact('quotations'));
    }

    public function create()
    {
        $contacts = Contact::all();
        $organizations = Organization::all();
        $opportunities = Opportunity::all();
        $users = User::all();
        $items = Item::all();
        $price_lists = PriceList::all();
        $units = Unit::all();

        return view('sales.quotations.create', compact(
            'contacts',
            'organizations',
            'opportunities',
            'users',
            'items',
            'price_lists',
            'units'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'quotation_date' => 'required|date',
            'contact_id' => 'required|exists:contacts,id',
            'organization_id' => 'required|exists:organizations,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'assigned_to' => 'required|exists:users,id',
            'product_manager' => 'required|exists:users,id',
            'quotation_number' => 'required|string|max:255|unique:quotations',
            'billing_address_source' => 'required|in:organization,contact,custom',
            'shipping_address_source' => 'required|in:organization,contact,custom',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'customer_address' => 'required|string',
            'postal_code' => 'required|string|max:10',
            'item_name' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:1',
            'price_list' => 'required|exists:price_lists,id',
            'discount' => 'nullable|numeric|min:0',
            'unit' => 'required|exists:units,id',
            'no_tax_region' => 'boolean',
            'tax_type' => 'required|string|in:group',
            'adjustment_type' => 'nullable|in:adjustment,setting',
            'adjustment_value' => 'nullable|numeric',
        ]);

        $quotation = Quotation::create($validated);

        return redirect()->route('sales.quotations.index')
            ->with('success', 'پیش فاکتور با موفقیت ایجاد شد.');
    }

    public function show(Quotation $quotation)
    {
        return view('sales.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $contacts = Contact::all();
        $organizations = Organization::all();
        $opportunities = Opportunity::all();
        $users = User::all();
        $items = Item::all();
        $price_lists = PriceList::all();
        $units = Unit::all();

        return view('sales.quotations.edit', compact(
            'quotation',
            'contacts',
            'organizations',
            'opportunities',
            'users',
            'items',
            'price_lists',
            'units'
        ));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'quotation_date' => 'required|date',
            'contact_id' => 'required|exists:contacts,id',
            'organization_id' => 'required|exists:organizations,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'assigned_to' => 'required|exists:users,id',
            'product_manager' => 'required|exists:users,id',
            'quotation_number' => 'required|string|max:255|unique:quotations,quotation_number,' . $quotation->id,
            'billing_address_source' => 'required|in:organization,contact,custom',
            'shipping_address_source' => 'required|in:organization,contact,custom',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'customer_address' => 'required|string',
            'postal_code' => 'required|string|max:10',
            'item_name' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:1',
            'price_list' => 'required|exists:price_lists,id',
            'discount' => 'nullable|numeric|min:0',
            'unit' => 'required|exists:units,id',
            'no_tax_region' => 'boolean',
            'tax_type' => 'required|string|in:group',
            'adjustment_type' => 'nullable|in:adjustment,setting',
            'adjustment_value' => 'nullable|numeric',
        ]);

        $quotation->update($validated);

        return redirect()->route('sales.quotations.index')
            ->with('success', 'پیش فاکتور با موفقیت بروزرسانی شد.');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()->route('sales.quotations.index')
            ->with('success', 'پیش فاکتور با موفقیت حذف شد.');
    }
} 