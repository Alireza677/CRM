<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Opportunity;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Spatie\Activitylog\Models\Activity;
use Morilog\Jalali\Jalalian;
use Illuminate\Validation\Rule;
use App\Helpers\FormOptionsHelper;
use Illuminate\Support\Facades\DB;
use App\Services\CommissionService;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityGuard;
use App\Helpers\DateHelper;
use App\Models\Activity as CrmActivity;
use Illuminate\Support\Str;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::visibleFor(auth()->user(), 'opportunities')
            ->with(['contact', 'assignedUser', 'organization']);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('contact')) {
            $query->whereHas('contact', function ($q) use ($request) {
                $term = '%' . $request->contact . '%';
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', $term);
            });
        }

        if ($request->filled('source')) {
            $query->where('source', 'like', '%' . $request->source . '%');
        }

        if ($request->filled('building_usage')) {
            $query->where('building_usage', 'like', '%' . $request->building_usage . '%');
        }

        if ($request->filled('assigned_to')) {
            $query->whereHas('assignedUser', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->assigned_to . '%');
            });
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        $perPage = (int) $request->get('per_page', 15);
        $opportunities = $query->latest()->paginate($perPage)->withQueryString();

        return view('sales.opportunities.index', compact('opportunities'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', \App\Models\Opportunity::class);

        $opportunity   = new Opportunity();
        $organizations = Organization::all();
        $contacts = Contact::all();
        $users = User::all();

        $contactId = $request->input('contact_id');
        $defaultContact = $contactId ? Contact::find($contactId) : null;

        return view('sales.opportunities.create', compact(
            'opportunity',
            'organizations',
            'contacts',
            'users',
            'defaultContact'
        ));
    }

    public function store(Request $request)
    {
        $this->authorize('create', \App\Models\Opportunity::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'contact_id' => 'nullable|exists:contacts,id',
            // برچسب نوع کسب‌وکار آزاد است (لیست UI تغییری نمی‌کند)
            'type' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            // برچسب نوع کاربری بنا آزاد است (لیست UI تغییری نمی‌کند)
            'building_usage' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'success_rate' => 'required|numeric|min:0|max:100',
            // Make follow-up optional when stage is "برنده" (won)
            'next_follow_up' => 'nullable|date|required_unless:stage,won,lost,dead',
            'description' => 'nullable|string',
            'stage' => ['nullable','string', Rule::in(array_keys(FormOptionsHelper::opportunityStages()))],
            'lost_reason' => ['nullable','string', Rule::in(array_keys(FormOptionsHelper::opportunityLostReasons()))],
            'activity_override' => ['nullable','boolean'],
            'quick_note_body' => ['nullable','string','max:5000'],
        ]);

        $validated['stage'] = $validated['stage'] ?? Opportunity::STAGE_OPEN;
        if (!in_array($validated['stage'], Opportunity::closedStages(), true)) {
            $validated['lost_reason'] = null;
        }

        $opportunity = Opportunity::create($validated);
        $opportunity->notifyIfAssigneeChanged(null);

        return redirect()
            ->route('sales.opportunities.index')
            ->with('success', 'فرصت فروش با موفقیت ایجاد شد.');
    }

    public function show(Opportunity $opportunity)
    {
        $this->authorize('view', $opportunity);

        $breadcrumb = [
            ['title' => 'داشبورد', 'url' => url('/dashboard')],
            ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
            ['title' => $opportunity->name ?: ('فرصت فروش #' . $opportunity->id)],
        ];

        $activities = Activity::where('subject_type', Opportunity::class)
            ->where('subject_id', $opportunity->id)
            ->latest()
            ->get();

        $opportunity->load([
            'proformas' => function ($q) use ($opportunity) {
                $q->select(
                    'id',
                    'opportunity_id',
                    'proforma_number',
                    'proforma_date',
                    'approval_stage',
                    'proforma_stage',
                    'total_amount'
                )
                ->where('opportunity_id', $opportunity->id)
                ->orderByDesc('proforma_date');
            },
        ])->loadMissing(['roleAssignments.user']);

        return view('sales.opportunities.show', compact('opportunity', 'breadcrumb', 'activities'));
    }

    public function edit(Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $opportunity->loadMissing(['organization', 'contact']);

        $organizations = Organization::orderBy('name')->get(['id', 'name', 'phone']);
        $contacts      = Contact::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'mobile']);
        $users         = User::orderBy('name')->get(['id', 'name']);
        $hasRecentActivity = $opportunity->hasRecentActivity();

        $nextFollowUpDate = '';
        if (!empty($opportunity->next_follow_up)) {
            try {
                $nextFollowUpDate = Jalalian::fromDateTime($opportunity->next_follow_up)->format('Y/m/d');
            } catch (\Throwable $e) {
                $nextFollowUpDate = '';
            }
        }

        return view('sales.opportunities.edit', compact(
            'opportunity', 'organizations', 'contacts', 'users', 'nextFollowUpDate', 'hasRecentActivity'
        ));
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        // Normalize Jalali/Gregorian follow-up date before validation
        $rawFollowUp = $request->input('next_follow_up', $request->input('next_follow_up_shamsi'));
        if (!is_null($rawFollowUp) && trim((string) $rawFollowUp) !== '') {
            $request->merge([
                'next_follow_up' => DateHelper::normalizeDateInput($rawFollowUp),
            ]);
        }

        if ($request->filled('source')) {
            $request->merge([
                'source' => FormOptionsHelper::getLeadSourceLabel($request->input('source')),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'type' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            // برچسب نوع کاربری بنا آزاد است (لیست UI تغییری نمی‌کند)
            'building_usage' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'success_rate' => 'nullable|numeric|min:0|max:100',
            'next_follow_up' => 'nullable|date|required_unless:stage,won,lost,dead',
            'description' => 'nullable|string',
            'stage' => ['nullable','string', Rule::in(array_keys(FormOptionsHelper::opportunityStages()))],
            'lost_reason' => ['nullable','string', Rule::in(array_keys(FormOptionsHelper::opportunityLostReasons()))],
            'loss_reason_body' => ['nullable','string','max:5000'],
            'loss_reasons' => ['nullable','array'],
            'loss_reasons.*' => ['string','max:255'],
        ]);

        $oldStage = $opportunity->getStageValue() ?? Opportunity::STAGE_OPEN;
        $requestedStage = $validated['stage'] ?? $oldStage;
        $normalizedRequestedStage = $requestedStage !== null ? strtolower((string) $requestedStage) : $oldStage;
        $stageChanged = $normalizedRequestedStage !== $oldStage;
        $stageChangedToLoss = $stageChanged && in_array($normalizedRequestedStage, [Opportunity::STAGE_LOST, Opportunity::STAGE_DEAD], true);
        $selectedLossReasons = $this->sanitizeLossReasons($request->input('loss_reasons', []));
        $lossReasonBody = $this->composeLossReasonBody(
            $selectedLossReasons,
            (string) $request->input('loss_reason_body', '')
        );

        if ($stageChangedToLoss) {
    $request->merge([
        'loss_reason_body' => $lossReasonBody,
        'loss_reasons'     => $selectedLossReasons,
    ]);

    $request->validate(
        [
            'loss_reason_body' => ['required', 'string', 'max:5000'],
            'loss_reasons'     => ['nullable', 'array'],
            'loss_reasons.*'   => ['string', 'max:255'],
        ],
        [
            'loss_reason_body.required' => 'ثبت دلیل از دست رفتن فرصت الزامی است.',
        ]
    );

    $opportunity->notes()->create([
        'body'    => $lossReasonBody,
        'user_id' => auth()->id(),
    ]);

    try {
        $creatorId  = auth()->id() ?: $opportunity->assigned_to;
        $assigneeId = $opportunity->assigned_to ?: $creatorId;

        $activity = CrmActivity::create([
            'subject'        => 'lost_reason',
            'start_at'       => now(),
            'due_at'         => now(),
            'assigned_to_id' => $assigneeId,
            'related_type'   => Opportunity::class,
            'related_id'     => $opportunity->id,
            'status'         => 'completed',
            'priority'       => 'normal',
            'description'    => $lossReasonBody,
            'is_private'     => false,
            'created_by_id'  => $creatorId,
            'updated_by_id'  => $creatorId,
        ]);

        if (method_exists($opportunity, 'markFirstActivity')) {
            $activityTime = $activity->start_at ?? $activity->created_at ?? now();
            $opportunity->markFirstActivity($activityTime);
        }
    } catch (\Throwable $activityException) {
        Log::warning('opportunity_loss_reason_activity_failed', [
            'opportunity_id' => $opportunity->id,
            'error'          => $activityException->getMessage(),
        ]);
    }
}


        $realActivitiesCount = ActivityGuard::countRealActivities($opportunity);

        Log::info('opportunity_stage_change_request', [
            'context' => 'opportunity_stage_change_request',
            'opportunity_id' => $opportunity->id ?? null,
            'user_id' => auth()->id(),
            'old_stage' => $oldStage,
            'new_stage' => $normalizedRequestedStage,
            'real_activities_count' => $realActivitiesCount,
            'request_data' => $request->all(),
        ]);


        // الزام فعالیت قبل از تغییر مرحله
        $overrideRequested = (bool) $request->boolean('activity_override');
        $quickNoteBody = trim((string) $request->input('quick_note_body', ''));

        $incomingStage = $normalizedRequestedStage ?: null;
        $currentStage = $oldStage;
        $previousStage = $currentStage;
        if ($incomingStage && $incomingStage !== $currentStage) {
            $canChangeStage = $opportunity->canChangeStageTo($incomingStage);
        
            if (!$canChangeStage && $overrideRequested && $quickNoteBody !== '') {
                $opportunity->notes()->create([
                    'body' => $quickNoteBody,
                    'user_id' => auth()->id(),
                ]);
                $canChangeStage = true;
        
                Log::info('OVERRIDE: opportunity stage change with note', [
                    'opportunity_id' => $opportunity->id,
                    'from_stage' => $previousStage,
                    'to_stage' => $incomingStage,
                    'user_id' => auth()->id(),
                ]);
            }
        
            if (!$canChangeStage) {
                Log::warning('BLOCKED: opportunity stage change without activity', [
                    'opportunity_id' => $opportunity->id,
                    'from_stage' => $previousStage,
                    'to_stage' => $incomingStage,
                    'real_activities_count' => $realActivitiesCount,
                ]);
                return redirect()
                    ->back()
                    ->withErrors(['stage' => 'تغییر وضعیت بدون فعالیت تماس/جلسه/یادداشت اخیر مجاز نیست.'])
                    ->withInput();
            }
        
            Log::info('ALLOWED: opportunity stage change', [
                'opportunity_id' => $opportunity->id,
                'from_stage' => $previousStage,
                'to_stage' => $incomingStage,
                'real_activities_count' => $realActivitiesCount,
            ]);
        }
        
        $oldAssignedTo = $opportunity->assigned_to;

        $validated['stage'] = $requestedStage ?? $oldStage ?? Opportunity::STAGE_OPEN;
        if (!in_array($validated['stage'], Opportunity::closedStages(), true)) {
            $validated['lost_reason'] = null;
        }

        unset($validated['activity_override'], $validated['quick_note_body'], $validated['loss_reason_body'], $validated['loss_reasons']);

        $opportunity->update($validated);
        $opportunity->notifyIfAssigneeChanged($oldAssignedTo);

        $newStage = $opportunity->getStageValue();
        if ($previousStage !== Opportunity::STAGE_WON && $newStage === Opportunity::STAGE_WON) {
            app(CommissionService::class)->calculateForOpportunity($opportunity);
        } elseif ($previousStage === Opportunity::STAGE_WON && $newStage !== Opportunity::STAGE_WON) {
            // TODO: در صورت خروج فرصت از مرحله won منطق اصلاح/بازگشت کمیسیون اعمال شود.
        }

        return redirect()
            ->route('sales.opportunities.show', $opportunity)
            ->with('success', 'تغییرات فرصت فروش با موفقیت ذخیره شد.');
    }


    protected function sanitizeLossReasons($reasons): array
    {
        if (!is_array($reasons)) {
            return [];
        }

        $clean = [];
        foreach ($reasons as $reason) {
            if (!is_string($reason)) {
                continue;
            }
            $value = trim($reason);
            if ($value !== '') {
                $clean[] = $value;
            }
        }

        return array_values(array_unique($clean));
    }

    protected function composeLossReasonBody(array $reasons, string $body): string
    {
        $reasons = $this->sanitizeLossReasons($reasons);
        $cleanBody = trim($body);

        $reasonsLine = $reasons !== []
            ? 'دلایل عدم موفقیت: ' . implode('، ', $reasons)
            : '';

        if ($reasonsLine !== '') {
            if (Str::startsWith($cleanBody, $reasonsLine)) {
                return $cleanBody;
            }

            return $cleanBody !== ''
                ? $reasonsLine . PHP_EOL . $cleanBody
                : $reasonsLine;
        }

        return $cleanBody;
    }


    public function destroy(Opportunity $opportunity)
    {
        $user = auth()->user();
        $isAdmin = false;

        if ($user) {
            if (method_exists($user, 'hasRole')) {
                $isAdmin = $user->hasRole('admin');
            } else {
                $isAdmin = (bool)($user->is_admin ?? false);
            }
        }

        abort_unless($isAdmin, 403, 'شما مجوز حذف این فرصت را ندارید.');

        $opportunity->delete();

        return redirect()
            ->route('sales.opportunities.index')
            ->with('success', 'فرصت فروش با موفقیت حذف شد.');
    }

    public function bulkDelete(Request $request)
    {
        $user = auth()->user();
        $isAdmin = false;

        if ($user) {
            if (method_exists($user, 'hasRole')) {
                $isAdmin = $user->hasRole('admin');
            } else {
                $isAdmin = (bool)($user->is_admin ?? false);
            }
        }

        abort_unless($isAdmin, 403, 'شما مجوز حذف گروهی فرصت‌ها را ندارید.');

        $validated = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:opportunities,id'],
        ]);

        $ids = $validated['ids'];

        Opportunity::whereIn('id', $ids)->get()->each(function (Opportunity $op) {
            $op->delete();
        });

        return redirect()
            ->route('sales.opportunities.index')
            ->with('success', 'حذف گروهی انجام شد (' . count($ids) . ').');
    }

    public function loadTab(Opportunity $opportunity, $tab)
    {
        $view = "sales.opportunities.tabs.$tab";

        if (!view()->exists($view)) {
            abort(404);
        }

        $data = ['opportunity' => $opportunity];

        if ($tab === 'summary') {
            $opportunity->loadMissing(['roleAssignments.user']);
        }

        // برای تب یادداشت‌ها، فهرست کاربران جهت منشن‌کردن نیاز است
        if ($tab === 'notes') {
            $data['allUsers'] = User::whereNotNull('username')->get();
        }

        // برای تب آپدیت‌ها، فعالیت‌های ثبت‌شدهٔ اسپیتی لود می‌شود
        if ($tab === 'updates') {
            $data['activities'] = Activity::where('subject_type', Opportunity::class)
                ->where('subject_id', $opportunity->id)
                ->where(function ($query) {
                    $query->whereIn('event', ['created', 'updated', 'proforma_created', 'document_voided', 'document_unvoided', 'contact_attached', 'contact_detached'])
                        ->orWhereNull('event');
                })
                ->latest()
                ->get();
        }

        if ($tab === 'contacts') {
            $opportunity->loadMissing(['contact.organization', 'contacts.organization']);
            $contacts = $opportunity->contacts;
            if ($opportunity->contact && !$contacts->contains('id', $opportunity->contact->id)) {
                $contacts = $contacts->prepend($opportunity->contact);
            }
            $data['contacts'] = $contacts;
            $data['allContacts'] = Contact::visibleFor(auth()->user(), 'contacts')
                ->select('id', 'first_name', 'last_name', 'mobile')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return view($view, $data);
    }

    public function ajaxTab(Opportunity $opportunity, $tab)
    {
        switch ($tab) {
            case 'info':
                return view('sales.opportunities.tabs.info', compact('opportunity'));

            case 'notes':
                return view('sales.opportunities.tabs.notes', compact('opportunity'));

            case 'calls':
                return view('sales.opportunities.tabs.calls', compact('opportunity'));

            case 'updates':
                $activities = Activity::where('subject_type', Opportunity::class)
                    ->where('subject_id', $opportunity->id)
                    ->where(function ($query) {
                        $query->whereIn('event', ['created', 'updated', 'proforma_created', 'document_voided', 'document_unvoided', 'contact_attached', 'contact_detached'])
                            ->orWhereNull('event');
                    })
                    ->latest()
                    ->get();
                return view('sales.opportunities.tabs.updates', compact('opportunity', 'activities'));

            case 'activities':
                return view('sales.opportunities.tabs.activities', compact('opportunity'));

            case 'proformas':
                $opportunity->load('proformas');
                return view('sales.opportunities.tabs.proformas', compact('opportunity'));

            case 'approvals':
                return view('sales.opportunities.tabs.approvals', compact('opportunity'));

            case 'documents':
                $opportunity->load('documents');
                return view('sales.opportunities.tabs.documents', compact('opportunity'));

            case 'contacts':
                $opportunity->loadMissing(['contact.organization', 'contacts.organization']);
                $contacts = $opportunity->contacts;
                if ($opportunity->contact && !$contacts->contains('id', $opportunity->contact->id)) {
                    $contacts = $contacts->prepend($opportunity->contact);
                }
                $allContacts = Contact::visibleFor(auth()->user(), 'contacts')
                    ->select('id', 'first_name', 'last_name', 'mobile')
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get();
                return view('sales.opportunities.tabs.contacts', compact('opportunity', 'contacts', 'allContacts'));

            case 'orders':
                return view('sales.opportunities.tabs.orders', compact('opportunity'));

            default:
                abort(404);
        }
    }

    public function attachContact(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer|exists:contacts,id',
        ]);

        $contact = Contact::visibleFor($request->user(), 'contacts')->find($validated['contact_id']);
        if (!$contact) {
            return response()->json(['message' => 'مخاطب انتخاب شده یافت نشد.'], 404);
        }

        $contact->forceFill(['opportunity_id' => $opportunity->id])->save();
        if (empty($opportunity->contact_id)) {
            $opportunity->forceFill(['contact_id' => $contact->id])->save();
        }

        activity('opportunity')
            ->performedOn($opportunity)
            ->causedBy($request->user())
            ->withProperties([
                'contact_id' => $contact->id,
                'contact_name' => $contact->full_name ?? $contact->name ?? '',
            ])
            ->log('contact_attached');

        return response()->json([
            'ok' => true,
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->full_name ?? $contact->name ?? '',
            ],
        ]);
    }

    public function detachContact(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer|exists:contacts,id',
        ]);

        $contactId = (int) $validated['contact_id'];

        $contact = Contact::visibleFor($request->user(), 'contacts')->find($contactId);
        if (!$contact) {
            return response()->json(['message' => 'مخاطب انتخاب‌شده یافت نشد.'], 404);
        }

        if ((int) ($contact->opportunity_id ?? 0) !== (int) $opportunity->id
            && (int) ($opportunity->contact_id ?? 0) !== $contactId) {
            return response()->json(['message' => 'این مخاطب به این فرصت فروش مرتبط نیست.'], 422);
        }

        $contact->forceFill(['opportunity_id' => null])->save();

        $wasPrimary = (int) ($opportunity->contact_id ?? 0) === $contactId;
        $newPrimaryId = null;

        if ($wasPrimary) {
            $newPrimaryId = $opportunity->contacts()->orderBy('created_at')->value('id');
            $opportunity->forceFill(['contact_id' => $newPrimaryId])->save();
        }

        activity('opportunity')
            ->performedOn($opportunity)
            ->causedBy($request->user())
            ->withProperties([
                'contact_id' => $contactId,
                'contact_name' => $contact->full_name ?? $contact->name ?? '',
                'was_primary' => $wasPrimary,
                'new_primary_id' => $newPrimaryId,
            ])
            ->log('contact_detached');

        return response()->json([
            'ok' => true,
            'new_primary_id' => $newPrimaryId,
        ]);
    }


    public function setPrimaryContact(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer|exists:contacts,id',
        ]);

        $contactId = (int) $validated['contact_id'];
        $exists = $opportunity->contacts()->whereKey($contactId)->exists()
            || (int) ($opportunity->contact_id ?? 0) === $contactId;
        if (!$exists) {
            return response()->json(['message' => 'مخاطب انتخاب شده به این فرصت متصل نیست.'], 422);
        }

        $opportunity->forceFill(['contact_id' => $contactId])->save();

        return response()->json(['ok' => true]);
    }
}
