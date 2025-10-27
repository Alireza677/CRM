<?php

namespace App\Http\Controllers;

use App\Models\SalesLead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Contact;
use Illuminate\Support\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\FormOptionsHelper;
use Illuminate\Validation\Rule;
use App\Helpers\DateHelper;

class SalesLeadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin')->only('destroy');
    }
    public function index(Request $request)
    {
        $query = SalesLead::visibleFor(auth()->user(), 'leads')->with('assignedUser');

        // جستجوی عمومی
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('state', 'like', "%{$search}%");
            });
        }

       // فیلتر بر اساس فیلدهای خاص
        if ($request->filled('lead_source')) {
            $query->where('lead_source', $request->lead_source);
        }

        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('full_name')) {
            $query->where('full_name', 'like', '%' . $request->full_name . '%');
        }

        if ($request->filled('mobile')) {
            $query->where(function ($q) use ($request) {
                $q->where('mobile', 'like', '%' . $request->mobile . '%')
                ->orWhere('phone', 'like', '%' . $request->mobile . '%');
            });
        }


        // صفحه‌بندی
        $leads = $query->latest()->paginate(10)->appends($request->query());

        // داده‌های کمکی
        $users = User::all();
        $leadSources = \App\Helpers\FormOptionsHelper::leadSources();

        return view('marketing.leads.index', compact('leads', 'users', 'leadSources'));
    }


    public function create()
    {
        $users = User::all();
        $referrals = $users;
        return view('marketing.leads.create', compact('users', 'referrals'));
    }

    
    public function store(Request $request)
    {
        \Log::info('🟡 store() method started');
        \Log::info('🟡 Raw request input:', $request->all());

        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',
            'full_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'lead_source' => ['required', 'string', Rule::in(array_keys(FormOptionsHelper::leadSources()))],

            'lead_status' => ['nullable', 'string'],
            'assigned_to' => 'nullable|exists:users,id',
            'lead_date' => 'nullable|string',
            'next_follow_up_date' => 'nullable|string',

            'referred_to' => 'nullable|exists:users,id',
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
            'full_name.required' => 'نام و نام خانوادگی الزامی است.',
            'email.email' => 'فرمت ایمیل نامعتبر است.',
            'website.url' => 'فرمت وب سایت نامعتبر است.',
        ]);

        if ($validator->fails()) {
            \Log::warning('🔴 Validation failed:', $validator->errors()->toArray());
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $validated = $validator->validated();
            \Log::info('🟢 Validation passed:', $validated);

            // 🟠 جدا کردن یادداشت اولیه
            $noteContent = $validated['notes'] ?? null;
            unset($validated['notes']);

            $validated['created_by'] = Auth::id();
            // Ensure creator ownership is recorded for visibility scopes
            $validated['owner_user_id'] = Auth::id();
            $validated['do_not_email'] = $request->has('do_not_email');
            $validated['lead_date'] = DateHelper::toGregorian((string)($validated['lead_date'] ?? ''));
            if (strtolower((string)($validated['lead_status'] ?? '')) === 'lost') {
                $validated['next_follow_up_date'] = null; // سرکاری → تاریخ پیگیری بعدی لازم نیست
            } else {
                $validated['next_follow_up_date'] = DateHelper::toGregorian((string)($validated['next_follow_up_date'] ?? ''));
            }

            \Log::info('🔵 Final data before create:', $validated);

            $lead = SalesLead::create($validated);

            if ($lead && $lead->id) {
                \Log::info('✅ Sales lead created successfully with ID: ' . $lead->id);

                // 🟢 ثبت یادداشت اولیه در جدول notes
                if (!empty($noteContent)) {
                    $lead->notes()->create([
                        'body' => $noteContent,
                        'user_id' => auth()->id(),
                    ]);
                    \Log::info('📝 Initial note saved for lead ID: ' . $lead->id);
                }

                return redirect()->route('marketing.leads.index')
                    ->with('success', 'سرنخ فروش با موفقیت ایجاد شد.');
            } else {
                \Log::error('🛑 Sales lead creation failed. No ID returned.');
                return redirect()->back()
                    ->with('error', 'خطا در ایجاد سرنخ فروش. لطفا دوباره تلاش کنید.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            \Log::error('🔥 Exception caught during sales lead creation: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'خطا در ایجاد سرنخ فروش: ' . $e->getMessage())
                ->withInput();
        }
    }

    

    public function bulkDelete(Request $request)
    {
        
        $leadIds = $request->input('selected_leads', []);
        
        if (!empty($leadIds)) {
            SalesLead::whereIn('id', $leadIds)->delete();
        }

        return redirect()->route('marketing.leads.index')->with('success', 'سرنخ‌ها با موفقیت حذف شدند.');
    }


    public function edit(SalesLead $lead)
    {
        $users = User::all();
        $referrals = $users;
        return view('marketing.leads.edit', compact('lead', 'users', 'referrals'));
    }

    
    public function update(Request $request, SalesLead $lead)
    {
        \Log::info('🔵 update() reached');
        \Log::info('🔵 Request all:', $request->all());

        // 🟢 تبدیل تاریخ‌های شمسی به میلادی قبل از ولیدیشن
        $leadDateConv = DateHelper::toGregorian((string)($request->lead_date ?? ''));
        $statusVal = (string)($request->lead_status ?? '');
        if (strtolower($statusVal) === 'lost') {
            $nextFollowUpConv = null;
        } else {
            $nextFollowUpConv = DateHelper::toGregorian((string)($request->next_follow_up_date ?? ''));
        }
        $request->merge([
            'lead_date' => $leadDateConv,
            'next_follow_up_date' => $nextFollowUpConv,
        ]);
        \Log::info('🔁 Converted dates:', [
            'lead_date' => $request->lead_date,
            'next_follow_up_date' => $request->next_follow_up_date,
        ]);

        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',
            'full_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'lead_source' => ['required', 'string', Rule::in(array_keys(FormOptionsHelper::leadSources()))],
            'lead_status' => ['required', 'string', Rule::in(array_keys(FormOptionsHelper::leadStatuses()))],
            'assigned_to' => 'required|exists:users,id',
            'referred_to' => 'nullable|exists:users,id',
            'lead_date' => 'required|date',
            'next_follow_up_date' => 'nullable|date|after_or_equal:today|required_unless:lead_status,lost',
            'do_not_email' => 'boolean',
            'customer_type' => 'nullable|string|in:مشتری جدید,مشتری قدیمی,مشتری بالقوه',
            'industry' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'main_test_field' => 'nullable|string|max:255',
            'dependent_test_field' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',

            // 👇 در آپدیت می‌خوایم اصلاً نادیده بگیریمش؛
            // پس توی ولیدیشن هم آزاد می‌ذاریم که خطا نده،
            // ولی بعداً حذفش می‌کنیم.
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // ✅ یادداشت اولیه در آپدیت نباید تغییر کند
        if (array_key_exists('notes', $validated)) {
            \Log::info('🧯 Removing notes from update payload to keep initial note immutable.');
            unset($validated['notes']);
        }

        // چک‌باکس
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
        $lead->load(['lastNote', 'assignedTo']);
        $lead->jalali_created_at = DateHelper::toJalali($lead->created_at);
        $lead->jalali_updated_at = DateHelper::toJalali($lead->updated_at);
        
        $allUsers = User::whereNotNull('username')->get(); // ✅ این خط اضافه شود

        return view('marketing.leads.show', compact('lead', 'allUsers'));
    }

    public function loadTab(SalesLead $lead, $tab)
    {
        return view("marketing.leads.tabs.{$tab}", compact('lead'));
    }

    public function convertToOpportunity(Request $request, SalesLead $lead)
    {
        if (!empty($lead->converted_at)) {
            return redirect()->back()->with('error', 'این سرنخ قبلاً به فرصت تبدیل شده است.');
        }

        try {
            $organization = null;
            if (!empty($lead->company)) {
                $organization = Organization::firstOrCreate(
                    ['name' => $lead->company],
                    [
                        'phone' => $lead->phone ?? $lead->mobile,
                        'city' => $lead->city,
                        'state' => $lead->state,
                        'address' => $lead->address,
                    ]
                );
            }

            $firstName = null;
            $lastName = null;
            if (!empty($lead->full_name)) {
                $parts = preg_split('/\s+/', trim($lead->full_name));
                $lastName = array_pop($parts);
                $firstName = trim(implode(' ', $parts));
                if ($firstName === '') {
                    $firstName = $lastName;
                    $lastName = '';
                }
            }

            $contact = null;
            if (!empty($firstName) || !empty($lastName)) {
                $contact = Contact::create([
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'email'      => $lead->email,
                    'mobile'     => $lead->mobile,
                    'phone'      => $lead->phone,
                    'company'    => $lead->company,
                    'city'       => $lead->city,
                    'state'      => $lead->state,
                    'address'    => $lead->address,
                    'organization_id' => $organization?->id,
                    'assigned_to' => $lead->assigned_to,
                ]);
            }

            $name = $lead->company ? ('فرصت - ' . $lead->company) : ('فرصت - ' . ($lead->full_name ?: ('سرنخ #' . $lead->id)));

            $opportunity = Opportunity::create([
                'name'             => $name,
                'organization_id'  => $organization?->id,
                'contact_id'       => $contact?->id,
                'assigned_to'      => $lead->assigned_to,
                'source'           => $lead->lead_source,
                'next_follow_up'   => $lead->next_follow_up_date,
                'description'      => $lead->notes,
                'stage'            => 'new',
            ]);

            $lead->converted_at = Carbon::now();
            $lead->converted_opportunity_id = $opportunity->id;
            $lead->converted_by = Auth::id();
            $lead->save();

            return redirect()
                ->route('marketing.leads.index')
                ->with('success', 'سرنخ با موفقیت به فرصت فروش تبدیل شد.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'خطا در تبدیل سرنخ به فرصت: ' . $e->getMessage());
        }
    }

}
