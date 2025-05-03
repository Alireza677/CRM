<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Opportunity;
use App\Models\Contact;

class OpportunityController extends Controller
{
    // نمایش لیست فرصت‌های فروش
    public function index()
    {
        $opportunities = Opportunity::with('contact')->latest()->paginate(10);
        return view('sales.opportunities.index', compact('opportunities'));
    }

    // نمایش فرم ایجاد فرصت جدید
    public function create()
    {
        return view('sales.opportunities.create');
    }

    // ذخیره فرصت فروش جدید
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'required|string',
            'amount' => 'nullable|numeric',
        ]);

        $contact = Contact::where('name', $request->input('contact'))->first();

        if (!$contact) {
            return back()->withErrors(['contact' => 'مخاطب واردشده یافت نشد.']);
        }

        $data = $request->except('contact');
        $data['contact_id'] = $contact->id;

        Opportunity::create($data);

        return redirect()->route('sales.opportunities.index')->with('success', 'فرصت فروش با موفقیت ذخیره شد.');
    }
}
