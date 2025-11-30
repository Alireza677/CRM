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

        $organizationStats   = $this->getOrganizationStats();
        $contactStats        = $this->getContactStats();
        $opportunityStats    = $this->getOpportunityStats();
        $proformaStats       = $this->getProformaStats();
        $purchaseOrderStats  = $this->getPurchaseOrderStats();
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

        return view('dashboard', compact(
            'notifications',
            'tasks',
            'todayFollowUps',
            'organizationStats',
            'contactStats',
            'opportunityStats',
            'proformaStats',
            'purchaseOrderStats',
            'leadStats'
        ));
    }

    private function getOrganizationStats(): array
    {
        $newCount = Organization::whereDate('created_at', '>=', now()->subDays(30))->count();
        $hasContact = Organization::has('contacts')->count();
        $noContact = Organization::doesntHave('contacts')->count();

        return [
            'new'        => $newCount,
            'pending'    => Organization::whereNull('assigned_to')->count(),
            'converted'  => Organization::has('opportunities')->count(),
            'total_value'=> Opportunity::whereNotNull('organization_id')->sum('amount'),
            'statuses'   => [
                ['label' => 'دارای مخاطب', 'count' => $hasContact],
                ['label' => 'بدون مخاطب', 'count' => $noContact],
            ],
        ];
    }

    private function getContactStats(): array
    {
        $newCount = Contact::whereDate('created_at', '>=', now()->subDays(30))->count();
        $withOrg = Contact::whereNotNull('organization_id')->count();
        $withoutOrg = Contact::whereNull('organization_id')->count();

        return [
            'new'        => $newCount,
            'pending'    => Contact::whereNull('assigned_to')->count(),
            'converted'  => Contact::has('opportunities')->count(),
            'total_value'=> Opportunity::whereNotNull('contact_id')->sum('amount'),
            'statuses'   => [
                ['label' => 'متصل به سازمان', 'count' => $withOrg],
                ['label' => 'بدون سازمان', 'count' => $withoutOrg],
            ],
        ];
    }

    private function getOpportunityStats(): array
    {
        $today = now()->toDateString();

        $statusRows = Opportunity::selectRaw('stage, COUNT(*) as aggregate')
            ->groupBy('stage')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => FormOptionsHelper::getOpportunityStageLabel($row->stage),
                    'count' => (int) $row->aggregate,
                ];
            })
            ->values()
            ->all();

        return [
            'new'        => Opportunity::whereDate('created_at', '>=', now()->subDays(30))->count(),
            'pending'    => Opportunity::whereNotNull('next_follow_up')->whereDate('next_follow_up', '<=', $today)->count(),
            'converted'  => Opportunity::whereIn('stage', ['won'])->count(),
            'total_value'=> Opportunity::sum('amount'),
            'statuses'   => $statusRows,
        ];
    }

    private function getProformaStats(): array
    {
        $statusRows = Proforma::select(DB::raw('COALESCE(approval_stage, proforma_stage) as stage_key'), DB::raw('COUNT(*) as aggregate'))
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
            'new'         => Proforma::whereDate('created_at', '>=', now()->subDays(30))->count(),
            'pending'     => Proforma::whereIn(DB::raw('COALESCE(approval_stage, proforma_stage)'), $pendingStages)->count(),
            'converted'   => Proforma::whereIn(DB::raw('COALESCE(approval_stage, proforma_stage)'), $convertedStages)->count(),
            'total_value' => Proforma::sum('total_amount'),
            'statuses'    => $statusRows,
        ];
    }

    private function getPurchaseOrderStats(): array
    {
        $statusLabels = PurchaseOrder::statuses();

        $statusRows = PurchaseOrder::select('status', DB::raw('COUNT(*) as aggregate'))
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
            'new'         => PurchaseOrder::whereDate('created_at', '>=', now()->subDays(30))->count(),
            'pending'     => PurchaseOrder::whereIn('status', $pendingStatuses)->count(),
            'converted'   => PurchaseOrder::whereIn('status', $convertedStatuses)->count(),
            'total_value' => PurchaseOrder::sum('total_amount'),
            'statuses'    => $statusRows,
        ];
    }

    private function getLeadStats($user = null): array
    {
        $today = now()->toDateString();

        $visibleLeadQuery = SalesLead::query();
        if ($user) {
            $visibleLeadQuery = $visibleLeadQuery->visibleFor($user, 'leads');
        }

        $statusRows = SalesLead::select('lead_status', DB::raw('COUNT(*) as aggregate'))
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
            'new'         => SalesLead::whereDate('created_at', '>=', now()->subDays(30))->count(),
            'pending'     => SalesLead::whereNotNull('next_follow_up_date')->whereDate('next_follow_up_date', '<=', $today)->count(),
            'converted'   => (clone $visibleLeadQuery)->whereHas('opportunities')->count(),
            'total_value' => null,
            'statuses'    => $statusRows,
        ];
    }
}
