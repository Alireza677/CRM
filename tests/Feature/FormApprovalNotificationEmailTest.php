<?php

namespace Tests\Feature;

use App\Mail\RoutedNotificationMail;
use App\Models\NotificationTemplate;
use App\Models\Proforma;
use App\Models\User;
use App\Notifications\FormApprovalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormApprovalNotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_mail_uses_db_template_when_available(): void
    {
        $sender = User::factory()->create(['name' => 'Alice']);
        $recipient = User::factory()->create(['name' => 'Bob']);

        $pf = Proforma::create([
            'subject' => 'Sample PF',
            'organization_name' => 'Contoso',
        ]);

        NotificationTemplate::create([
            'module' => 'proformas',
            'event'  => 'approval.sent',
            'channel'=> 'email',
            'subject_template' => 'Approval {{ actor.name }} {proforma_number}',
            'body_template'    => 'Customer {customer_name} - {{ url }}',
        ]);

        $n = FormApprovalNotification::fromModel($pf, $sender->id);
        $mail = $n->toMail($recipient);

        $this->assertInstanceOf(RoutedNotificationMail::class, $mail);
        $this->assertStringContainsString('Approval Alice PF-2002', (string) $mail->subjectText);
        $this->assertStringContainsString('Customer Contoso', (string) $mail->bodyText);
    }
}
