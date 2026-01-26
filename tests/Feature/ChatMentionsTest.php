<?php

namespace Tests\Feature;

use App\Models\OnlineChatGroup;
use App\Models\OnlineChatMembership;
use App\Models\OnlineChatMessage;
use App\Models\User;
use App\Notifications\CustomRoutedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Middleware\Authorize;
use Tests\TestCase;

class ChatMentionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mentions_are_persisted_and_notified(): void
    {
        Notification::fake();
        $this->withoutMiddleware(Authorize::class);

        $sender = User::factory()->create(['name' => 'Sender User']);
        $mentioned = User::factory()->create(['name' => 'Mentioned User']);
        $outsider = User::factory()->create(['name' => 'Outside User']);

        $group = OnlineChatGroup::create([
            'title' => 'Test Group',
            'description' => null,
            'created_by' => $sender->id,
            'is_active' => true,
        ]);

        OnlineChatMembership::create([
            'online_chat_group_id' => $group->id,
            'user_id' => $sender->id,
            'role' => OnlineChatMembership::ROLE_OWNER,
        ]);

        OnlineChatMembership::create([
            'online_chat_group_id' => $group->id,
            'user_id' => $mentioned->id,
            'role' => OnlineChatMembership::ROLE_MEMBER,
        ]);

        $body = 'سلام @[Mentioned User](user:' . $mentioned->id . ') و @[Outside User](user:' . $outsider->id . ')';

        $response = $this->actingAs($sender)->postJson(
            route('chat.groups.messages.store', ['group' => $group]),
            ['body' => $body]
        );

        $response->assertStatus(201);

        $message = OnlineChatMessage::first();
        $this->assertNotNull($message);
        $this->assertSame($body, $message->body);

        $this->assertDatabaseHas('online_chat_message_mentions', [
            'message_id' => $message->id,
            'user_id' => $mentioned->id,
        ]);

        $this->assertDatabaseMissing('online_chat_message_mentions', [
            'message_id' => $message->id,
            'user_id' => $outsider->id,
        ]);

        Notification::assertSentTo($mentioned, CustomRoutedNotification::class);
    }

    public function test_mention_all_notifies_group_members(): void
    {
        Notification::fake();
        $this->withoutMiddleware(Authorize::class);

        $sender = User::factory()->create(['name' => 'Sender User']);
        $memberA = User::factory()->create(['name' => 'Member A']);
        $memberB = User::factory()->create(['name' => 'Member B']);
        $outsider = User::factory()->create(['name' => 'Outside User']);

        $group = OnlineChatGroup::create([
            'title' => 'All Group',
            'description' => null,
            'created_by' => $sender->id,
            'is_active' => true,
        ]);

        OnlineChatMembership::create([
            'online_chat_group_id' => $group->id,
            'user_id' => $sender->id,
            'role' => OnlineChatMembership::ROLE_OWNER,
        ]);

        OnlineChatMembership::create([
            'online_chat_group_id' => $group->id,
            'user_id' => $memberA->id,
            'role' => OnlineChatMembership::ROLE_MEMBER,
        ]);

        OnlineChatMembership::create([
            'online_chat_group_id' => $group->id,
            'user_id' => $memberB->id,
            'role' => OnlineChatMembership::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($sender)->postJson(
            route('chat.groups.messages.store', ['group' => $group]),
            ['body' => 'سلام @all']
        );

        $response->assertStatus(201);

        $message = OnlineChatMessage::first();
        $this->assertNotNull($message);

        $this->assertDatabaseHas('online_chat_message_mentions', [
            'message_id' => $message->id,
            'user_id' => $memberA->id,
        ]);
        $this->assertDatabaseHas('online_chat_message_mentions', [
            'message_id' => $message->id,
            'user_id' => $memberB->id,
        ]);
        $this->assertDatabaseMissing('online_chat_message_mentions', [
            'message_id' => $message->id,
            'user_id' => $outsider->id,
        ]);

        Notification::assertSentTo($memberA, CustomRoutedNotification::class);
        Notification::assertSentTo($memberB, CustomRoutedNotification::class);
        Notification::assertNotSentTo($outsider, CustomRoutedNotification::class);
    }
}
