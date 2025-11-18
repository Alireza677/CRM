<?php

namespace Tests\Feature;

use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRuleConditionsUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_conditions_from_flat_keys_on_purchase_order_status(): void
    {
        $user = User::factory()->create();

        $rule = NotificationRule::create([
            'module' => 'purchase_orders',
            'event'  => 'status.changed',
            'enabled' => true,
            'channels' => ['database'],
            'conditions' => null,
            'subject_template' => 'PO {po_number}',
            'body_template' => 'از {from_status} به {to_status}',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('settings.notifications.index'))
            ->put(route('settings.notifications.update', $rule), [
                'enabled' => 1,
                // Post flat keys as sent by hidden inputs in blade
                'from_status' => 'created',
                'to_status'   => 'approved',
            ]);

        $response->assertRedirect(route('settings.notifications.index'));

        $rule->refresh();
        $this->assertIsArray($rule->conditions);
        $this->assertSame('created', $rule->conditions['from_status'] ?? null);
        $this->assertSame('approved', $rule->conditions['to_status'] ?? null);
    }

    public function test_store_resolves_supports_conditions_with_dotted_event_key(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('settings.notifications.index'))
            ->post(route('settings.notifications.store'), [
                'module'  => 'purchase_orders',
                'event'   => 'status.changed',
                'enabled' => 1,
                // Nested conditions as the Blade also posts
                'conditions' => [
                    'from_status' => 'created',
                    'to_status'   => 'approved',
                ],
            ]);

        $response->assertRedirect(route('settings.notifications.index'));

        $rule = NotificationRule::where('module','purchase_orders')->where('event','status.changed')->first();
        $this->assertNotNull($rule);
        $this->assertIsArray($rule->conditions);
        $this->assertSame('created', $rule->conditions['from_status'] ?? null);
        $this->assertSame('approved', $rule->conditions['to_status'] ?? null);
    }

    public function test_store_creates_additional_purchase_order_rules(): void
    {
        $user = User::factory()->create();

        NotificationRule::create([
            'module' => 'purchase_orders',
            'event' => 'status.changed',
            'enabled' => true,
            'channels' => ['database'],
            'conditions' => ['from_status' => 'created', 'to_status' => 'approved'],
            'subject_template' => 'PO {po_number}',
            'body_template' => '{from_status} -> {to_status}',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('settings.notifications.index'))
            ->post(route('settings.notifications.store'), [
                'module'  => 'purchase_orders',
                'event'   => 'status.changed',
                'enabled' => 1,
                'conditions' => [
                    'from_status' => 'approved',
                    'to_status'   => 'delivered',
                ],
            ]);

        $response->assertRedirect(route('settings.notifications.index'));

        $this->assertSame(
            2,
            NotificationRule::where('module', 'purchase_orders')->where('event', 'status.changed')->count()
        );
    }
}
