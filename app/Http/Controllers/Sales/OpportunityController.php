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
    // نمایش لیست فرصت‌های فروش
    public function index()
    {
        $opportunities = Opportunity::with('contact')->latest()->paginate(10);
        return view('sales.opportunities.index', compact('opportunities'));
    }

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
            'contact_id' => 'nullable|exists:contacts,id',
            'type' => 'required|string|in:کسب و کار موجود,کسب و کار جدید',
            'source' => 'required|string|in:وب سایت,مشتریان قدیمی,نمایشگاه,بازاریابی حضوری',
            'assigned_to' => 'nullable|exists:users,id',
            'success_rate' => 'required|numeric|min:0|max:100',
            'amount' => 'required|numeric|min:0',
            'next_follow_up' => 'required|date',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'نام فرصت فروش الزامی است.',
            'organization_id.exists' => 'سازمان انتخاب شده معتبر نیست.',
            'contact_id.exists' => 'مخاطب انتخاب شده معتبر نیست.',
            'type.required' => 'نوع کسب و کار الزامی است.',
            'type.in' => 'نوع کسب و کار انتخاب شده معتبر نیست.',
            'source.required' => 'منبع سرنخ الزامی است.',
            'source.in' => 'منبع سرنخ انتخاب شده معتبر نیست.',
            'assigned_to.exists' => 'کاربر انتخاب شده معتبر نیست.',
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
}
