<?php

namespace Tests\Feature;

use App\Mail\RoutedNotificationMail;
use App\Models\NotificationRule;
use App\Models\User;
use App\Notifications\CustomRoutedNotification;
use App\Services\Notifications\NotificationRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_default_rules(): void
    {
        $this->seed(\Database\Seeders\NotificationRuleSeeder::class);
        $this->assertDatabaseHas('notification_rules', ['module' => 'purchase_orders', 'event' => 'status.changed']);
        $this->assertDatabaseHas('notification_rules', ['module' => 'proformas', 'event' => 'approval.sent']);
        $this->assertDatabaseHas('notification_rules', ['module' => 'leads', 'event' => 'assigned.changed']);
        $this->assertDatabaseHas('notification_rules', ['module' => 'notes', 'event' => 'note.mentioned']);
    }

    public function test_condition_matching_purchase_order_status(): void
    {
        $first = NotificationRule::create([
            'module' => 'purchase_orders',
            'event' => 'status.changed',
            'enabled' => true,
            'channels' => ['database'],
            'conditions' => ['from_status' => 'created', 'to_status' => 'approved'],
            'subject_template' => 'PO {po_number}',
            'body_template' => '{from_status} -> {to_status}',
        ]);
        $second = NotificationRule::create([
            'module' => 'purchase_orders',
            'event' => 'status.changed',
            'enabled' => true,
            'channels' => ['database'],
            'conditions' => ['from_status' => 'approved', 'to_status' => 'delivered'],
            'subject_template' => 'PO {po_number} delivered',
            'body_template' => '{from_status} -> {to_status}',
        ]);

        $router = new NotificationRouter();
        $rule = $router->findRule('purchase_orders','status.changed', [
            'prev_status' => 'created',
            'new_status' => 'approved',
        ]);
        $this->assertNotNull($rule);
        $this->assertSame($first->id, $rule->id);

        $rule2 = $router->findRule('purchase_orders','status.changed', [
            'prev_status' => 'approved',
            'new_status' => 'delivered',
        ]);
        $this->assertNotNull($rule2);
        $this->assertSame($second->id, $rule2->id);

        $noRule = $router->findRule('purchase_orders','status.changed', [
            'prev_status' => 'approved',
            'new_status' => 'created',
        ]);
        $this->assertNull($noRule);
    }

    public function test_router_dispatches_notifications(): void
    {
        Mail::fake();
        Notification::fake();

        $recipient = User::factory()->create(['email' => 'test@example.com']);

        $rule = NotificationRule::create([
            'module' => 'leads',
            'event' => 'assigned.changed',
            'enabled' => true,
            'channels' => ['database','email'],
            'conditions' => null,
            'subject_template' => 'ارجاع {lead_name}',
            'body_template' => 'از {old_user} به {new_user} - {{ url }}',
        ]);

        $router = new NotificationRouter();
        $router->route('leads','assigned.changed', [
            'model' => (object)['id'=>10, 'name' => 'نمونه'],
            'old_assignee' => 'کاربر قدیم',
            'new_assignee' => 'کاربر جدید',
            'url' => 'https://example.test/leads/10',
        ], [$recipient]);

        Notification::assertSentTo($recipient, CustomRoutedNotification::class);
        Mail::assertQueued(RoutedNotificationMail::class);
    }

    public function test_rules_with_specific_conditions_are_prioritized(): void
    {
        $fallback = NotificationRule::create([
            'module' => 'purchase_orders',
            'event' => 'status.changed',
            'enabled' => true,
            'channels' => ['database'],
            'conditions' => ['from_status' => '', 'to_status' => ''],
            'subject_template' => 'Default {po_number}',
            'body_template' => 'Default template',
        ]);

        $specific = NotificationRule::create([
            'module' => 'purchase_orders',
            'event' => 'status.changed',
            'enabled' => true,
            'channels' => ['database'],
            'conditions' => ['from_status' => 'created', 'to_status' => 'manager_approval'],
            'subject_template' => 'Custom {po_number}',
            'body_template' => 'Custom template',
        ]);

        $router = new NotificationRouter();

        $match = $router->findRule('purchase_orders', 'status.changed', [
            'prev_status' => 'created',
            'new_status' => 'manager_approval',
        ]);

        $this->assertNotNull($match);
        $this->assertSame($specific->id, $match->id, 'Specific rule should win when conditions match');

        $fallbackMatch = $router->findRule('purchase_orders', 'status.changed', [
            'prev_status' => 'manager_approval',
            'new_status' => 'accounting_approval',
        ]);

        $this->assertNotNull($fallbackMatch);
        $this->assertSame($fallback->id, $fallbackMatch->id, 'Fallback rule should handle unmatched conditions');
    }
}

