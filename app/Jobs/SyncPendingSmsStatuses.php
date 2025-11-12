<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\Sms\FarazEdgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPendingSmsStatuses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct()
    {
    }

    public function handle(FarazEdgeService $service): void
    {
        // Find messages with provider id but without final delivery status
        SmsLog::query()
            ->whereNotNull('provider_message_id')
            ->whereNull('status')
            ->orWhereIn('status', ['accepted', 'queued', 'sent', 'pending'])
            ->orderBy('id')
            ->chunkById(100, function ($chunk) use ($service) {
                $ids = $chunk->pluck('provider_message_id')->filter()->values()->all();
                if (empty($ids)) return;

                try {
                    // Optional: implement bulk status fetch in service if available
                    if (method_exists($service, 'fetchStatuses')) {
                        $results = $service->fetchStatuses($ids); // expected: [id => ['status' => 'delivered'|'failed'|...,'error_code'=>...,'error_message'=>...]]
                        foreach ($chunk as $log) {
                            $r = $results[$log->provider_message_id] ?? null;
                            if (!$r) continue;
                            $status = (string) ($r['status'] ?? 'unknown');
                            $failed = $status === 'failed';
                            $log->fill([
                                'status'            => $status,
                                'status_updated_at' => now(),
                                'error_code'        => $failed ? ($r['error_code'] ?? null) : null,
                                'error_message'     => $failed ? ($r['error_message'] ?? null) : null,
                            ])->save();
                        }
                    } else {
                        // If service has no fetch method, skip quietly.
                        Log::channel('sms')->info('[SMS][JOB] fetchStatuses not implemented in service; skipping batch of '.count($ids));
                    }
                } catch (\Throwable $e) {
                    Log::channel('sms')->error('[SMS][JOB] SyncPendingSmsStatuses failed', [
                        'message' => $e->getMessage(),
                        'code'    => $e->getCode(),
                    ]);
                }
            });
    }
}

