<?php

namespace App\Http\Controllers;

use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SalesLeadController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesLead::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%");
            });
        }

        // Filter by lead source
        if ($request->has('lead_source')) {
            $query->where('lead_source', $request->lead_source);
        }

        // Filter by lead status
        if ($request->has('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        $leads = $query->latest()->paginate(10);
        $users = User::all();

        return view('marketing.leads.index', compact('leads', 'users'));
    }

    public function create()
    {
        $users = User::all();
        return view('marketing.leads.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'lead_source' => ['required', 'string', 'in:وبسایت,شبکه‌های اجتماعی,معرفی,سایر'],
            'lead_status' => ['required', 'string', 'in:جدید,تماس گرفته شده,واجد شرایط,فاقد شرایط'],
            'assigned_to' => 'required|exists:users,id',
            'lead_date' => 'required|date',
            'next_follow_up_date' => 'required|date|after_or_equal:today',
            'do_not_email' => 'boolean',
            'customer_type' => 'nullable|string|in:مشتری جدید,مشتری قدیمی,مشتری بالقوه',
            'industry' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'main_test_field' => 'nullable|string|max:255',
            'dependent_test_field' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ], [
            'first_name.required' => 'نام الزامی است.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'assigned_to.required' => 'ارجاع به الزامی است.',
            'lead_date.required' => 'تاریخ ثبت سرنخ الزامی است.',
            'next_follow_up_date.required' => 'تاریخ پیگیری بعدی الزامی است.',
            'next_follow_up_date.after_or_equal' => 'تاریخ پیگیری بعدی باید از امروز به بعد باشد.',
            'email.email' => 'فرمت ایمیل نامعتبر است.',
            'website.url' => 'فرمت وب سایت نامعتبر است.',
            'lead_source.required' => 'منبع سرنخ الزامی است.',
            'lead_status.required' => 'وضعیت سرنخ الزامی است.',
            'lead_source.in' => 'منبع سرنخ انتخاب شده نامعتبر است.',
            'lead_status.in' => 'وضعیت سرنخ انتخاب شده نامعتبر است.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $validated = $validator->validated();
            $validated['created_by'] = Auth::id();
            $validated['do_not_email'] = $request->has('do_not_email');
            
            // Format dates if they're not already in the correct format
            $validated['lead_date'] = \Carbon\Carbon::parse($validated['lead_date'])->format('Y-m-d');
            $validated['next_follow_up_date'] = \Carbon\Carbon::parse($validated['next_follow_up_date'])->format('Y-m-d');

            // Create the lead
            $lead = SalesLead::create($validated);

            if ($lead) {
                return redirect()->route('marketing.leads.index')
                    ->with('success', 'سرنخ فروش با موفقیت ایجاد شد.');
            } else {
                return redirect()->back()
                    ->with('error', 'خطا در ایجاد سرنخ فروش. لطفا دوباره تلاش کنید.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            \Log::error('Error creating sales lead: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'خطا در ایجاد سرنخ فروش: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(SalesLead $lead)
    {
        $users = User::all();
        return view('marketing.leads.edit', compact('lead', 'users'));
    }

    public function update(Request $request, SalesLead $lead)
    {
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'lead_source' => 'required|string|in:وب سایت,نمایشگاه,معرفی,تبلیغات,سایر',
            'lead_status' => 'required|string|in:تماس اولیه,موکول به آینده,در حال پیگیری,تبدیل شده,از دست رفته',
            'assigned_to' => 'required|exists:users,id',
            'lead_date' => 'required|date',
            'next_follow_up_date' => 'required|date|after_or_equal:today',
            'do_not_email' => 'boolean',
            'customer_type' => 'nullable|string|in:مشتری جدید,مشتری قدیمی,مشتری بالقوه',
            'industry' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'main_test_field' => 'nullable|string|max:255',
            'dependent_test_field' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'first_name.required' => 'نام الزامی است.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'assigned_to.required' => 'ارجاع به الزامی است.',
            'lead_date.required' => 'تاریخ ثبت سرنخ الزامی است.',
            'next_follow_up_date.required' => 'تاریخ پیگیری بعدی الزامی است.',
            'next_follow_up_date.after_or_equal' => 'تاریخ پیگیری بعدی باید از امروز به بعد باشد.',
            'email.email' => 'فرمت ایمیل نامعتبر است.',
            'website.url' => 'فرمت وب سایت نامعتبر است.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $validated['do_not_email'] = $request->has('do_not_email');

        $lead->update($validated);

        return redirect()->route('marketing.leads.index')
            ->with('success', 'سرنخ فروش با موفقیت بروزرسانی شد.');
    }

    public function destroy(SalesLead $lead)
    {
        $lead->delete();

        return redirect()->route('marketing.leads.index')
            ->with('success', 'سرنخ فروش با موفقیت حذف شد.');
    }

    public function show(SalesLead $lead)
    {
        return view('marketing.leads.show', compact('lead'));
    }
} 