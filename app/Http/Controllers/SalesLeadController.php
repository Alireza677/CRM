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
            ->whereNull('converted_at');

        $query->where(function (Builder $builder) {
    $builder
        ->whereNull('lead_status')
        ->orWhereNotIn('lead_status', [
            SalesLead::STATUS_DISCARDED,
            'lost',
        ]);
});


        $this->applyLeadFilters($request, $query);

        $listingData = $this->prepareLeadListingData($request, $query);

        return view('marketing.leads.index', array_merge($listingData, [
            'leadListingRoute' => 'marketing.leads.index',
            'isJunkListing' => false,
        ]))->with('breadcrumb', $this->leadsBreadcrumb([], false));
    }

   public function junk(Request $request)
{
    $query = SalesLead::visibleFor(auth()->user(), 'leads')
        ->with('assignedUser')
        ->whereNull('converted_at')
        ->where(function (Builder $builder) {
            $builder->whereIn('lead_status', [
                SalesLead::STATUS_DISCARDED,
                'lost', // اگر ثابت STATUS_LOST ندارید
            ]);
        });

    // در لیست سرکاری‌ها فیلتر وضعیت غیرفعال می‌ماند
    $this->applyLeadFilters($request, $query, false);

    $listingData = $this->prepareLeadListingData($request, $query);

    return view('marketing.leads.index', array_merge($listingData, [
        'leadListingRoute' => 'sales.leads.junk',
        'isJunkListing' => true,
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
            ->whereNotNull('converted_at');

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

        $statusFilter = $request->input('status', $request->lead_status);
        if (!empty($statusFilter)) {
            $query->where('lead_status', $statusFilter);
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

        return view('marketing.leads.converted', compact(
            'leads',
            'users',
            'leadSources',
            'favoriteLeadIds',
            'perPage',
            'perPageOptions'
        ))->with('breadcrumb', $this->leadsBreadcrumb([
            ['title' => 'سرنخ‌های تبدیل‌شده'],
        ], false));

    }

   public function create()
{
    $users = User::all();
    $referrals = $users;

    $contacts = Contact::select('id', 'first_name', 'last_name', 'mobile')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    return view('marketing.leads.create', compact('users', 'referrals', 'contacts'))
        ->with('breadcrumb', $this->leadsBreadcrumb([
            ['title' => 'ایجاد سرنخ'],
        ]));
}


    public function store(Request $request)
{
    \Log::info('🐙 store() method started');
    \Log::info('🐙 Raw request input:', $request->all());

    $validator = Validator::make($request->all(), [
        'prefix' => 'nullable|string|max:10',
        'full_name' => 'required|string|max:255',
        'company' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'mobile' => 'nullable|string|max:20',
        'phone' => 'nullable|string|max:20',
        'website' => 'nullable|url|max:255',
        'create_contact' => 'nullable|boolean',
        'contact_id' => 'nullable|exists:contacts,id',
        'lead_source' => ['required', 'string', Rule::in(array_keys(FormOptionsHelper::leadSources()))],

        'lead_status' => ['nullable', 'string', Rule::in(array_keys(FormOptionsHelper::leadStatuses()))],
        'disqualify_reason' => ['nullable', 'string', Rule::in(array_keys(FormOptionsHelper::leadDisqualifyReasons()))],
        'assigned_to' => 'nullable|exists:users,id',
        'lead_date' => 'nullable|string',
        'next_follow_up_date' => 'nullable|string',

        'referred_to' => 'nullable|exists:users,id',
        'do_not_email' => 'boolean',
        // نوع مشتری: جدید / قدیمی / بالقوه
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
    ], [
        'full_name.required' => 'نام و نام خانوادگی الزامی است.',
        'email.email'        => 'فرمت ایمیل نامعتبر است.',
        'website.url'        => 'فرمت وب‌سایت نامعتبر است.',
    ]);

    if ($validator->fails()) {
        \Log::warning('🔴 Validation failed:', $validator->errors()->toArray());
        return redirect()->back()->withErrors($validator)->withInput();
    }

    try {
        $validated = $validator->validated();
        \Log::info('🟢 Validation passed:', $validated);

        $selectedContactId = $validated['contact_id'] ?? null;
        $shouldCreateContact = empty($selectedContactId) && (bool) ($validated['create_contact'] ?? false);
        $validated['contact_id'] = $selectedContactId ? (int) $selectedContactId : null;
        unset($validated['create_contact']);

        $validated['created_by'] = Auth::id();
        // ثبت مالکیت ایجادکننده برای محدوده‌های دسترسی
        $validated['owner_user_id'] = Auth::id();
        $validated['do_not_email'] = $request->has('do_not_email');
        $validated['lead_date'] = DateHelper::normalizeDateInput($validated['lead_date'] ?? null);

        $leadStatusValue = SalesLead::normalizeStatus($validated['lead_status'] ?? SalesLead::STATUS_NEW);
        $validated['status'] = $leadStatusValue;
        $validated['lead_status'] = $leadStatusValue;

        if ($leadStatusValue === SalesLead::STATUS_DISCARDED) {
            // در حالت سرکاری/حذف‌شده تاریخ پیگیری بعدی صفر می‌شود
            $validated['next_follow_up_date'] = null;
        } else {
            $validated['next_follow_up_date'] = DateHelper::normalizeDateInput($validated['next_follow_up_date'] ?? null);
        }

        $normalizedMobile = $this->normalizeMobile($validated['mobile'] ?? null);

        if ($normalizedMobile) {
            $validated['mobile'] = $normalizedMobile;
            $existingLead = $this->findLeadByNormalizedMobile($normalizedMobile);
            if ($existingLead) {
                $existingStatus = SalesLead::normalizeStatus($existingLead->lead_status ?? $existingLead->status);
                if ($existingStatus === SalesLead::STATUS_DISCARDED) {
                    $reactivatedLead = $this->reactivateDiscardedLead($existingLead, $validated, $shouldCreateContact);

                    return redirect()
                        ->route('marketing.leads.show', $reactivatedLead)
                        ->with('success', '??? ????? ????? ?? ???? ????????? ???? ? ?????? ???? ?????? ???? ??.');
                }

                return redirect()->back()
                    ->withErrors(['mobile' => '????? ?? ??? ????? ?????? ????? ??? ??? ???.'])
                    ->with('duplicate_lead_alert', $this->duplicateLeadAlertPayload($existingLead))
                    ->withInput();
            }
        } else {
            $validated['mobile'] = $this->cleanupMobileInput($validated['mobile'] ?? null);
        }



        \Log::info('🔧 Final data before create:', $validated);

        $lead = DB::transaction(function () use ($validated, $shouldCreateContact) {
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
                } else {
                    Log::warning('lead_round_robin_empty_active_list');
                }
            }

            $lead = SalesLead::create($payload);

            if ($shouldCreateContact && $lead) {
                $contact = $this->createContactFromLead($lead);
                if ($contact) {
                    $lead->forceFill(['contact_id' => $contact->id])->saveQuietly();

                    \Log::info('👤 Contact created from lead', [
                        'lead_id' => $lead->id,
                        'contact_id' => $contact->id,
                    ]);
                }
            }


            return $lead;
        });

        if ($lead && $lead->id) {
            \Log::info('✔ Sales lead created successfully with ID: ' . $lead->id);

            return redirect()->route('marketing.leads.index')
                ->with('success', 'سرنخ فروش با موفقیت ایجاد شد.');
        }

        \Log::error('❌ Sales lead creation failed. No ID returned.');

        return redirect()->back()
            ->with('error', 'ایجاد سرنخ فروش انجام نشد. لطفاً اطلاعات را بررسی کنید و دوباره تلاش کنید.')
            ->withInput();

    } catch (\Exception $e) {
        \Log::error('🔥 Exception caught during sales lead creation: ' . $e->getMessage());

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

            $activity = CrmActivity::create([
                'subject'        => 'lead_status_reason',
                'start_at'       => now(),
                'due_at'         => now(),
                'assigned_to_id' => $assigneeId,
                'related_type'   => SalesLead::class,
                'related_id'     => $lead->id,
                'status'         => 'completed',
                'priority'       => 'normal',
                'description'    => $statusReasonBody,
                'is_private'     => false,
                'created_by_id'  => $creatorId,
                'updated_by_id'  => $creatorId,
            ]);

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
    $lead->jalali_created_at = DateHelper::toJalali($lead->created_at);
    $lead->jalali_updated_at = DateHelper::toJalali($lead->updated_at);

    // ✓ این خط اضافه شد تا فقط کاربرانی که نام کاربری دارند برگردند
    $allUsers = User::whereNotNull('username')->get();

    return view('marketing.leads.show', compact('lead', 'allUsers'))
        ->with('breadcrumb', $this->leadsBreadcrumb([
            ['title' => 'جزئیات سرنخ'],
        ]));
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
            $lead->status                   = SalesLead::STATUS_CONVERTED_TO_OPPORTUNITY;
            $lead->lead_status              = SalesLead::STATUS_CONVERTED_TO_OPPORTUNITY;
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
