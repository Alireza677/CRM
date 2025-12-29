<?php

namespace App\Http\Controllers;

use App\Models\SalesLead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Activity as CrmActivity;
use App\Models\User;
use App\Models\LeadRoundRobinUser;
use App\Models\LeadRoundRobinSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\FormOptionsHelper;
use Illuminate\Validation\Rule;
use App\Helpers\DateHelper;
use Spatie\Activitylog\Models\Activity;
use App\Http\Controllers\Concerns\LeadsBreadcrumbs;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Services\DuplicateMobileFinder;
class SalesLeadController extends Controller
{
    use LeadsBreadcrumbs;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin')->only('destroy');
    }


    public function index(Request $request)
    {
        $query = SalesLead::visibleFor(auth()->user(), 'leads')
            ->with('assignedUser')
            ->activeListing();


        $this->applyLeadFilters($request, $query);

        $listingData = $this->prepareLeadListingData($request, $query);
        $tabCounts = SalesLead::tabCountsFor($request->user());

        return view('marketing.leads.index', array_merge($listingData, [
            'leadListingRoute' => 'marketing.leads.index',
            'isJunkListing' => false,
            'leadTabCounts' => $tabCounts,
        ]))->with('breadcrumb', $this->leadsBreadcrumb([], false));
    }

   public function junk(Request $request)
{
    $query = SalesLead::visibleFor(auth()->user(), 'leads')
        ->with('assignedUser')
        ->junkListing();

    // در لیست سرکاری‌ها فیلتر وضعیت غیرفعال می‌ماند
    $this->applyLeadFilters($request, $query, false);

    $listingData = $this->prepareLeadListingData($request, $query);
    $tabCounts = SalesLead::tabCountsFor($request->user());

    return view('marketing.leads.index', array_merge($listingData, [
        'leadListingRoute' => 'sales.leads.junk',
        'isJunkListing' => true,
        'leadTabCounts' => $tabCounts,
    ]))->with('breadcrumb', $this->leadsBreadcrumb([
        ['title' => 'سرکاری‌ها'],
    ], false));
}


    protected function applyLeadFilters(Request $request, Builder $query, bool $allowStatusFilter = true): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            });
        }

        if ($request->filled('lead_source')) {
            $query->where('lead_source', $request->lead_source);
        }

        if ($allowStatusFilter) {
            $statusFilter = $request->input('status', $request->lead_status);
            if (!empty($statusFilter)) {
                $query->where('lead_status', $statusFilter);
            }
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
    }

    protected function prepareLeadListingData(Request $request, Builder $query): array
    {
        $perPageOptions = [20, 50, 100, 200];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }

        $leads = $query->latest()->paginate($perPage)->appends($request->query());

        $favoriteLeadIds = [];
        if ($request->user()) {
            $favoriteLeadIds = DB::table('lead_favorites')
                ->where('user_id', $request->user()->id)
                ->whereIn('lead_id', $leads->pluck('id'))
                ->pluck('lead_id')
                ->toArray();
        }

        $users = User::all();
        $leadSources = FormOptionsHelper::leadSources();

        return [
            'leads' => $leads,
            'users' => $users,
            'leadSources' => $leadSources,
            'favoriteLeadIds' => $favoriteLeadIds,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'leadPoolRules' => $this->leadPoolRulesData(),
        ];
    }

    protected function leadPoolRulesData(): array
    {
        $settings = LeadRoundRobinSetting::query()->first();

        $firstActivityValue = $settings?->sla_duration_value ?? 24;
        $firstActivityUnit = $settings?->sla_duration_unit ?? 'hours';
        $firstActivityLabel = $firstActivityUnit === 'minutes'
            ? $firstActivityValue . ' دقیقه'
            : $firstActivityValue . ' ساعت';

        $maxReassignments = $settings?->max_reassign_count ?? 3;
        $finalDecisionDays = data_get($settings, 'final_decision_days') ?? 14;

        return [
            'first_activity_deadline_label' => $firstActivityLabel,
            'max_reassignments' => $maxReassignments,
            'final_decision_days' => $finalDecisionDays,
        ];
    }

    public function converted(Request $request)
    {
        $query = SalesLead::visibleFor(auth()->user(), 'leads')
            ->with(['assignedUser', 'convertedOpportunity'])
            ->convertedListing();

        $this->applyLeadFilters($request, $query);

        $perPageOptions = [20, 50, 100, 200];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }

        $leads = $query->latest('converted_at')->paginate($perPage)->appends($request->query());

        $favoriteLeadIds = [];
        if ($request->user()) {
            $favoriteLeadIds = \DB::table('lead_favorites')
                ->where('user_id', $request->user()->id)
                ->whereIn('lead_id', $leads->pluck('id'))
                ->pluck('lead_id')
                ->toArray();
        }

        $users = User::all();
        $leadSources = \App\Helpers\FormOptionsHelper::leadSources();
        $leadTabCounts = SalesLead::tabCountsFor($request->user());

        return view('marketing.leads.converted', compact(
            'leads',
            'users',
            'leadSources',
            'favoriteLeadIds',
            'perPage',
            'perPageOptions',
            'leadTabCounts'
        ))->with('breadcrumb', $this->leadsBreadcrumb([
            ['title' => 'سرنخ‌های تبدیل‌شده'],
        ], false));

    }

   public function create(Request $request)
{
    $users = User::all();
    $referrals = $users;

    $contacts = Contact::select('id', 'first_name', 'last_name', 'mobile')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    $lead = new SalesLead();
    $contactId = $request->input('contact_id');
    if (!empty($contactId)) {
        $contact = Contact::visibleFor($request->user(), 'contacts')->find($contactId);
        if ($contact) {
            $lead->contact_id = $contact->id;
            $lead->full_name = $contact->full_name ?: ($contact->company ?? null);
            $lead->mobile = $contact->mobile;
            $lead->state = $contact->state;
            $lead->city = $contact->city;
        }
    }

    return view('marketing.leads.create', compact('users', 'referrals', 'contacts', 'lead'))
        ->with('breadcrumb', $this->leadsBreadcrumb([
            ['title' => 'ایجاد سرنخ'],
        ]));
}


 public function store(\Illuminate\Http\Request $request)
{
    // ---------- Logging helpers ----------
    $traceId = (string) \Illuminate\Support\Str::uuid();

    $sanitizeForLog = function (array $data): array {
        $data = \Illuminate\Support\Arr::except($data, ['_token', 'password', 'password_confirmation']);

        // Mask sensitive-ish fields
        if (!empty($data['mobile'])) {
            $m = (string) $data['mobile'];
            $data['mobile'] = (mb_strlen($m) >= 4) ? (str_repeat('*', max(0, mb_strlen($m) - 4)) . mb_substr($m, -4)) : '***';
        }
        if (!empty($data['email'])) {
            $data['email'] = '***';
        }

        // Prevent huge logs
        if (isset($data['notes']) && is_string($data['notes']) && mb_strlen($data['notes']) > 500) {
            $data['notes'] = mb_substr($data['notes'], 0, 500) . '...';
        }

        return $data;
    };

    \Log::withContext([
        'trace_id' => $traceId,
        'actor_id' => \Illuminate\Support\Facades\Auth::id(),
        'ip'       => $request->ip(),
        'route'    => optional($request->route())->getName(),
    ]);

    \Log::info('🐙 leads.store.started', [
        'method' => $request->method(),
        'url'    => $request->fullUrl(),
    ]);

    \Log::debug('🐙 leads.store.raw_request', [
        'input' => $sanitizeForLog($request->all()),
    ]);

    // ---------- Validation ----------
    $validator = \Illuminate\Support\Facades\Validator::make(
        $request->all(),
        [
            'prefix' => 'nullable|string|max:10',
            'full_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'create_contact' => 'nullable|boolean',
            'contact_id' => 'nullable|exists:contacts,id',
            'confirm_use_existing_contact' => 'nullable|boolean',
            'existing_contact_id' => 'nullable|exists:contacts,id',
            'lead_source' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(FormOptionsHelper::leadSources()))],

            'lead_status' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(FormOptionsHelper::leadStatuses()))],
            'disqualify_reason' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(FormOptionsHelper::leadDisqualifyReasons()))],
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
            'building_usage' => 'nullable|string|max:255',
            'internal_temperature' => 'nullable|numeric',
            'external_temperature' => 'nullable|numeric',
            'building_length' => 'nullable|numeric|min:0',
            'building_width' => 'nullable|numeric|min:0',
            'eave_height' => 'nullable|numeric|min:0',
            'ridge_height' => 'nullable|numeric|min:0',
            'wall_material' => 'nullable|string|max:255',
            'insulation_status' => 'nullable|string|in:good,medium,weak',
            'spot_heating_systems' => 'nullable|integer|min:0',
            'central_200_systems' => 'nullable|integer|min:0',
            'central_300_systems' => 'nullable|integer|min:0',
        ],
        [
            'full_name.required' => 'نام و نام خانوادگی الزامی است.',
            'email.email'        => 'فرمت ایمیل نامعتبر است.',
            'website.url'        => 'فرمت وب‌سایت نامعتبر است.',
        ]
    );

    if ($validator->fails()) {
        \Log::warning('🔴 leads.store.validation_failed', [
            'errors' => $validator->errors()->toArray(),
            'input'  => $sanitizeForLog($request->all()),
        ]);

        return redirect()->back()->withErrors($validator)->withInput();
    }

    try {
        $validated = $validator->validated();

        \Log::info('🟢 leads.store.validation_passed', [
            'validated' => $sanitizeForLog($validated),
        ]);

        // ---------- Normalize / Prepare payload ----------
        $confirmUseExistingContact = (bool) ($validated['confirm_use_existing_contact'] ?? false);
        $existingContactId = $validated['existing_contact_id'] ?? null;
        unset($validated['confirm_use_existing_contact'], $validated['existing_contact_id']);

        $duplicateFinder = app(DuplicateMobileFinder::class);
        $normalizedMobile = $duplicateFinder->normalizeMobile($validated['mobile'] ?? null);

       if ($normalizedMobile) {
            $validated['mobile'] = $normalizedMobile;

            \Log::info('leads.store.mobile.normalized', [
                'mobile' => $sanitizeForLog(['mobile' => $normalizedMobile])['mobile'],
            ]);
        } else {
            $validated['mobile'] = $this->cleanupMobileInput($validated['mobile'] ?? null);

            \Log::info('leads.store.mobile.cleaned', [
                'mobile' => $sanitizeForLog(['mobile' => $validated['mobile']])['mobile'],
            ]);
        }

        $selectedContactId = $validated['contact_id'] ?? null;
        if ($confirmUseExistingContact) {
            $confirmedContact = null;
            if ($existingContactId) {
                $confirmedContact = Contact::visibleFor($request->user(), 'contacts')->find($existingContactId);
            }

            $contactMobileNormalized = $confirmedContact
                ? $duplicateFinder->normalizeMobile($confirmedContact->mobile ?? null)
                : null;

            if (!$confirmedContact || !$contactMobileNormalized || ($normalizedMobile && $contactMobileNormalized !== $normalizedMobile)) {
                return redirect()
                    ->back()
                    ->withErrors(['mobile' => '??????? ????? ?????????? ?? ????? ?????? ?????? ?????.'])
                    ->withInput();
            }

            $contactFullName = trim((string) $confirmedContact->first_name . ' ' . (string) $confirmedContact->last_name);
            $missingFields = [];

            if ($contactFullName === '') {
                $missingFields['full_name'] = '??? ????? ???? ???.';
            }

            if (!$contactMobileNormalized) {
                $missingFields['mobile'] = '????? ?????? ????? ???? ???.';
            }

            if (empty($confirmedContact->state)) {
                $missingFields['state'] = '????? ????? ???? ???.';
            }

            if (empty($confirmedContact->city)) {
                $missingFields['city'] = '??? ????? ???? ???.';
            }

            if (!empty($missingFields)) {
                return redirect()
                    ->back()
                    ->withErrors($missingFields)
                    ->with('error', '??????? ????? ???? ???? ????? ????? ???? ?? ????? ????.')
                    ->withInput();
            }

            $normalizedMobile = $contactMobileNormalized;
            $validated['contact_id'] = $confirmedContact->id;
            $validated['create_contact'] = false;
            $validated['full_name'] = $contactFullName;
            $validated['mobile'] = $contactMobileNormalized;
            $validated['state'] = $confirmedContact->state;
            $validated['city'] = $confirmedContact->city;

            $selectedContactId = $confirmedContact->id;
        }


        $shouldCreateContact = empty($selectedContactId) && (bool) ($validated['create_contact'] ?? false);

        $validated['contact_id'] = $selectedContactId ? (int) $selectedContactId : null;
        unset($validated['create_contact']);

        $validated['created_by'] = \Illuminate\Support\Facades\Auth::id();
        $validated['owner_user_id'] = \Illuminate\Support\Facades\Auth::id();
        $validated['do_not_email'] = $request->has('do_not_email');
        $validated['lead_date'] = DateHelper::normalizeDateInput($validated['lead_date'] ?? null);

        $leadStatusValue = SalesLead::normalizeStatus($validated['lead_status'] ?? SalesLead::STATUS_NEW);
        $validated['status'] = $leadStatusValue;
        $validated['lead_status'] = $leadStatusValue;

        if ($leadStatusValue === SalesLead::STATUS_DISCARDED) {
            $validated['next_follow_up_date'] = null;
        } else {
            $validated['next_follow_up_date'] = DateHelper::normalizeDateInput($validated['next_follow_up_date'] ?? null);
        }

        \Log::info('🔧 leads.store.normalized', [
            'should_create_contact' => $shouldCreateContact,
            'status'                => $leadStatusValue,
            'lead_date'             => $validated['lead_date'] ?? null,
            'next_follow_up_date'   => $validated['next_follow_up_date'] ?? null,
        ]);

        // ---------- Duplicate mobile check ----------
        if ($normalizedMobile) {
            $existingLead = $duplicateFinder->findLeadByMobile($normalizedMobile);
            if ($existingLead) {
                \Log::warning('?? leads.store.duplicate_lead_by_mobile', [
                    'lead_id' => $existingLead->id ?? null,
                    'mobile' => $sanitizeForLog(['mobile' => $normalizedMobile])['mobile'],
                ]);

                $alertPayload = $duplicateFinder->buildModalPayload(
                    $existingLead,
                    DuplicateMobileFinder::TYPE_LEAD,
                    $normalizedMobile,
                    ['intent' => 'block']
                );
                $alertPayload['intent'] = 'block';

                return redirect()
                    ->route('marketing.leads.create')
                    ->withErrors(['mobile' => 'Oاین شماره موبایل قبلاً در سرنخ دیگری ثبت شده است.'])
                    ->with('duplicate_mobile_alert', $alertPayload)
                    ->withInput();
            }
        }

        if (!empty($selectedContactId)) {
            $existingLeadByContact = SalesLead::query()
                ->where('contact_id', $selectedContactId)
                ->latest()
                ->first();

            if ($existingLeadByContact) {
                \Log::warning('?? leads.store.duplicate_lead_by_contact', [
                    'lead_id' => $existingLeadByContact->id ?? null,
                    'contact_id' => $selectedContactId,
                ]);

                $leadMobile = $normalizedMobile
                    ?: $duplicateFinder->normalizeMobile($existingLeadByContact->mobile ?? $existingLeadByContact->phone);

                $alertPayload = $duplicateFinder->buildModalPayload(
                    $existingLeadByContact,
                    DuplicateMobileFinder::TYPE_LEAD,
                    $leadMobile ?? ''
                );
                $alertPayload['intent'] = 'block';

                return redirect()
                    ->route('marketing.leads.create')
                    ->withErrors(['mobile' => 'Oبرای این مخاطب قبلاً سرنخ ثبت شده است و امکان ثبت جدید وجود ندارد.'])
                    ->with('duplicate_mobile_alert', $alertPayload)
                    ->withInput();
            }
        }

        if ($normalizedMobile && empty($selectedContactId)) {
            $existingContact = $duplicateFinder->findContactByMobile($normalizedMobile);
            if ($existingContact && !$confirmUseExistingContact) {
                \Log::info('dY"? leads.store.duplicate_contact_by_mobile', [
                    'contact_id' => $existingContact->id ?? null,
                    'mobile' => $sanitizeForLog(['mobile' => $normalizedMobile])['mobile'],
                ]);

                $alertPayload = $duplicateFinder->buildModalPayload(
                    $existingContact,
                    DuplicateMobileFinder::TYPE_CONTACT,
                    $normalizedMobile,
                    [
                        'intent' => 'confirm_contact',
                        'contact' => [
                            'id' => $existingContact->id,
                            'name' => $existingContact->full_name,
                            'mobile' => $existingContact->mobile,
                            'state' => $existingContact->state,
                            'city' => $existingContact->city,
                        ],
                    ]
                );
                $alertPayload['intent'] = 'confirm_contact';

                return redirect()
                    ->back()
                    ->with('duplicate_mobile_alert', $alertPayload)
                    ->withInput();
            }
        }
        \Log::info('🧾 leads.store.final_payload_before_create', [
            'payload' => $sanitizeForLog($validated),
        ]);

        // ---------- Create inside transaction ----------
        $lead = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $shouldCreateContact) {
            $payload = $validated;

            if (empty($payload['assigned_to'])) {
                $nextRoundRobin = LeadRoundRobinUser::query()
                    ->where('is_active', true)
                    ->orderByRaw('last_assigned_at IS NOT NULL')
                    ->orderBy('last_assigned_at')
                    ->first();

                if ($nextRoundRobin) {
                    $payload['assigned_to'] = $nextRoundRobin->user_id;
                    $nextRoundRobin->forceFill(['last_assigned_at' => now()])->save();

                    \Log::info('🎯 leads.store.round_robin_assigned', [
                        'assigned_to' => $payload['assigned_to'],
                        'round_robin_id' => $nextRoundRobin->id ?? null,
                    ]);
                } else {
                    \Log::warning('⚠️ leads.store.round_robin_empty_active_list');
                }
            }

            $lead = SalesLead::create($payload);

            \Log::info('✅ leads.store.lead_created', [
                'lead_id' => $lead->id ?? null,
            ]);

            if ($shouldCreateContact && $lead) {
                $contact = $this->createContactFromLead($lead);

                if ($contact) {
                    $lead->forceFill(['contact_id' => $contact->id])->saveQuietly();

                    \Log::info('👤 leads.store.contact_created_from_lead', [
                        'lead_id'    => $lead->id,
                        'contact_id' => $contact->id,
                    ]);
                } else {
                    \Log::warning('⚠️ leads.store.contact_create_failed', [
                        'lead_id' => $lead->id ?? null,
                    ]);
                }
            }

            return $lead;
        });

        if ($lead && $lead->id) {
            \Log::info('✔ leads.store.completed', [
                'lead_id' => $lead->id,
            ]);

            return redirect()
                ->route('marketing.leads.index')
                ->with('success', 'سرنخ فروش با موفقیت ایجاد شد.');
        }

        \Log::error('❌ leads.store.failed_no_id_returned');

        return redirect()->back()
            ->with('error', 'ایجاد سرنخ فروش انجام نشد. لطفاً اطلاعات را بررسی کنید و دوباره تلاش کنید.')
            ->withInput();

    } catch (\Throwable $e) {
        \Log::error('🔥 leads.store.exception', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        return redirect()->back()
            ->with('error', 'خطا در ایجاد سرنخ فروش: ' . $e->getMessage())
            ->withInput();
    }
}




    private function createContactFromLead(SalesLead $lead): ?Contact
    {
        $hasAnyValue = !empty($lead->full_name)
            || !empty($lead->company)
            || !empty($lead->email)
            || !empty($lead->mobile)
            || !empty($lead->phone);

        if (!$hasAnyValue) {
            return null;
        }

        [$firstName, $lastName] = $this->splitLeadName($lead->full_name);

        return Contact::create([
            'owner_user_id' => $lead->owner_user_id ?? Auth::id(),
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'email'         => $lead->email,
            'mobile'        => $lead->mobile,
            'phone'         => $lead->phone,
            'company'       => $lead->company,
            'state'         => $lead->state,
            'city'          => $lead->city,
            'address'       => $lead->address,
            'website'       => $lead->website,
            'assigned_to'   => $lead->assigned_to,
        ]);
    }

    private function splitLeadName(?string $fullName): array
    {
        if (!$fullName) {
            return [null, null];
        }

        $parts = preg_split('/\s+/', trim($fullName));
        $lastName = array_pop($parts);
        $firstName = trim(implode(' ', $parts));

        if ($firstName === '') {
            $firstName = $lastName;
            $lastName = null;
        }

        return [$firstName ?: null, $lastName ?: null];
    }

    public function normalizeMobile(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        static $digitMap = [
            "\u{06F0}" => '0', "\u{06F1}" => '1', "\u{06F2}" => '2', "\u{06F3}" => '3', "\u{06F4}" => '4',
            "\u{06F5}" => '5', "\u{06F6}" => '6', "\u{06F7}" => '7', "\u{06F8}" => '8', "\u{06F9}" => '9',
            "\u{0660}" => '0', "\u{0661}" => '1', "\u{0662}" => '2', "\u{0663}" => '3', "\u{0664}" => '4',
            "\u{0665}" => '5', "\u{0666}" => '6', "\u{0667}" => '7', "\u{0668}" => '8', "\u{0669}" => '9',
        ];

        $value = strtr($value, $digitMap);
        $value = preg_replace('/[^\d+]/u', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '098')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = substr($digits, -10);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        } elseif (strlen($digits) > 11) {
            $lastTen = substr($digits, -10);
            if ($lastTen !== false && strlen($lastTen) === 10 && str_starts_with($lastTen, '9')) {
                $digits = '0' . $lastTen;
            }
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return $digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    private function mobileComparisonVariants(string $normalized): array
    {
        $digits = preg_replace('/\D+/', '', $normalized) ?? '';
        if ($digits === '') {
            return [];
        }

        $variants = [$digits];
        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            $withoutZero = substr($digits, 1);
            $variants[] = $withoutZero;
            $variants[] = '98' . $withoutZero;
            $variants[] = '098' . $withoutZero;
            $variants[] = '0098' . $withoutZero;
        } else {
            $variants[] = ltrim($digits, '0');
        }

        return array_values(array_unique(array_filter($variants)));
    }

    private function buildMobileRegexFromDigits(string $digits): string
    {
        $parts = preg_split('//u', $digits, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return '';
        }

        $escaped = array_map(static fn (string $part) => preg_quote($part, '/'), $parts);

        return implode('[^0-9]*', $escaped);
    }

    public function findLeadByNormalizedMobile(string $normalized, ?int $ignoreLeadId = null): ?SalesLead
    {
        $variants = $this->mobileComparisonVariants($normalized);
        if (empty($variants)) {
            return null;
        }

        $query = SalesLead::query()
            ->select(['id', 'mobile', 'lead_status', 'status', 'full_name'])
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->when($ignoreLeadId, fn ($q) => $q->where('id', '<>', $ignoreLeadId));

        $query->where(function ($q) use ($variants) {
            $applied = false;
            foreach ($variants as $digits) {
                $pattern = $this->buildMobileRegexFromDigits($digits);
                if ($pattern === '') {
                    continue;
                }
                $applied = true;
                $q->orWhereRaw('mobile REGEXP ?', [$pattern]);
            }

            if (!$applied) {
                $q->whereRaw('1 = 0');
            }
        });

        $candidates = $query->limit(20)->get();

        return $candidates->first(function (SalesLead $lead) use ($normalized) {
            return $this->normalizeMobile($lead->mobile) === $normalized;
        });
    }

    private function duplicateLeadAlertPayload(SalesLead $lead): array
    {
        return [
            'id' => $lead->id,
            'url' => route('marketing.leads.show', $lead),
            'mobile' => $lead->mobile,
            'full_name' => $lead->full_name,
        ];
    }

    public function cleanupMobileInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[\s\-]+/u', '', $value) ?? '';
        $cleaned = trim($cleaned);

        return $cleaned === '' ? null : $cleaned;
    }

    public function reactivateDiscardedLead(SalesLead $lead, array $payload, bool $shouldCreateContact): SalesLead
    {
        return DB::transaction(function () use ($lead, $payload, $shouldCreateContact) {
            $updatable = $payload;
            unset($updatable['created_by'], $updatable['owner_user_id']);

            $updatable['lead_status'] = SalesLead::STATUS_NEW;
            $updatable['status'] = SalesLead::STATUS_NEW;
            $updatable['disqualify_reason'] = null;

            $originalLeadSource = $lead->lead_source;
            if (!empty($originalLeadSource)) {
                // Keep the original source when reactivating a discarded lead.
                $updatable['lead_source'] = $originalLeadSource;
            }

            $lead->fill($updatable);
            $lead->is_reengaged = true;
            $lead->reengaged_at = now();
            $lead->save();

            if ($shouldCreateContact && empty($lead->contact_id)) {
                $contact = $this->createContactFromLead($lead);
                if ($contact) {
                    $lead->contact_id = $contact->id;
                    $lead->save();
                }
            }

            return $lead->refresh();
        });
    }

public function bulkDelete(Request $request)
{
    $leadIds = $request->input('selected_leads', []);

    if (!empty($leadIds)) {
        SalesLead::whereIn('id', $leadIds)->delete();
    }

    return redirect()
        ->route('marketing.leads.index')
        ->with('success', 'سرنخ‌ها با موفقیت حذف شدند.');
}


  public function edit(SalesLead $lead)
{
    $users = User::all();
    $referrals = $users;
    $hasRecentActivity = $lead->hasRecentActivity();

    return view('marketing.leads.edit', compact('lead', 'users', 'referrals', 'hasRecentActivity'))
        ->with('breadcrumb', $this->leadsBreadcrumb([
            ['title' => 'ویرایش سرنخ'],
        ]));
}






public function update(Request $request, SalesLead $lead)
{
    Log::info('SalesLeadController@update reached');
    Log::info('SalesLeadController@update payload', $request->all());

    // 1) نرمال‌سازی وضعیت (برای شرط‌های بعدی)
    $statusVal = SalesLead::normalizeStatus($request->input('lead_status', ''));

    // 2) نرمال‌سازی تاریخ‌ها قبل از validate
    //    (باید خروجی Y-m-d میلادی بده یا null)
    $leadDateConv = DateHelper::normalizeDateInput($request->input('lead_date'));
    $nextFollowUpConv = ($statusVal === SalesLead::STATUS_DISCARDED)
        ? null
        : DateHelper::normalizeDateInput($request->input('next_follow_up_date'));

    $request->merge([
        'lead_status' => $statusVal,
        'lead_date' => $leadDateConv,
        'next_follow_up_date' => $nextFollowUpConv,
    ]);

    // 3) اگر status=discarded و disqual_reason_body خالی بود ولی چک‌باکس‌ها پر بودند،
    //    دلیل را از روی چک‌باکس‌ها بساز
    $reasonsArr = (array) $request->input('disqual_reasons', []);
    $reasonBody = trim((string) $request->input('disqual_reason_body', ''));

    if ($statusVal === SalesLead::STATUS_DISCARDED && $reasonBody === '' && !empty($reasonsArr)) {
        $request->merge([
            'disqual_reason_body' => implode('، ', array_filter($reasonsArr)),
        ]);
        $reasonBody = trim((string) $request->input('disqual_reason_body', ''));
    }

    // 4) اعتبارسنجی اصلی
    //    - next_follow_up_date فقط وقتی discarded نیست لازم باشد
    $rules = [
        'prefix' => 'nullable|string|max:10',
        'full_name' => 'required|string|max:255',
        'company' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'mobile' => 'nullable|string|max:20',
        'phone' => 'nullable|string|max:20',
        'website' => 'nullable|url|max:255',

        'lead_source' => ['required', 'string', Rule::in(array_keys(FormOptionsHelper::leadSources()))],
        'lead_status' => ['required', 'string', Rule::in(array_keys(FormOptionsHelper::leadStatuses()))],

        // اگر این فیلد را واقعاً استفاده نمی‌کنی حذفش کن؛ الان در فرم تو disqual_reasons[] داری
        // 'disqualify_reason' => ['nullable','string', Rule::in(array_keys(FormOptionsHelper::leadDisqualifyReasons()))],

        'assigned_to' => 'nullable|exists:users,id',
        'referred_to' => 'nullable|exists:users,id',

        'lead_date' => 'required|date',
        'next_follow_up_date' => [
            'nullable',
            'date',
            // فقط وقتی discarded نیست لازم باشد:
            Rule::requiredIf(fn() => $statusVal !== SalesLead::STATUS_DISCARDED),
        ],

        'do_not_email' => 'nullable|boolean',

        'industry' => 'nullable|string|max:255',
        'nationality' => 'nullable|string|max:255',
        'main_test_field' => 'nullable|string|max:255',
        'dependent_test_field' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:1000',
        'state' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'notes' => 'nullable|string',

        'building_usage' => 'nullable|string|max:255',
        'internal_temperature' => 'nullable|numeric',
        'external_temperature' => 'nullable|numeric',
        'building_length' => 'nullable|numeric|min:0',
        'building_width' => 'nullable|numeric|min:0',
        'eave_height' => 'nullable|numeric|min:0',
        'ridge_height' => 'nullable|numeric|min:0',
        'wall_material' => 'nullable|string|max:255',
        'insulation_status' => 'nullable|string|in:good,medium,weak',

        'spot_heating_systems' => 'nullable|integer|min:0',
        'central_200_systems' => 'nullable|integer|min:0',
        'central_300_systems' => 'nullable|integer|min:0',

        'activity_override' => ['nullable','boolean'],
        'quick_note_body' => ['nullable','string','max:5000'],

        // مهم: دلیل از دست رفتن
        'disqual_reason_body' => ['nullable','string','max:5000'],

        // چک‌باکس‌ها (برای اینکه خطای silent نده/یا دیتا حذف نشه)
        'disqual_reasons' => ['nullable','array'],
        'disqual_reasons.*' => ['nullable','string','max:255'],
    ];

    $messages = [
        'next_follow_up_date.required' => 'برای این وضعیت، تاریخ پیگیری بعدی الزامی است.',
        'lead_date.date' => 'تاریخ ثبت سرنخ معتبر نیست.',
        'next_follow_up_date.date' => 'تاریخ پیگیری بعدی معتبر نیست.',
    ];

    $data = $request->validate($rules, $messages);

    // 5) اگر تغییر وضعیت به discarded انجام شده، دلیل را اجباری کن (فقط هنگام تغییر)
    $originalStatus = SalesLead::normalizeStatus($lead->lead_status ?? $lead->status);
    $newStatus = SalesLead::normalizeStatus($data['lead_status'] ?? $originalStatus);

    $statusChanged = $originalStatus !== $newStatus;
    $isDiscardedChange = $statusChanged && $newStatus === SalesLead::STATUS_DISCARDED;

    $overrideRequested = (bool) $request->boolean('activity_override');
    $quickNoteBody = trim((string) $request->input('quick_note_body', ''));
    $statusReasonBody = trim((string) ($data['disqual_reason_body'] ?? ''));

    if ($isDiscardedChange) {
        if ($statusReasonBody === '') {
            return back()
                ->withErrors(['disqual_reason_body' => 'ذکر دلیل از دست رفتن سرنخ الزامی است.'])
                ->withInput();
        }

        // اگر Quick note خالی بود، از دلیل پرش کن
        if ($quickNoteBody === '') {
            $quickNoteBody = $statusReasonBody;
        }

        // با این تغییر، گارد فعالیت را هم عملاً override می‌کنی
        $overrideRequested = true;
    }

    // 6) نرمال‌سازی موبایل و جلوگیری از تکراری
    $normalizedMobile = $this->normalizeMobile($data['mobile'] ?? null);

    if ($normalizedMobile) {
        $duplicateLead = $this->findLeadByNormalizedMobile($normalizedMobile, $lead->id);

        if ($duplicateLead) {
            return back()
                ->withErrors(['mobile' => 'این شماره موبایل قبلاً برای سرنخ دیگری ثبت شده است.'])
                ->with('duplicate_lead_alert', $this->duplicateLeadAlertPayload($duplicateLead))
                ->withInput();
        }

        $data['mobile'] = $normalizedMobile;
    } else {
        $data['mobile'] = $this->cleanupMobileInput($data['mobile'] ?? null);
    }

    // 7) گارد تغییر وضعیت (فعالیت اخیر)
    $canChangeStage = true;

    if ($statusChanged) {
        $canChangeStage = $isDiscardedChange ? true : $lead->canChangeStageTo($newStatus);

        if (!$canChangeStage && $overrideRequested && $quickNoteBody !== '') {
            $lead->notes()->create([
                'body' => $quickNoteBody,
                'user_id' => auth()->id(),
            ]);

            if (method_exists($lead, 'markFirstActivity')) {
                $lead->markFirstActivity(now());
            }

            $canChangeStage = true;

            Log::info('lead_stage_guard_overridden_with_note', [
                'lead_id' => $lead->id,
                'original_status' => $originalStatus,
                'new_status' => $newStatus,
                'user_id' => auth()->id(),
            ]);
        }

        if (!$canChangeStage) {
            Log::info('lead_stage_guard_blocked', [
                'lead_id' => $lead->id,
                'original_status' => $originalStatus,
                'new_status' => $newStatus,
            ]);

            return back()
                ->withErrors(['lead_status' => 'تغییر وضعیت بدون فعالیت تماس، جلسه یا یادداشت اخیر مجاز نیست.'])
                ->withInput();
        }
    }

    // 8) notes اولیه immutable
    if (array_key_exists('notes', $data)) {
        unset($data['notes']);
    }

    // 9) اگر discarded شد، next_follow_up_date را null کن
    if ($newStatus === SalesLead::STATUS_DISCARDED) {
        $data['next_follow_up_date'] = null;
    }

    // 10) ذخیره دلیل به عنوان Note و Activity (اختیاری اما مفید)
    if ($isDiscardedChange && $statusReasonBody !== '') {
        $lead->notes()->create([
            'body' => $statusReasonBody,
            'user_id' => auth()->id(),
        ]);

        try {
            $creatorId = auth()->id() ?: $lead->assigned_to;
            $assigneeId = $lead->assigned_to ?: $creatorId;

          

            if (method_exists($lead, 'markFirstActivity')) {
                $activityTime = $activity->start_at ?? $activity->created_at ?? now();
                $lead->markFirstActivity($activityTime);
            }
        } catch (\Throwable $e) {
            Log::warning('lead_status_reason_activity_failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            if (method_exists($lead, 'markFirstActivity')) {
                $lead->markFirstActivity(now());
            }
        }
    }

    // 11) پاک‌سازی فیلدهای غیر دیتابیسی
    unset($data['activity_override'], $data['quick_note_body'], $data['disqual_reasons']);

    // 12) وضعیت را یک‌دست در lead_status و status بنویس
    $data['lead_status'] = $newStatus;
    $data['status'] = $newStatus;

    // 13) checkbox
    $data['do_not_email'] = $request->has('do_not_email');

    // 14) ذخیره
    $lead->fill($data);
    $lead->save();

    return redirect()
        ->route('marketing.leads.index')
        ->with('success', 'تغییرات با موفقیت ذخیره شد.');
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
    $lead->loadCount(['notes', 'activities']);
    $lead->jalali_created_at = DateHelper::toJalali($lead->created_at);
    $lead->jalali_updated_at = DateHelper::toJalali($lead->updated_at);

    $rotationRemainingSeconds = $this->rotationRemainingSeconds($lead);

    // ✓ این خط اضافه شد تا فقط کاربرانی که نام کاربری دارند برگردند
    $allUsers = User::whereNotNull('username')->get();

    return view('marketing.leads.show', compact('lead', 'allUsers', 'rotationRemainingSeconds'))
        ->with('breadcrumb', $this->leadsBreadcrumb([
            ['title' => 'جزئیات سرنخ'],
        ]));
}


    public function loadTab(SalesLead $lead, $tab)
    {
        $view = "marketing.leads.tabs.{$tab}";
        abort_unless(view()->exists($view), 404);

        $lead->loadCount(['notes', 'activities']);
        $data = ['lead' => $lead];

        if ($tab === 'overview') {
            $data['rotationRemainingSeconds'] = $this->rotationRemainingSeconds($lead);
        }

        if ($tab === 'notes') {
            $data['notes'] = $lead->notes()->latest()->get();
            $data['allUsers'] = User::query()
                ->select(['id', 'name', 'username'])
                ->whereNotNull('username')
                ->orderBy('name')
                ->get();
        }

        return view($view, $data);
    }

    private function rotationRemainingSeconds(SalesLead $lead): int
    {
        $rotationDueAt = $lead->rotation_due_at;
        if (!$rotationDueAt) {
            return 0;
        }

        return max(0, Carbon::now()->diffInSeconds($rotationDueAt, false));
    }

    public function convertToOpportunity(Request $request, SalesLead $lead)
{
    if (!empty($lead->converted_at)) {
        return redirect()->back()->with('error', 'این سرنخ قبلاً به فرصت تبدیل شده است.');
    }

    try {
        DB::transaction(function () use ($lead) {
            $organization = null;
            if (!empty($lead->company)) {
                $organization = Organization::firstOrCreate(
                    ['name' => $lead->company],
                    [
                        'phone'   => $lead->phone ?? $lead->mobile,
                        'city'    => $lead->city,
                        'state'   => $lead->state,
                        'address' => $lead->address,
                    ]
                );
            }

            $firstName = null;
            $lastName  = null;
            if (!empty($lead->full_name)) {
                $parts     = preg_split('/\s+/', trim($lead->full_name));
                $lastName  = array_pop($parts);
                $firstName = trim(implode(' ', $parts));
                if ($firstName === '') {
                    $firstName = $lastName;
                    $lastName  = '';
                }
            }

            $contact = null;
            if (!empty($firstName) || !empty($lastName)) {
                $contact = Contact::create([
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'email'           => $lead->email,
                    'mobile'          => $lead->mobile,
                    'phone'           => $lead->phone,
                    'company'         => $lead->company,
                    'city'            => $lead->city,
                    'state'           => $lead->state,
                    'address'         => $lead->address,
                    'organization_id' => $organization?->id,
                    'assigned_to'     => $lead->assigned_to,
                ]);
            }

            $name = $lead->company
                ? ('فرصت - ' . $lead->company)
                : ('فرصت - ' . ($lead->full_name ?: ('سرنخ #' . $lead->id)));

            $opportunity = Opportunity::create([
                'name'            => $name,
                'organization_id' => $organization?->id,
                'contact_id'      => $contact?->id,
                'assigned_to'     => $lead->assigned_to,
                'source'          => $lead->lead_source,
                'next_follow_up'  => $lead->next_follow_up_date,
                'description'     => $lead->notes,
                'stage'           => Opportunity::STAGE_OPEN,
            ]);

            $lead->converted_at             = Carbon::now();
            $lead->converted_opportunity_id = $opportunity->id;
            $lead->converted_by             = Auth::id();
            $lead->status                   = SalesLead::STATUS_CONVERTED;
            $lead->lead_status              = SalesLead::STATUS_CONVERTED;
            $lead->save();

            $this->transferLeadNotesToOpportunity($lead, $opportunity);
            $this->transferLeadActivitiesToOpportunity($lead, $opportunity);
        });

        return redirect()
            ->route('marketing.leads.index')
            ->with('success', 'سرنخ با موفقیت به فرصت فروش تبدیل شد.');
    } catch (\Throwable $e) {
        return redirect()->back()
            ->with('error', 'خطا در تبدیل سرنخ به فرصت: ' . $e->getMessage());
    }
}


    private function transferLeadNotesToOpportunity(SalesLead $lead, Opportunity $opportunity): void
    {
        $lead->leadNotes()
            ->with('mentions')
            ->get()
            ->each(function (Note $note) use ($opportunity) {
                $newNote = $note->replicate(['noteable_id', 'noteable_type']);
                $newNote->noteable_id = $opportunity->id;
                $newNote->noteable_type = Opportunity::class;
                $newNote->save();

                if ($note->mentions->isNotEmpty()) {
                    $payload = $note->mentions->mapWithKeys(function ($user) {
                        return [
                            $user->id => [
                                'created_at'  => $user->pivot->created_at,
                                'updated_at'  => $user->pivot->updated_at,
                                'notified_at' => $user->pivot->notified_at,
                            ],
                        ];
                    })->toArray();

                    $newNote->mentions()->sync($payload);
                }
            });
    }

    private function transferLeadActivitiesToOpportunity(SalesLead $lead, Opportunity $opportunity): void
    {
        $lead->activities()
            ->get()
            ->each(function (Activity $activity) use ($opportunity, $lead) {
                $newActivity = $activity->replicate(['subject_id', 'subject_type', 'log_name']);
                $newActivity->subject_id = $opportunity->id;
                $newActivity->subject_type = Opportunity::class;
                $newActivity->log_name = 'opportunity';
                $properties = $activity->properties ? $activity->properties->toArray() : [];
                $properties['copied_from'] = 'lead';
                $properties['copied_lead_id'] = $lead->id;
                $newActivity->properties = $properties;
                $newActivity->save();
            });
    }
}
