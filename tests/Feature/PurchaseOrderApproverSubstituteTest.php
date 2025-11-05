<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderWorkflowSetting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseOrderApproverSubstituteTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
    }

    private function baseSupplier(): Supplier
    {
        return Supplier::create([
            'name' => 'Test Supplier',
            'is_active' => true,
        ]);
    }

    private function basePurchaseOrder(User $requester, Supplier $supplier, string $status = 'created'): PurchaseOrder
    {
        return PurchaseOrder::create([
            'subject' => 'PO Subject',
            'purchase_type' => 'normal',
            'supplier_id' => $supplier->id,
            'requested_by' => $requester->id,
            'request_date' => now(),
            'purchase_date' => now(),
            'status' => $status,
            'total_amount' => 0,
            'total_with_vat' => 0,
            'previously_paid_amount' => 0,
            'remaining_payable_amount' => 0,
            'assigned_to' => $requester->id,
        ]);
    }

    public function test_substitute_can_approve_when_main_on_leave(): void
    {
        $this->withoutMiddleware();

        $requester = User::factory()->create();
        $main = User::factory()->create(['is_on_leave' => true]);
        $sub = User::factory()->create();
        $supplier = $this->baseSupplier();

        $settings = PurchaseOrderWorkflowSetting::first() ?: new PurchaseOrderWorkflowSetting();
        $settings->fill([
            'first_approver_id' => $main->id,
            'first_approver_substitute_id' => $sub->id,
        ]);
        $settings->save();

        $po = $this->basePurchaseOrder($requester, $supplier, 'created');

        $this->actingAs($sub);
        $resp = $this->post(route('inventory.purchase-orders.approve', $po->id));
        $resp->assertStatus(302);

        $po->refresh();
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $po->id,
            'user_id' => $sub->id,
            'step' => 1,
            'status' => 'approved',
        ]);
    }

    public function test_substitute_rejected_when_main_not_on_leave(): void
    {
        $this->withoutMiddleware();

        $requester = User::factory()->create();
        $main = User::factory()->create(['is_on_leave' => false]);
        $sub = User::factory()->create();
        $supplier = $this->baseSupplier();

        $settings = PurchaseOrderWorkflowSetting::first() ?: new PurchaseOrderWorkflowSetting();
        $settings->fill([
            'first_approver_id' => $main->id,
            'first_approver_substitute_id' => $sub->id,
        ]);
        $settings->save();

        $po = $this->basePurchaseOrder($requester, $supplier, 'created');

        $this->actingAs($sub);
        $resp = $this->post(route('inventory.purchase-orders.approve', $po->id));
        // Controller returns redirect with error message (not 403)
        $resp->assertStatus(302);

        $po->refresh();
        $this->assertDatabaseMissing('approvals', [
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $po->id,
            'user_id' => $sub->id,
            'step' => 1,
            'status' => 'approved',
        ]);
        $this->assertEquals('created', $po->status);
    }

    public function test_substitute_can_approve_when_main_is_null(): void
    {
        $this->withoutMiddleware();

        $requester = User::factory()->create();
        $sub = User::factory()->create();
        $supplier = $this->baseSupplier();

        $settings = PurchaseOrderWorkflowSetting::first() ?: new PurchaseOrderWorkflowSetting();
        $settings->fill([
            'first_approver_id' => null,
            'first_approver_substitute_id' => $sub->id,
        ]);
        $settings->save();

        $po = $this->basePurchaseOrder($requester, $supplier, 'created');

        $this->actingAs($sub);
        $resp = $this->post(route('inventory.purchase-orders.approve', $po->id));
        $resp->assertStatus(302);

        $po->refresh();
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $po->id,
            'user_id' => $sub->id,
            'step' => 1,
            'status' => 'approved',
        ]);
    }
}
