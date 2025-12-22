<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\AutomationRule;
use App\Models\Proforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProformaRejectEmergencyApproverTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_approver_can_reject_current_pending_after_superseded_rows(): void
    {
        $primaryApprover = User::factory()->create();
        $emergencyApprover = User::factory()->create();

        $rule = AutomationRule::query()->forceCreate([
            'proforma_stage' => 'send_for_approval',
            'operator'       => '=',
            'value'          => 'send_for_approval',
            'approver_1'     => $primaryApprover->id,
            'approver_2'     => null,
            'emergency_approver_id' => $emergencyApprover->id,
        ]);

        $proforma = Proforma::create([
            'subject'        => 'Test Proforma',
            'proforma_stage' => 'send_for_approval',
            'approval_stage' => 'send_for_approval',
            'address_type'   => 'invoice',
        ]);

        $proforma->automation_rule_id = $rule->id;
        $proforma->save();

        $proforma->approvals()->create([
            'user_id' => $primaryApprover->id,
            'status'  => Approval::STATUS_SUPERSEDED,
            'step'    => 1,
        ]);

        $proforma->approvals()->create([
            'user_id' => $primaryApprover->id,
            'status'  => 'pending',
            'step'    => 1,
        ]);

        $response = $this->actingAs($emergencyApprover)
            ->post(route('sales.proformas.reject', $proforma), [
                'reject_reason' => 'Emergency rejection for test.',
            ]);

        $response->assertSessionHas('success');

        $proforma->refresh();
        $this->assertSame('rejected', $proforma->approval_stage);

        $rejected = $proforma->approvals()->where('status', 'rejected')->first();
        $this->assertNotNull($rejected);
        $this->assertSame($emergencyApprover->id, (int) $rejected->approved_by);
        $this->assertFalse($proforma->approvals()->where('status', 'pending')->exists());
    }

    public function test_reject_is_blocked_when_prior_step_is_not_approved(): void
    {
        $step1Approver = User::factory()->create();
        $step2Approver = User::factory()->create();
        $emergencyApprover = User::factory()->create();

        $rule = AutomationRule::query()->forceCreate([
            'proforma_stage' => 'send_for_approval',
            'operator'       => '=',
            'value'          => 'send_for_approval',
            'approver_1'     => $step1Approver->id,
            'approver_2'     => $step2Approver->id,
            'emergency_approver_id' => $emergencyApprover->id,
        ]);

        $proforma = Proforma::create([
            'subject'        => 'Test Proforma Blocker',
            'proforma_stage' => 'send_for_approval',
            'approval_stage' => 'send_for_approval',
            'address_type'   => 'invoice',
        ]);

        $proforma->automation_rule_id = $rule->id;
        $proforma->save();

        $proforma->approvals()->create([
            'user_id' => $step1Approver->id,
            'status'  => 'rejected',
            'step'    => 1,
        ]);

        $proforma->approvals()->create([
            'user_id' => $step2Approver->id,
            'status'  => 'pending',
            'step'    => 2,
        ]);

        $response = $this->actingAs($emergencyApprover)
            ->post(route('sales.proformas.reject', $proforma), [
                'reject_reason' => 'Should be blocked.',
            ]);

        $response->assertSessionHas('error');

        $proforma->refresh();
        $this->assertSame('send_for_approval', $proforma->approval_stage);
    }
}
