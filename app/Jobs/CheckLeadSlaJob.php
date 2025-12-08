<?php

namespace App\Jobs;

use App\Models\SalesLead;
use App\Models\User;
use App\Services\Notifications\NotificationRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CheckLeadSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        protected int $slaHours = 24
    ) {}

    public function handle(NotificationRouter $router): void
    {
        $threshold = Carbon::now()->subHours($this->slaHours);
        $managers = $this->managerRecipients();

        SalesLead::query()
            ->where('pool_status', SalesLead::POOL_ASSIGNED)
            ->whereNotNull('assigned_at')
            ->whereNull('first_activity_at')
            ->where('assigned_at', '<=', $threshold)
            ->orderBy('id')
            ->chunkById(200, function ($leads) use ($router, $managers) {
                foreach ($leads as $lead) {
                    $lead->forceFill([
                        'pool_status' => SalesLead::POOL_NEEDS_REASSIGNMENT,
                    ])->save();

                    if (!empty($managers)) {
                        $this->notifyManagers($router, $lead, $managers);
                    }
                }
            });
    }

    protected function managerRecipients(): array
    {
        return User::query()
            ->where(function ($q) {
                $q->where('is_admin', true)
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', ['admin', 'super-admin', 'sales_manager']);
                    });
            })
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    protected function notifyManagers(NotificationRouter $router, SalesLead $lead, array $recipients): void
    {
        try {
            $router->route('leads', 'sla.breached', [
                'model' => $lead,
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_to,
                'url' => route('marketing.leads.show', $lead->id),
            ], $recipients);
        } catch (\Throwable $e) {
            Log::warning('CheckLeadSlaJob notification failed', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
