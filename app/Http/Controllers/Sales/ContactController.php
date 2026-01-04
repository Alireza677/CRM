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
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ContactsImport;
use App\Services\DuplicateMobileFinder;

class ContactController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin')->only('destroy');
    }

    public function index(Request $request)
    {
        // پیش‌فرض تعداد سطر در هر صفحه 100
        $perPage = (int) $request->input('per_page', 100);
        $perPage = in_array($perPage, [25, 50, 100, 200]) ? $perPage : 100;

        $query = Contact::visibleFor(auth()->user(), 'contacts')
            ->select('contacts.*', 'organizations.name as organization_name', 'users.name as assigned_to_name')
            ->leftJoin('organizations', 'contacts.organization_id', '=', 'organizations.id')
            ->leftJoin('users', 'contacts.assigned_to', '=', 'users.id');

        // متد filled مشابه has است، ولی مقدار خالی را در نظر نمی‌گیرد
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contacts.first_name', 'like', "%{$search}%")
                    ->orWhere('contacts.last_name', 'like', "%{$search}%")
                    ->orWhere('contacts.mobile', 'like', "%{$search}%");
            });
        }
        if ($request->filled('contact_number')) {
            $query->where('contacts.contact_number', 'like', '%' . $request->contact_number . '%');
        }
        if ($request->filled('mobile')) {
            $query->where('contacts.mobile', 'like', '%' . $request->mobile . '%');
        }

        if ($request->filled('assigned_to')) {
            $query->where('contacts.assigned_to', $request->assigned_to);
        }

        if ($request->filled('organization')) {
            $query->where('contacts.organization_id', (int) $request->organization);
        } elseif ($request->filled('organization_name')) {
            $name = trim($request->input('organization_name'));
            if ($name !== '') {
                $query->where(function ($q) use ($name) {
                    $q->where('organizations.name', 'like', "%{$name}%")
                        ->orWhere('contacts.company', 'like', "%{$name}%");
                });
            }
        }

        $sortField     = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        if ($sortField === 'organization_name') {
            $query->orderBy('organizations.name', $sortDirection);
        } elseif ($sortField === 'assigned_to_name') {
            $query->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy("contacts.{$sortField}", $sortDirection);
        }

        $contacts      = $query->paginate($perPage)->withQueryString();
        $users         = \App\Models\User::all(['id', 'name']);
        $organizations = \App\Models\Organization::all(['id', 'name']);
        $smsLists      = SmsList::query()->orderByDesc('created_at')->get(['id', 'name']);

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('sales.contacts.partials.rows', compact('contacts'))->render(),
                'pagination' => view('sales.contacts.partials.pagination', compact('contacts'))->render(),
            ]);
        }

        return view('sales.contacts.index', compact('contacts', 'users', 'organizations', 'smsLists'));
    }

    public function create(Request $request)
    {
        $organizations = \App\Models\Organization::all();
        // گرفتن opportunity_id از URL (در صورت وجود)
        $opportunityId = $request->get('opportunity_id');
        // لیست کاربران برای ارجاع مخاطب
        $users = \App\Models\User::all();

        return view('sales.contacts.create', compact('organizations', 'opportunityId', 'users'));
    }

    public function store(Request $request)
    {
        try {
            $rawNewOrgName       = $request->input('new_org_name');
            $normalizedNewOrgName = is_array($rawNewOrgName) ? Arr::first($rawNewOrgName) : $rawNewOrgName;
            $request->merge(['new_org_name' => is_scalar($normalizedNewOrgName) ? (string) $normalizedNewOrgName : null]);

            $validated = $request->validate([
                'first_name'         => 'nullable|string|max:255',
                'last_name'          => 'nullable|string|max:255',
                'position'           => 'nullable|string|max:255',
                'email'              => 'nullable|string|email|max:255|unique:contacts,email',
                'phone'              => 'nullable|string|max:20',
                'mobile'             => 'nullable|string|max:20',
                'website'            => 'nullable|url|max:255',
                'address'            => 'nullable|string',
                'company'            => 'nullable|string|max:255',
                'city'               => 'nullable|string|max:255',
                'state'              => 'nullable|string|max:255',
                'assigned_to'        => 'nullable|exists:users,id',
                'organization_id'    => 'nullable|exists:organizations,id',
                'opportunity_id'     => 'nullable|exists:opportunities,id',
                'lead_id'            => 'nullable|exists:sales_leads,id',
                'do_not_send_email'  => 'nullable|boolean',
                'is_portal_user'     => 'nullable|boolean',
                'create_new_org'     => 'nullable|boolean',
                'new_org_name'       => 'required_if:create_new_org,1|max:255',
                'new_org_website'    => 'nullable|string|max:255',
                'new_org_address'    => 'nullable|string',
            ], [
                'new_org_name.required_if' => 'در صورت انتخاب گزینه «ایجاد سازمان جدید»، وارد کردن نام سازمان الزامی است.',
            ]);

            // در صورت نبودن state اما وجود province، آن را به state نگاشت می‌کنیم
            if (!$request->filled('state') && $request->filled('province')) {
                $validated['state'] = trim($request->input('province'));
            }

            $duplicateFinder = app(DuplicateMobileFinder::class);
            $normalizedMobile = $duplicateFinder->normalizeMobile($validated['mobile'] ?? null);

            if ($normalizedMobile) {
                $validated['mobile'] = $normalizedMobile;
            } else {
                $validated['mobile'] = $this->cleanupMobileInput($validated['mobile'] ?? null);
            }

            if ($normalizedMobile) {
                $existingContact = $duplicateFinder->findContactByMobile($normalizedMobile);
                if ($existingContact) {
                    $contactName = trim((string) ($existingContact->first_name ?? '') . ' ' . (string) ($existingContact->last_name ?? ''));
                    if ($contactName === '') {
                        $contactName = $existingContact->company ?? ('مخاطب #' . $existingContact->id);
                    }

                    $alertPayload = $duplicateFinder->buildModalPayload(
                        $existingContact,
                        DuplicateMobileFinder::TYPE_CONTACT,
                        $normalizedMobile,
                        [
                            'intent' => 'block',
                            'contact' => [
                                'id' => $existingContact->id,
                                'name' => $contactName,
                                'mobile' => $existingContact->mobile ?? $existingContact->phone,
                                'state' => $existingContact->state,
                                'city' => $existingContact->city,
                                'show_url' => route('sales.contacts.show', $existingContact),
                                'edit_url' => route('sales.contacts.edit', $existingContact),
                            ],
                        ]
                    );
                    $alertPayload['intent'] = 'block';

                    return redirect()
                        ->route('sales.contacts.create')
                        ->withErrors(['mobile' => 'این شماره موبایل قبلاً برای مخاطب دیگری ثبت شده است.'])
                        ->with('duplicate_mobile_alert', $alertPayload)
                        ->withInput();
                }
            }

            $data = Arr::except($validated, [
                'create_new_org',
                'new_org_name',
                'new_org_website',
                'new_org_address',
            ]);

            $user                   = auth()->user();
            $data['assigned_to']    = $data['assigned_to'] ?? $user?->id;
            $data['owner_user_id']  = $data['owner_user_id'] ?? $user?->id;
            $data['team_id']        = $data['team_id'] ?? ($user?->team_id ?? null);
            $data['department']     = $data['department'] ?? ($user?->department ?? null);

            Log::info('CONTACT_STORE_DATA', $data);

            $contact = DB::transaction(function () use ($request, $data) {
                $payload                       = $this->prepareOrganizationData($request, $data);
                $payload['do_not_send_email']  = $request->has('do_not_send_email');
                $payload['is_portal_user']     = $request->has('is_portal_user');
                return Contact::create($payload);
            });

            if ($request->filled('opportunity_id')) {
                Opportunity::where('id', $request->opportunity_id)
                    ->update(['contact_id' => $contact->id]);
            }

            if ($request->filled('lead_id')) {
                SalesLead::where('id', $request->lead_id)
                    ->update(['contact_id' => $contact->id]);

                return redirect()
                    ->route('marketing.leads.show', $request->lead_id)
                    ->with('success', 'مخاطب با موفقیت ایجاد شد و به سرنخ فروش متصل گردید.');
            }

            return redirect()
                ->route('sales.contacts.index')
                ->with('contact_created', [
                    'contact_id'   => $contact->id,
                    'contact_name' => $contact->name ?: ($contact->company ?? 'مخاطب بدون نام'),
                ]);
        } catch (\Throwable $e) {
            Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if (!app()->environment('production')) {
                throw $e;
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'error' => __("\u{062E}\u{0637}\u{0627} \u{062F}\u{0631} \u{0630}\u{062E}\u{06CC}\u{0631}\u{0647} \u{0645}\u{062E}\u{0627}\u{0637}\u{0628}")
                ]);
        }
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

        if ($tab === 'leads') {
            $data['leads'] = $contact->leads()
                ->visibleFor(auth()->user(), 'leads')
                ->latest()
                ->get();
        }

        return view($view, $data);
    }

    public function edit(Contact $contact)
    {
        $this->authorize('update', $contact);

        $organizations = \App\Models\Organization::all();
        // لیست کاربران برای انتخاب «ارجاع به»
        $users = \App\Models\User::all();

        return view('sales.contacts.edit', compact('contact', 'organizations', 'users'));
    }

    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        try {
            $validated = $request->validate([
                'first_name'         => 'nullable|string|max:255',
                'last_name'          => 'nullable|string|max:255',
                'position'           => 'nullable|string|max:255',
                'email'              => 'nullable|email|max:255|unique:contacts,email,' . $contact->id,
                'phone'              => 'nullable|string|max:20',
                'mobile'             => 'nullable|string|max:20',
                'website'            => 'nullable|url|max:255',
                'address'            => 'nullable|string',
                'company'            => 'nullable|string|max:255',
                'city'               => 'nullable|string|max:255',
                'state'              => 'nullable|string|max:255',
                'organization_id'    => 'nullable|exists:organizations,id',
                'opportunity_id'     => 'nullable|exists:opportunities,id',
                'assigned_to'        => 'nullable|exists:users,id',
                'do_not_send_email'  => 'nullable|boolean',
                'is_portal_user'     => 'nullable|boolean',
                'create_new_org'     => 'nullable|boolean',
                'new_org_name'       => 'required_if:create_new_org,1|max:255',
                'new_org_website'    => 'nullable|string|max:255',
                'new_org_address'    => 'nullable|string',
            ], [
                'new_org_name.required_if' => 'در صورت انتخاب گزینه «ایجاد سازمان جدید»، وارد کردن نام سازمان الزامی است.',
            ]);

            if (!$request->filled('state') && $request->filled('province')) {
                $validated['state'] = trim($request->input('province'));
            }

            $duplicateFinder = app(DuplicateMobileFinder::class);
            $normalizedMobile = $duplicateFinder->normalizeMobile($validated['mobile'] ?? null);

            if ($normalizedMobile) {
                $validated['mobile'] = $normalizedMobile;
            } else {
                $validated['mobile'] = $this->cleanupMobileInput($validated['mobile'] ?? null);
            }

            if ($normalizedMobile) {
                $existingContact = $duplicateFinder->findContactByMobile($normalizedMobile);
                if ($existingContact && $existingContact->id !== $contact->id) {
                    $contactName = trim((string) ($existingContact->first_name ?? '') . ' ' . (string) ($existingContact->last_name ?? ''));
                    if ($contactName === '') {
                        $contactName = $existingContact->company ?? ('مخاطب #' . $existingContact->id);
                    }

                    $alertPayload = $duplicateFinder->buildModalPayload(
                        $existingContact,
                        DuplicateMobileFinder::TYPE_CONTACT,
                        $normalizedMobile,
                        [
                            'intent' => 'block',
                            'contact' => [
                                'id' => $existingContact->id,
                                'name' => $contactName,
                                'mobile' => $existingContact->mobile ?? $existingContact->phone,
                                'state' => $existingContact->state,
                                'city' => $existingContact->city,
                                'show_url' => route('sales.contacts.show', $existingContact),
                                'edit_url' => route('sales.contacts.edit', $existingContact),
                            ],
                        ]
                    );
                    $alertPayload['intent'] = 'block';

                    return redirect()
                        ->back()
                        ->withErrors(['mobile' => 'این شماره موبایل قبلاً برای مخاطب دیگری ثبت شده است.'])
                        ->with('duplicate_mobile_alert', $alertPayload)
                        ->withInput();
                }
            }

            $data = Arr::except($validated, [
                'create_new_org',
                'new_org_name',
                'new_org_website',
                'new_org_address',
            ]);

            $user                  = auth()->user();
            $data['assigned_to']   = $data['assigned_to'] ?? ($contact->assigned_to ?? $user?->id);
            $data['owner_user_id'] = $contact->owner_user_id ?? $user?->id;
            $data['team_id']       = $data['team_id'] ?? ($contact->team_id ?? ($user?->team_id ?? null));
            $data['department']    = $data['department'] ?? ($contact->department ?? ($user?->department ?? null));

            Log::info('CONTACT_STORE_DATA', $data);

            DB::transaction(function () use ($request, $contact, $data) {
                $payload                       = $this->prepareOrganizationData($request, $data);
                $payload['do_not_send_email']  = $request->has('do_not_send_email');
                $payload['is_portal_user']     = $request->has('is_portal_user');
                $contact->update($payload);
            });

            return redirect()
                ->route('sales.contacts.index')
                ->with('success', 'اطلاعات مخاطب با موفقیت به‌روزرسانی شد.');
        } catch (\Throwable $e) {
            Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if (!app()->environment('production')) {
                throw $e;
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'error' => __("\u{062E}\u{0637}\u{0627} \u{062F}\u{0631} \u{0630}\u{062E}\u{06CC}\u{0631}\u{0647} \u{0645}\u{062E}\u{0627}\u{0637}\u{0628}")
                ]);
        }
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
                'owner_user_id'         => auth()->id(),
                'full_name'             => $fullName,
                'company'               => $contact->company,
                'email'                 => $contact->email,
                'mobile'                => $contact->mobile,
                'phone'                 => $contact->phone,
                'address'               => $contact->address,
                'state'                 => $contact->state,
                'city'                  => $contact->city,
                'lead_source'           => 'contact',
                'lead_status'           => 'new',
                'assigned_to'           => $contact->assigned_to,
                'lead_date'             => now()->toDateString(),
                'next_follow_up_date'   => now()->addDays(7)->toDateString(),
                'created_by'            => auth()->id(),
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
                'name'    => trim($request->input('new_org_name')),
                'website' => $request->input('new_org_website'),
                'address' => $request->input('new_org_address') ?: ($payload['address'] ?? null),
                'phone'   => $request->input('phone'),
                'state'   => $payload['state'] ?? null,
                'city'    => $payload['city'] ?? null,
            ]);

            $payload['organization_id'] = $organization->id;
            $payload['company']         = $organization->name;
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
            $payload['company']         = $organization->name;
        }

        return $payload;
    }

    protected function cleanupMobileInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[\s\-]+/u', '', $value) ?? '';
        $cleaned = trim($cleaned);

        return $cleaned === '' ? null : $cleaned;
    }

    public function bulkDelete(Request $request)
    {
        Contact::whereIn('id', $request->input('selected_contacts', []))->delete();

        return redirect()
            ->route('sales.contacts.index')
            ->with('success', 'مخاطبین انتخاب‌شده با موفقیت حذف شدند.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()
            ->route('sales.contacts.index')
            ->with('success', 'مخاطب با موفقیت حذف شد.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new ContactsImport, $request->file('file'));

            Log::info(
                'فایل ایمپورت مخاطبین با موفقیت توسط کاربر زیر ایمپورت شد: ' . auth()->user()->email
            );

            return redirect()
                ->back()
                ->with('success', 'فایل مخاطبین با موفقیت ایمپورت شد.');
        } catch (\Exception $e) {
            Log::error('خطا در ایمپورت مخاطبین: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'خطا در ایمپورت مخاطبین: ' . $e->getMessage());
        }
    }
}

