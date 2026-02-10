<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\SalesLead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\Proforma;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\FormOptionsHelper;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        $organizationStats   = $this->getOrganizationStats($user);
        $contactStats        = $this->getContactStats($user);
        $opportunityStats    = $this->getOpportunityStats($user);
        $proformaStats       = $this->getProformaStats($user);
        $purchaseOrderStats  = $this->getPurchaseOrderStats($user);
        $leadStats           = $this->getLeadStats($user);

        // Notifications
        $notifications = $user->notifications()
            ->latest()
            ->take(10)
            ->get();

        // Incomplete activities (tasks)
        $tasks = Activity::where('status', '!=', 'completed')
            ->where('assigned_to_id', $user->id)
            ->orderBy('due_at', 'asc')
            ->take(10)
            ->get();

        // Today's follow-ups (from leads + opportunities assigned to the user)
        $today = now()->toDateString();

        $leadFollowUps = SalesLead::query()
            ->where('assigned_to', $user->id)
            ->whereDate('next_follow_up_date', $today)
            ->get()
            ->map(function (SalesLead $lead) {
                return [
                    'type'  => 'lead',
                    'title' => method_exists($lead, 'getNotificationTitle') ? $lead->getNotificationTitle() : ($lead->full_name ?: ($lead->company ?: (__('Lead') . ' #' . $lead->id))),
                    'date'  => $lead->next_follow_up_date,
                    'url'   => route('marketing.leads.show', $lead->id),
                ];
            });

        $opFollowUps = Opportunity::query()
            ->where('assigned_to', $user->id)
            ->whereDate('next_follow_up', $today)
            ->get()
            ->map(function (Opportunity $opportunity) {
                return [
                    'type'  => 'opportunity',
                    'title' => method_exists($opportunity, 'getNotificationTitle') ? $opportunity->getNotificationTitle() : ($opportunity->name ?: (__('Opportunity') . ' #' . $opportunity->id)),
                    'date'  => $opportunity->next_follow_up,
                    'url'   => route('sales.opportunities.show', $opportunity->id),
                ];
            });

        $todayFollowUps = $leadFollowUps->concat($opFollowUps)->sortBy('date')->values();

        // Past-due follow-ups (before today)
        $pastLeadFollowUps = SalesLead::query()
            ->where('assigned_to', $user->id)
            ->whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<', $today)
            ->get()
            ->map(function (SalesLead $lead) {
                return [
                    'type'  => 'lead',
                    'title' => method_exists($lead, 'getNotificationTitle') ? $lead->getNotificationTitle() : ($lead->full_name ?: ($lead->company ?: (__('Lead') . ' #' . $lead->id))),
                    'date'  => $lead->next_follow_up_date,
                    'url'   => route('marketing.leads.show', $lead->id),
                ];
            });

        $pastOpFollowUps = Opportunity::query()
            ->where('assigned_to', $user->id)
            ->whereNotNull('next_follow_up')
            ->whereDate('next_follow_up', '<', $today)
            ->get()
            ->map(function (Opportunity $opportunity) {
                return [
                    'type'  => 'opportunity',
                    'title' => method_exists($opportunity, 'getNotificationTitle') ? $opportunity->getNotificationTitle() : ($opportunity->name ?: (__('Opportunity') . ' #' . $opportunity->id)),
                    'date'  => $opportunity->next_follow_up,
                    'url'   => route('sales.opportunities.show', $opportunity->id),
                ];
            });

        $pastFollowUps = $pastLeadFollowUps->concat($pastOpFollowUps)->sortByDesc('date')->values();

        return view('dashboard', compact(
            'notifications',
            'tasks',
            'todayFollowUps',
            'pastFollowUps',
            'organizationStats',
            'contactStats',
            'opportunityStats',
            'proformaStats',
            'purchaseOrderStats',
            'leadStats'
        ));
    }

    private function applyVisibility($query, $user, string $modulePrefix, ?string $fallbackAssignedColumn = null)
    {
        if ($user && method_exists($query->getModel(), 'scopeVisibleFor')) {
            return $query->visibleFor($user, $modulePrefix);
        }
        // اگر مدل اسکوپ دسترسی ندارد، کوئری را دست‌نخورده برگردان (برای مدیر کل هم قابل‌دسترس می‌ماند)
        return $query;
    }

    private function getOrganizationStats($user): array
    {
        $orgBase = $this->applyVisibility(Organization::query(), $user, 'organizations');

        $newCount = (clone $orgBase)->whereDate('created_at', '>=', now()->subDays(30))->count();
        $hasContact = (clone $orgBase)->whereHas('contacts')->count();
        $noContact = (clone $orgBase)->doesntHave('contacts')->count();
        $visibleOpportunities = $this->applyVisibility(Opportunity::query(), $user, 'opportunities');

        return [
            'new'        => $newCount,
            'pending'    => (clone $orgBase)->whereNull('assigned_to')->count(),
            'converted'  => (clone $orgBase)->whereHas('opportunities')->count(),
            'total_value'=> (clone $visibleOpportunities)->whereNotNull('organization_id')->sum('amount'),
            'statuses'   => [
                ['label' => 'دارای مخاطب', 'count' => $hasContact],
                ['label' => 'بدون مخاطب', 'count' => $noContact],
            ],
        ];
    }

    private function getContactStats($user): array
    {
        $contactBase = $this->applyVisibility(Contact::query(), $user, 'contacts');

        $newCount = (clone $contactBase)->whereDate('created_at', '>=', now()->subDays(30))->count();
        $withOrg = (clone $contactBase)->whereNotNull('organization_id')->count();
        $withoutOrg = (clone $contactBase)->whereNull('organization_id')->count();
        $visibleOpportunities = $this->applyVisibility(Opportunity::query(), $user, 'opportunities');

        return [
            'new'        => $newCount,
            'pending'    => (clone $contactBase)->whereNull('assigned_to')->count(),
            'converted'  => (clone $contactBase)->whereHas('opportunities')->count(),
            'total_value'=> (clone $visibleOpportunities)->whereNotNull('contact_id')->sum('amount'),
            'statuses'   => [
                ['label' => 'متصل به سازمان', 'count' => $withOrg],
                ['label' => 'بدون سازمان', 'count' => $withoutOrg],
            ],
        ];
    }

    private function getOpportunityStats($user): array
    {
        $today = now()->toDateString();

        $opportunityBase = $this->applyVisibility(Opportunity::query(), $user, 'opportunities');

        $stageExpr = "TRIM(stage)";
        $statusRows = (clone $opportunityBase)->selectRaw($stageExpr . ' as stage_clean, COUNT(*) as aggregate')
            ->groupBy(DB::raw($stageExpr))
            ->orderByDesc('aggregate')
            ->get()
            ->map(function ($row) {
                $stageKey = $row->stage_clean ?? '';
                return [
                    'label' => FormOptionsHelper::getOpportunityStageLabel($stageKey),
                    'count' => (int) $row->aggregate,
                ];
            })
            ->values()
            ->all();

        return [
            'new'        => (clone $opportunityBase)->whereDate('created_at', '>=', now()->subDays(30))->count(),
            'pending'    => (clone $opportunityBase)->whereNotNull('next_follow_up')->whereDate('next_follow_up', '<=', $today)->count(),
            'converted'  => (clone $opportunityBase)->whereIn('stage', ['won'])->count(),
            'total_value'=> (clone $opportunityBase)->sum('amount'),
            'statuses'   => $statusRows,
        ];
    }

    private function getProformaStats($user): array
    {
        $proformaBase = $this->applyVisibility(Proforma::query(), $user, 'proformas');

        $statusRows = (clone $proformaBase)->select(DB::raw('COALESCE(approval_stage, proforma_stage) as stage_key'), DB::raw('COUNT(*) as aggregate'))
            ->groupBy('stage_key')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => FormOptionsHelper::getProformaStageLabel($row->stage_key),
                    'count' => (int) $row->aggregate,
                ];
            })
            ->values()
            ->all();

        $pendingStages = ['draft', 'send_for_approval', 'awaiting_second_approval'];
        $convertedStages = ['approved', 'finalized'];

        return [
            'new'         => (clone $proformaBase)->whereDate('created_at', '>=', now()->subDays(30))->count(),
            'pending'     => (clone $proformaBase)->whereIn(DB::raw('COALESCE(approval_stage, proforma_stage)'), $pendingStages)->count(),
            'converted'   => (clone $proformaBase)->whereIn(DB::raw('COALESCE(approval_stage, proforma_stage)'), $convertedStages)->count(),
            'total_value' => (clone $proformaBase)->sum('total_amount'),
            'statuses'    => $statusRows,
        ];
    }

    private function getPurchaseOrderStats($user): array
    {
        $statusLabels = PurchaseOrder::statuses();

        $poBase = $this->applyVisibility(PurchaseOrder::query(), $user, 'purchase_orders');

        $statusRows = (clone $poBase)->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->get()
            ->map(function ($row) use ($statusLabels) {
                return [
                    'label' => $statusLabels[$row->status] ?? $row->status,
                    'count' => (int) $row->aggregate,
                ];
            })
            ->values()
            ->all();

        $pendingStatuses = ['created', 'supervisor_approval', 'manager_approval', 'accounting_approval', 'purchasing'];
        $convertedStatuses = ['purchased', 'warehouse_delivered'];

        return [
            'new'         => (clone $poBase)->whereDate('created_at', '>=', now()->subDays(30))->count(),
            'pending'     => (clone $poBase)->whereIn('status', $pendingStatuses)->count(),
            'converted'   => (clone $poBase)->whereIn('status', $convertedStatuses)->count(),
            'total_value' => (clone $poBase)->sum('total_amount'),
            'statuses'    => $statusRows,
        ];
    }

    private function getLeadStats($user = null): array
    {
        $today = now()->toDateString();

        $visibleLeadQuery = $this->applyVisibility(SalesLead::query(), $user, 'leads');

        $statusRows = (clone $visibleLeadQuery)->select('lead_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('lead_status')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => FormOptionsHelper::getLeadStatusLabel($row->lead_status),
                    'count' => (int) $row->aggregate,
                ];
            })
            ->values()
            ->all();

        return [
            'new'         => (clone $visibleLeadQuery)->whereDate('created_at', '>=', now()->subDays(30))->count(),
            'pending'     => (clone $visibleLeadQuery)->whereNotNull('next_follow_up_date')->whereDate('next_follow_up_date', '<=', $today)->count(),
            'converted'   => (clone $visibleLeadQuery)->whereHas('opportunities')->count(),
            'total_value' => null,
            'statuses'    => $statusRows,
        ];
    }
}
