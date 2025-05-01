<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    /**
     * نمایش لیست فرصت‌های فروش
     */
    public function index()
    {
        $opportunities = Opportunity::with(['contact', 'user', 'organization'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('sales.opportunities.index', compact('opportunities'));
    }

    /**
     * نمایش فرم ایجاد فرصت فروش
     */
    public function create()
    {
        $contacts = Contact::all();
        $organizations = Organization::all();
        $users = User::all();
        return view('sales.opportunities.create', compact('contacts', 'organizations', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
            'type' => 'required|string|in:کسب و کار موجود,کسب و کار جدید',
            'source' => 'required|string|in:وب سایت,مشتریان قدیمی,نمایشگاه,بازاریابی حضوری',
            'assigned_to' => 'required|string|in:علیرضا عامری,مهدی شیخی,نازی پیران',
            'success_rate' => 'required|numeric|min:0|max:100',
            'amount' => 'required|numeric|min:0',
            'next_follow_up' => 'required|date',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'نام فرصت فروش الزامی است.',
            'organization.required' => 'نام سازمان الزامی است.',
            'contact.required' => 'نام مخاطب الزامی است.',
            'type.required' => 'نوع کسب و کار الزامی است.',
            'type.in' => 'نوع کسب و کار انتخاب شده معتبر نیست.',
            'source.required' => 'منبع سرنخ الزامی است.',
            'source.in' => 'منبع سرنخ انتخاب شده معتبر نیست.',
            'assigned_to.required' => 'ارجاع به الزامی است.',
            'assigned_to.in' => 'ارجاع به انتخاب شده معتبر نیست.',
            'success_rate.required' => 'درصد موفقیت الزامی است.',
            'success_rate.numeric' => 'درصد موفقیت باید عددی باشد.',
            'success_rate.min' => 'درصد موفقیت نمی‌تواند کمتر از 0 باشد.',
            'success_rate.max' => 'درصد موفقیت نمی‌تواند بیشتر از 100 باشد.',
            'amount.required' => 'مقدار الزامی است.',
            'amount.numeric' => 'مقدار باید عددی باشد.',
            'amount.min' => 'مقدار نمی‌تواند کمتر از 0 باشد.',
            'next_follow_up.required' => 'تاریخ پیگیری بعدی الزامی است.',
            'next_follow_up.date' => 'تاریخ پیگیری بعدی معتبر نیست.',
        ]);

        Opportunity::create($validated);

        return redirect()->route('sales.opportunities.index')
            ->with('success', 'فرصت فروش با موفقیت ایجاد شد.');
    }

    public function show(Opportunity $opportunity)
    {
        return view('sales.opportunities.show', compact('opportunity'));
    }

    public function edit(Opportunity $opportunity)
    {
        $contacts = Contact::all();
        $organizations = Organization::all();
        $users = User::all();
        return view('sales.opportunities.edit', compact('opportunity', 'contacts', 'organizations', 'users'));
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'stage' => 'required|string|max:255',
            'source' => 'nullable|string|max:255',
            'contact_id' => 'required|exists:contacts,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'user_id' => 'required|exists:users,id',
            'success_rate' => 'nullable|numeric|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $opportunity->update($validated);

        return redirect()->route('sales.opportunities.index')
            ->with('success', 'Sales opportunity updated successfully.');
    }

    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();

        return redirect()->route('sales.opportunities.index')
            ->with('success', 'Sales opportunity deleted successfully.');
    }
}
