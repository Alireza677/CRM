<?php

namespace App\Http\Controllers;

use App\Models\SalesLead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Activity as CrmActivity;
use App\Models\User;
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
        $query = SalesLead::visibleFor(auth()->user(), 'leads')->with('assignedUser');
        $query->whereNull('converted_at');

        // Ø¬Ø³Øªâ€ŒÙˆØ¬ÙˆÛŒ Ø¹Ù…ÙˆÙ…ÛŒ
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            });
        }

        // ÙÛŒÙ„ØªØ± Ø¨Ø± Ø§Ø³Ø§Ø³ ÙÛŒÙ„Ø¯Ù‡Ø§ÛŒ Ø®Ø§Øµ
        if ($request->filled('lead_source')) {
            $query->where('lead_source', $request->lead_source);
        }

        $statusFilter = $request->input('status', $request->lead_status);
        if (!empty($statusFilter)) {
            $query->where(function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter)
                    ->orWhere('lead_status', $statusFilter);
            });
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

        // ØµÙØ­Ù‡â€ŒØ¨Ù†Ø¯ÛŒ
        $perPageOptions = [20, 50, 100, 200];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }

        $leads = $query->latest()->paginate($perPage)->appends($request->query());

        $favoriteLeadIds = [];
        if ($request->user()) {
            $favoriteLeadIds = \DB::table('lead_favorites')
                ->where('user_id', $request->user()->id)
                ->whereIn('lead_id', $leads->pluck('id'))
                ->pluck('lead_id')
                ->toArray();
        }

        // Ø¯Ø§Ø¯Ù‡â€ŒÙ‡Ø§ÛŒ Ú©Ù…Ú©ÛŒ
        $users = User::all();
        $leadSources = \App\Helpers\FormOptionsHelper::leadSources();

        return view('marketing.leads.index', compact(
            'leads',
            'users',
            'leadSources',
            'favoriteLeadIds',
            'perPage',
            'perPageOptions'
        ))->with('breadcrumb', $this->leadsBreadcrumb([], false));
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
            $query->where(function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter)
                    ->orWhere('lead_status', $statusFilter);
            });
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
            ['title' => 'Ø³Ø±Ù†Ø®â€ŒÙ‡Ø§ÛŒ ØªØ¨Ø¯ÛŒÙ„â€ŒØ´Ø¯Ù‡'],
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
                ['title' => 'Ø§ÛŒØ¬Ø§Ø¯ Ø³Ø±Ù†Ø®'],
            ]));
    }

    public function store(Request $request)
    {
        \Log::info('ðŸª™ store() method started');
        \Log::info('ðŸª™ Raw request input:', $request->all());

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
            'customer_type' => 'nullable|string|in:Ù…Ø´ØªØ±ÛŒ Ø¬Ø¯ÛŒØ¯,Ù…Ø´ØªØ±ÛŒ Ù‚Ø¯ÛŒÙ…ÛŒ,Ù…Ø´ØªØ±ÛŒ Ø¨Ø§Ù„Ù‚ÙˆÙ‡',
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
            'full_name.required' => 'Ù†Ø§Ù… Ùˆ Ù†Ø§Ù… Ø®Ø§Ù†ÙˆØ§Ø¯Ú¯ÛŒ Ø§Ù„Ø²Ø§Ù…ÛŒ Ø§Ø³Øª.',
            'email.email' => 'ÙØ±Ù…Øª Ø§ÛŒÙ…ÛŒÙ„ Ù†Ø§Ù…Ø¹ØªØ¨Ø± Ø§Ø³Øª.',
            'website.url' => 'ÙØ±Ù…Øª ÙˆØ¨â€ŒØ³Ø§ÛŒØª Ù†Ø§Ù…Ø¹ØªØ¨Ø± Ø§Ø³Øª.',
        ]);

        if ($validator->fails()) {
            \Log::warning('ðŸ”´ Validation failed:', $validator->errors()->toArray());
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $validated = $validator->validated();
            \Log::info('ðŸŸ¢ Validation passed:', $validated);
            $selectedContactId = $validated['contact_id'] ?? null;
            $shouldCreateContact = empty($selectedContactId) && (bool) ($validated['create_contact'] ?? false);
            $validated['contact_id'] = $selectedContactId ? (int) $selectedContactId : null;
            unset($validated['create_contact']);

            $validated['created_by'] = Auth::id();
            // Ø«Ø¨Øª Ù…Ø§Ù„Ú©ÛŒØª Ø§ÛŒØ¬Ø§Ø¯Ú©Ù†Ù†Ø¯Ù‡ Ø¨Ø±Ø§ÛŒ Ù…Ø­Ø¯ÙˆØ¯Ù‡â€ŒÙ‡Ø§ÛŒ Ø¯Ø³ØªØ±Ø³ÛŒ
            $validated['owner_user_id'] = Auth::id();
            $validated['do_not_email'] = $request->has('do_not_email');
            $validated['lead_date'] = DateHelper::normalizeDateInput($validated['lead_date'] ?? null);

            $leadStatusValue = SalesLead::normalizeStatus($validated['lead_status'] ?? SalesLead::STATUS_NEW);
            $validated['status'] = $leadStatusValue;
            $validated['lead_status'] = $leadStatusValue;
            if ($leadStatusValue === SalesLead::STATUS_DISCARDED) {
                // Ø¯Ø± Ø­Ø§Ù„Øª Ø³Ø±Ú©Ø§Ø±ÛŒ/Ø­Ø°Ùâ€ŒØ´Ø¯Ù‡ ØªØ§Ø±ÛŒØ® Ù¾ÛŒÚ¯ÛŒØ±ÛŒ Ø¨Ø¹Ø¯ÛŒ ØµÙØ± Ù…ÛŒâ€ŒØ´ÙˆØ¯.
                $validated['next_follow_up_date'] = null;
            } else {
                $validated['next_follow_up_date'] = DateHelper::normalizeDateInput($validated['next_follow_up_date'] ?? null);
            }

            \Log::info('ðŸ”µ Final data before create:', $validated);

            $lead = DB::transaction(function () use ($validated, $shouldCreateContact) {
                $lead = SalesLead::create($validated);

                if ($shouldCreateContact && $lead) {
                    $contact = $this->createContactFromLead($lead);
                    if ($contact) {
                        $lead->contact_id = $contact->id;
                        $lead->save();
                        \Log::info('?? Contact created from lead', [
                            'lead_id' => $lead->id,
                            'contact_id' => $contact->id,
                        ]);
                    } else {
                        \Log::info('?? create_contact checked but contact payload was empty', ['lead_id' => $lead->id]);
                    }
                }

                return $lead;
            });

            if ($lead && $lead->id) {
                \Log::info('âœ” Sales lead created successfully with ID: ' . $lead->id);

                return redirect()->route('marketing.leads.index')
                    ->with('success', 'Ø³Ø±Ù†Ø® ÙØ±ÙˆØ´ Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø§ÛŒØ¬Ø§Ø¯ Ø´Ø¯.');
            }

            \Log::error('âŒ Sales lead creation failed. No ID returned.');

            return redirect()->back()
                ->with('error', 'Ø§ÛŒØ¬Ø§Ø¯ Ø³Ø±Ù†Ø® ÙØ±ÙˆØ´ Ø§Ù†Ø¬Ø§Ù… Ù†Ø´Ø¯. Ù„Ø·ÙØ§Ù‹ Ø§Ø·Ù„Ø§Ø¹Ø§Øª Ø±Ø§ Ø¨Ø±Ø±Ø³ÛŒ Ú©Ù†ÛŒØ¯ Ùˆ Ø¯ÙˆØ¨Ø§Ø±Ù‡ ØªÙ„Ø§Ø´ Ú©Ù†ÛŒØ¯.')
                ->withInput();

            } catch (\Exception $e) {
                \Log::error('ðŸ”¥ Exception caught during sales lead creation: ' . $e->getMessage());

                return redirect()->back()
                    ->with('error', 'Ø®Ø·Ø§ Ø¯Ø± Ø§ÛŒØ¬Ø§Ø¯ Ø³Ø±Ù†Ø® ÙØ±ÙˆØ´: ' . $e->getMessage())
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

    public function bulkDelete(Request $request)
    {
        $leadIds = $request->input('selected_leads', []);

        if (!empty($leadIds)) {
            SalesLead::whereIn('id', $leadIds)->delete();
        }

        return redirect()->route('marketing.leads.index')
            ->with('success', 'Ø³Ø±Ù†Ø®â€ŒÙ‡Ø§ Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø­Ø°Ù Ø´Ø¯Ù†Ø¯.');
    }

    public function edit(SalesLead $lead)
    {
        $users = User::all();
        $referrals = $users;
        $hasRecentActivity = $lead->hasRecentActivity();
        return view('marketing.leads.edit', compact('lead', 'users', 'referrals', 'hasRecentActivity'))
            ->with('breadcrumb', $this->leadsBreadcrumb([
                ['title' => 'ÙˆÛŒØ±Ø§ÛŒØ´ Ø³Ø±Ù†Ø®'],
            ]));
    }



    public function update(Request $request, SalesLead $lead)
    {
        \Log::info('SalesLeadController@update reached');
        \Log::info('SalesLeadController@update payload', $request->all());

        $leadDateConv = DateHelper::normalizeDateInput($request->lead_date ?? null);
        $statusVal = SalesLead::normalizeStatus($request->lead_status ?? '');
        $nextFollowUpConv = $statusVal === SalesLead::STATUS_DISCARDED
            ? null
            : DateHelper::normalizeDateInput($request->next_follow_up_date ?? null);

        $request->merge([
            'lead_date' => $leadDateConv,
            'next_follow_up_date' => $nextFollowUpConv,
        ]);

        $originalStatus = $lead->lead_status ?? $lead->status;

        $data = $request->validate([
            'prefix' => 'nullable|string|max:10',
            'full_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'lead_source' => ['required', 'string', Rule::in(array_keys(FormOptionsHelper::leadSources()))],
            'lead_status' => ['required', 'string', Rule::in(array_keys(FormOptionsHelper::leadStatuses()))],
            'disqualify_reason' => ['nullable', 'string', Rule::in(array_keys(FormOptionsHelper::leadDisqualifyReasons()))],
            'assigned_to' => 'nullable|exists:users,id',
            'referred_to' => 'nullable|exists:users,id',
            'lead_date' => 'required|date',
            'next_follow_up_date' => 'nullable|date|after_or_equal:today|required_unless:lead_status,discarded,junk',
            'do_not_email' => 'boolean',
            'customer_type' => 'nullable|string|in:U.O\'O?O?UO O?O_UOO_,U.O\'O?O?UO U,O_UOU.UO,U.O\'O?O?UO O"OU,U,U^U?',
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
            'disqual_reason_body' => ['nullable','string','max:5000'],
        ]);

        $newStatus = $data['lead_status'] ?? $originalStatus;
        $normalizedOriginalStatus = SalesLead::normalizeStatus($originalStatus);
        $normalizedNewStatus = SalesLead::normalizeStatus($newStatus);

        $overrideRequested = (bool) $request->boolean('activity_override');
        $quickNoteBody = trim((string) $request->input('quick_note_body', ''));
        $statusReasonBody = trim((string) $request->input('disqual_reason_body', ''));
        $statusChanged = $normalizedOriginalStatus !== $normalizedNewStatus;
        $isDiscardedChange = $statusChanged && $normalizedNewStatus === SalesLead::STATUS_DISCARDED;

        if ($isDiscardedChange) {
            $request->merge(['disqual_reason_body' => $statusReasonBody]);
            $request->validate(
                ['disqual_reason_body' => ['required','string','max:5000']],
                ['disqual_reason_body.required' => 'Ø¯Ù„ÛŒÙ„ ØªØºÛŒÛŒØ± ÙˆØ¶Ø¹ÛŒØª Ø¨Ù‡ Ø³Ø±Ú©Ø§Ø±ÛŒ Ø§Ù„Ø²Ø§Ù…ÛŒ Ø§Ø³Øª.']
            );
            if ($quickNoteBody === '') {
                $quickNoteBody = $statusReasonBody;
            }
            $overrideRequested = true;
        }
        $canChangeStage = true;

        if ($statusChanged) {
            $canChangeStage = $isDiscardedChange ? true : $lead->canChangeStageTo($normalizedNewStatus);

            if (!$canChangeStage && $overrideRequested && $quickNoteBody !== '') {
                $lead->notes()->create([
                    'body' => $quickNoteBody,
                    'user_id' => auth()->id(),
                ]);
                $lead->markFirstActivity(now());
                $canChangeStage = true;

                \Log::info('lead_stage_guard_overridden_with_note', [
                    'lead_id' => $lead->id,
                    'original_status' => $normalizedOriginalStatus,
                    'new_status' => $normalizedNewStatus,
                    'user_id' => auth()->id(),
                ]);
            }

            if (!$canChangeStage) {
                \Log::info('lead_stage_guard_blocked', [
                    'lead_id' => $lead->id,
                    'original_status' => $normalizedOriginalStatus,
                    'new_status' => $normalizedNewStatus,
                ]);

                return back()
                    ->withErrors(['lead_status' => 'ØªØºÛŒÛŒØ± ÙˆØ¶Ø¹ÛŒØª Ø¨Ø¯ÙˆÙ† ÙØ¹Ø§Ù„ÛŒØª ØªÙ…Ø§Ø³/Ø¬Ù„Ø³Ù‡/ÛŒØ§Ø¯Ø¯Ø§Ø´Øª Ø§Ø®ÛŒØ± Ù…Ø¬Ø§Ø² Ù†ÛŒØ³Øª.'])
                    ->withInput();
            }
        }

        if (array_key_exists('notes', $data)) {
            \Log::info('Removing notes from update payload to keep initial note immutable.');
            unset($data['notes']);
        }

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
            } catch (\Throwable $activityException) {
                \Log::warning('lead_status_reason_activity_failed', [
                    'lead_id' => $lead->id ?? null,
                    'error' => $activityException->getMessage(),
                ]);

                if (method_exists($lead, 'markFirstActivity')) {
                    $lead->markFirstActivity(now());
                }
            }
        }

        unset($data['activity_override'], $data['quick_note_body'], $data['disqual_reason_body']);

        if (array_key_exists('lead_status', $data)) {
            $data['lead_status'] = $normalizedNewStatus;
            $data['status'] = $normalizedNewStatus;
        }

        $data['do_not_email'] = $request->has('do_not_email');

        $lead->fill($data);
        $lead->save();

        return redirect()
    ->route('marketing.leads.index')
    ->with('success', 'ØªØºÛŒÛŒØ±Ø§Øª Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø°Ø®ÛŒØ±Ù‡ Ø´Ø¯.');

    }


    public function destroy(SalesLead $lead)
    {
        $lead->delete();

        return redirect()->route('marketing.leads.index')
            ->with('success', 'Ø³Ø±Ù†Ø® ÙØ±ÙˆØ´ Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø­Ø°Ù Ø´Ø¯.');
    }

    public function show(SalesLead $lead)
    {
        $lead->load(['lastNote', 'assignedTo']);
        $lead->jalali_created_at = DateHelper::toJalali($lead->created_at);
        $lead->jalali_updated_at = DateHelper::toJalali($lead->updated_at);

        // âœ… Ø§ÛŒÙ† Ø®Ø· Ø§Ø¶Ø§ÙÙ‡ Ø´Ø¯ ØªØ§ ÙÙ‚Ø· Ú©Ø§Ø±Ø¨Ø±Ø§Ù†ÛŒ Ú©Ù‡ Ù†Ø§Ù… Ú©Ø§Ø±Ø¨Ø±ÛŒ Ø¯Ø§Ø±Ù†Ø¯ Ø¨Ø±Ú¯Ø±Ø¯Ù†Ø¯
        $allUsers = User::whereNotNull('username')->get();

        return view('marketing.leads.show', compact('lead', 'allUsers'))
            ->with('breadcrumb', $this->leadsBreadcrumb([
                ['title' => 'Ø¬Ø²Ø¦ÛŒØ§Øª Ø³Ø±Ù†Ø®'],
            ]));
    }

    public function loadTab(SalesLead $lead, $tab)
    {
        return view("marketing.leads.tabs.{$tab}", compact('lead'));
    }

    public function convertToOpportunity(Request $request, SalesLead $lead)
    {
        if (!empty($lead->converted_at)) {
            return redirect()->back()->with('error', 'Ø§ÛŒÙ† Ø³Ø±Ù†Ø® Ù‚Ø¨Ù„Ø§Ù‹ Ø¨Ù‡ ÙØ±ØµØª ØªØ¨Ø¯ÛŒÙ„ Ø´Ø¯Ù‡ Ø§Ø³Øª.');
        }

        try {
            DB::transaction(function () use ($lead) {
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

                $name = $lead->company
                    ? ('ÙØ±ØµØª - ' . $lead->company)
                    : ('ÙØ±ØµØª - ' . ($lead->full_name ?: ('Ø³Ø±Ù†Ø® #' . $lead->id)));

                $opportunity = Opportunity::create([
                    'name'             => $name,
                    'organization_id'  => $organization?->id,
                    'contact_id'       => $contact?->id,
                    'assigned_to'      => $lead->assigned_to,
                    'source'           => $lead->lead_source,
                    'next_follow_up'   => $lead->next_follow_up_date,
                    'description'      => $lead->notes,
                    'stage'            => Opportunity::STAGE_OPEN,
                ]);

                $lead->converted_at = Carbon::now();
                $lead->converted_opportunity_id = $opportunity->id;
                $lead->converted_by = Auth::id();
                $lead->status = SalesLead::STATUS_CONVERTED_TO_OPPORTUNITY;
                $lead->lead_status = SalesLead::STATUS_CONVERTED_TO_OPPORTUNITY;
                $lead->save();

                $this->transferLeadNotesToOpportunity($lead, $opportunity);
                $this->transferLeadActivitiesToOpportunity($lead, $opportunity);

            });

            return redirect()
                ->route('marketing.leads.index')
                ->with('success', 'Ø³Ø±Ù†Ø® Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø¨Ù‡ ÙØ±ØµØª ÙØ±ÙˆØ´ ØªØ¨Ø¯ÛŒÙ„ Ø´Ø¯.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Ø®Ø·Ø§ Ø¯Ø± ØªØ¨Ø¯ÛŒÙ„ Ø³Ø±Ù†Ø® Ø¨Ù‡ ÙØ±ØµØª: ' . $e->getMessage());
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

