<?php

namespace Tests\Unit;

use App\Models\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_email_template_with_placeholders(): void
    {
        NotificationTemplate::create([
            'module' => 'proformas',
            'event' => 'approval.sent',
            'channel' => 'email',
            'subject_template' => 'PF {{ actor.name }} {proforma_number}',
            'body_template' => 'Customer: {customer_name} / Link: {{ url }}',
        ]);

        $ctx = [
            'proforma_number' => 'PF-1001',
            'customer_name' => 'ACME',
            'url' => 'https://example.test/pf/1',
            'actor.name' => 'Sender',
        ];

        $res = \App\Support\NotificationTemplateResolver::resolve('proformas','approval.sent','email', $ctx);
        $this->assertEquals('PF Sender PF-1001', $res['subject']);
        $this->assertStringContainsString('Customer: ACME', (string) $res['body']);
        $this->assertStringContainsString('https://example.test/pf/1', (string) $res['body']);
    }
}

