<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderWorkflowSetting;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\PurchaseOrderReadyForDeliveryNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PurchaseOrderReadyForDeliveryNotificationTest extends TestCase
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

    public function test_requester_is_notified_after_third_approval(): void
    {
        Notification::fake();

        $requester = User::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $accounting = User::factory()->create();
        $supplier = $this->baseSupplier();

        $settings = PurchaseOrderWorkflowSetting::first() ?: new PurchaseOrderWorkflowSetting();
        $settings->fill([
            'first_approver_id' => $first->id,
            'second_approver_id' => $second->id,
            'accounting_user_id' => $accounting->id,
        ]);
        $settings->save();

        $po = $this->basePurchaseOrder($requester, $supplier, 'created');

        $this->actingAs($first);
        $this->post(route('inventory.purchase-orders.approve', $po->id))->assertStatus(302);
        $po->refresh();
        $this->assertEquals('manager_approval', $po->status);

        $this->actingAs($second);
        $this->post(route('inventory.purchase-orders.approve', $po->id))->assertStatus(302);
        $po->refresh();
        $this->assertEquals('accounting_approval', $po->status);

        $this->actingAs($accounting);
        $this->post(route('inventory.purchase-orders.approve', $po->id))->assertStatus(302);
        $po->refresh();

        $this->assertEquals('purchasing', $po->status);
        $this->assertNotNull($po->ready_for_delivery_notified_at);

        Notification::assertSentTo($requester, PurchaseOrderReadyForDeliveryNotification::class);
    }

    public function test_no_duplicate_notification_if_already_notified(): void
    {
        Notification::fake();

        $requester = User::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $accounting = User::factory()->create();
        $supplier = $this->baseSupplier();

        $settings = PurchaseOrderWorkflowSetting::first() ?: new PurchaseOrderWorkflowSetting();
        $settings->fill([
            'first_approver_id' => $first->id,
            'second_approver_id' => $second->id,
            'accounting_user_id' => $accounting->id,
        ]);
        $settings->save();

        $po = $this->basePurchaseOrder($requester, $supplier, 'accounting_approval');
        $po->ready_for_delivery_notified_at = now();
        $po->save();

        $this->actingAs($accounting);
        $this->post(route('inventory.purchase-orders.approve', $po->id))->assertStatus(302);

        // Ensure no new notification was sent to requester
        Notification::assertNothingSent();
    }
}

