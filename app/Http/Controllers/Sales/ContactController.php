<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\SalesLead;
use App\Models\SmsList;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin')->only('destroy');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 100);   // پیش‌فرض 100
        $perPage = in_array($perPage, [25,50,100,200]) ? $perPage : 100;
    
        $query = Contact::visibleFor(auth()->user(), 'contacts')
            ->select('contacts.*', 'organizations.name as organization_name', 'users.name as assigned_to_name')
            ->leftJoin('organizations', 'contacts.organization_id', '=', 'organizations.id')
            ->leftJoin('users', 'contacts.assigned_to', '=', 'users.id');
    
        if ($request->filled('search')) {   // filled به‌جای has
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contacts.first_name', 'like', "%{$search}%")
                  ->orWhere('contacts.last_name', 'like', "%{$search}%")
                  ->orWhere('contacts.mobile', 'like', "%{$search}%");
            });
        }
    
        if ($request->filled('assigned_to')) {
            $query->where('contacts.assigned_to', $request->assigned_to);
        }
    
        if ($request->filled('organization')) {
            $query->where('contacts.organization_id', (int) $request->organization);
        } elseif ($request->filled('organization_name')) {
            $name = trim($request->input('organization_name'));
            if ($name !== '') {
                $query->where(function($q) use ($name) {
                    $q->where('organizations.name', 'like', "%{$name}%")
                      ->orWhere('contacts.company', 'like', "%{$name}%");
                });
            }
        }
    
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
    
        if ($sortField === 'organization_name') {
            $query->orderBy('organizations.name', $sortDirection);
        } elseif ($sortField === 'assigned_to_name') {
            $query->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy("contacts.{$sortField}", $sortDirection);
        }
    
        $contacts = $query->paginate($perPage)->withQueryString();
    
        $users = \App\Models\User::all(['id', 'name']);
        $organizations = \App\Models\Organization::all(['id', 'name']);
        $smsLists = SmsList::query()->orderByDesc('created_at')->get(['id','name']);

        return view('sales.contacts.index', compact('contacts', 'users', 'organizations', 'smsLists'));
    }

    public function create(Request $request)
    {
        $organizations = \App\Models\Organization::all();
        $opportunityId = $request->get('opportunity_id'); // دریافت opportunity_id از URL
        $users = \App\Models\User::all(); // لیست کاربران برای ارجاع به

        return view('sales.contacts.create', compact('organizations', 'opportunityId', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => 'nullable|string|email|max:255|unique:contacts,email',
            'phone'      => 'nullable|string|max:20',
            'mobile'     => 'nullable|string|max:20',
            'address'    => 'nullable|string',
            'company'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:255',
            'state'      => 'nullable|string|max:255',
            'assigned_to'=> 'nullable|exists:users,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'opportunity_id'  => 'nullable|exists:opportunities,id',
            'do_not_send_email' => 'nullable|boolean',
            'is_portal_user'    => 'nullable|boolean',
            'create_new_org'    => 'nullable|boolean',
            'new_org_name'      => 'required_if:create_new_org,1|string|max:255',
            'new_org_website'   => 'nullable|string|max:255',
            'new_org_address'   => 'nullable|string',
        ], [
            'new_org_name.required_if' => 'نام سازمان جدید الزامی است.',
        ]);

        if (!$request->filled('state') && $request->filled('province')) {
            $validated['state'] = trim($request->input('province'));
        }

        $data = Arr::except($validated, [
            'create_new_org',
            'new_org_name',
            'new_org_website',
            'new_org_address',
        ]);

        $contact = DB::transaction(function () use ($request, $data) {
            $payload = $this->prepareOrganizationData($request, $data);
            $payload['do_not_send_email'] = $request->has('do_not_send_email');
            $payload['is_portal_user']    = $request->has('is_portal_user');

            return Contact::create($payload);
        });

        if ($request->filled('opportunity_id')) {
            Opportunity::where('id', $request->opportunity_id)
                ->update(['contact_id' => $contact->id]);

            return redirect()
                ->route('sales.opportunities.show', $request->opportunity_id)
                ->with('success', 'مخاطب ایجاد شد و به فرصت انتخاب‌شده متصل گردید.');
        }

        return redirect()
            ->route('sales.contacts.index')
            ->with('contact_created', [
                'contact_id' => $contact->id,
                'contact_name' => $contact->name ?: ($contact->company ?? 'مخاطب جدید'),
            ]);
    }

    public function show(Contact $contact)
    {
        return view('sales.contacts.show', compact('contact'));
    }

    public function loadTab(Contact $contact, $tab)
    {
        $view = "sales.contacts.tabs.$tab";
        if (!view()->exists($view)) {
            abort(404);
        }

        $data = ['contact' => $contact];

        if ($tab === 'updates') {
            $data['activities'] = $contact->activities()->latest()->get();
        }

        return view($view, $data);
    }

    public function edit(Contact $contact)
    {
        $this->authorize('update', $contact);
        $organizations = \App\Models\Organization::all();
        $users = \App\Models\User::all(); // لیست کاربران برای کشوی ارجاع به

        return view('sales.contacts.edit', compact('contact', 'organizations', 'users'));
    }

    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'first_name'       => 'nullable|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'email'            => 'nullable|email|max:255|unique:contacts,email,' . $contact->id,
            'phone'            => 'nullable|string|max:20',
            'mobile'           => 'nullable|string|max:20',
            'address'          => 'nullable|string',
            'company'          => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:255',
            'state'            => 'nullable|string|max:255',
            'organization_id'  => 'nullable|exists:organizations,id',
            'opportunity_id'   => 'nullable|exists:opportunities,id',
            'assigned_to'      => 'nullable|exists:users,id',
            'do_not_send_email'=> 'nullable|boolean',
            'is_portal_user'   => 'nullable|boolean',
            'create_new_org'   => 'nullable|boolean',
            'new_org_name'     => 'required_if:create_new_org,1|string|max:255',
            'new_org_website'  => 'nullable|string|max:255',
            'new_org_address'  => 'nullable|string',
        ], [
            'new_org_name.required_if' => 'نام سازمان جدید الزامی است.',
        ]);

        if (!$request->filled('state') && $request->filled('province')) {
            $validated['state'] = trim($request->input('province'));
        }

        $data = Arr::except($validated, [
            'create_new_org',
            'new_org_name',
            'new_org_website',
            'new_org_address',
        ]);

        DB::transaction(function () use ($request, $contact, $data) {
            $payload = $this->prepareOrganizationData($request, $data);
            $payload['do_not_send_email'] = $request->has('do_not_send_email');
            $payload['is_portal_user']    = $request->has('is_portal_user');

            $contact->update($payload);
        });

        return redirect()
            ->route('sales.contacts.index')
            ->with('success', 'اطلاعات مخاطب با موفقیت به‌روزرسانی شد.');
    }

    public function convertToLead(Request $request, Contact $contact)
    {
        $this->authorize('view', $contact);

        $lead = DB::transaction(function () use ($contact) {
            $fullName = trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
            if ($fullName === '') {
                $fullName = $contact->company ?? null;
            }
            if (!$fullName) {
                $fullName = 'Contact #' . $contact->id;
            }

            return SalesLead::create([
                'owner_user_id' => auth()->id(),
                'full_name' => $fullName,
                'company' => $contact->company,
                'email' => $contact->email,
                'mobile' => $contact->mobile,
                'phone' => $contact->phone,
                'address' => $contact->address,
                'state' => $contact->state,
                'city' => $contact->city,
                'lead_source' => 'contact',
                'lead_status' => 'new',
                'assigned_to' => $contact->assigned_to,
                'lead_date' => now()->toDateString(),
                'next_follow_up_date' => now()->addDays(7)->toDateString(),
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('marketing.leads.edit', $lead)
            ->with('success', 'سرنخ جدید با موفقیت از این مخاطب ایجاد شد.');
    }

    protected function prepareOrganizationData(Request $request, array $payload): array
    {
        if ($request->boolean('create_new_org')) {
            $organization = Organization::create([
                'name' => trim($request->input('new_org_name')),
                'website' => $request->input('new_org_website'),
                'address' => $request->input('new_org_address') ?: ($payload['address'] ?? null),
                'phone' => $request->input('phone'),
                'state' => $payload['state'] ?? null,
                'city'  => $payload['city'] ?? null,
            ]);
            $payload['organization_id'] = $organization->id;
            $payload['company'] = $organization->name;

        } elseif (!empty($payload['organization_id'])) {
            if (empty($payload['company'])) {
                $payload['company'] = optional(Organization::find($payload['organization_id']))->name ?? $payload['company'];
            }

        } elseif ($request->filled('company')) {
            $organization = Organization::firstOrCreate(
                ['name' => trim($request->input('company'))],
                [
                    'phone' => $request->input('phone'),
                    'state' => $payload['state'] ?? null,
                    'city'  => $payload['city'] ?? null,
                ]
            );
            $payload['organization_id'] = $organization->id;
            $payload['company'] = $organization->name;
        }

        return $payload;
    }

    public function bulkDelete(Request $request)
    {
        Contact::whereIn('id', $request->input('selected_contacts', []))->delete();
        return redirect()->route('sales.contacts.index')->with('success', 'مخاطبین انتخاب‌شده با موفقیت حذف شدند.');
    }
    
    public function destroy(Contact $contact)
    {
        $contact->delete();
        return redirect()->route('sales.contacts.index')->with('success', 'مخاطب با موفقیت حذف شد.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new ContactsImport, $request->file('file'));

            Log::info('✔ ایمپورت مخاطبین با موفقیت انجام شد توسط کاربر: ' . auth()->user()->email);

            return redirect()->back()->with('success', 'ایمپورت مخاطبین با موفقیت انجام شد.');
        } catch (\Exception $e) {
            Log::error('❌ خطا در ایمپورت مخاطبین: ' . $e->getMessage());

            return redirect()->back()->with('error', 'خطا در ایمپورت مخاطبین: ' . $e->getMessage());
        }
    }
}
