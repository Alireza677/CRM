<?php

namespace Tests\Unit;

use App\Models\OnlineChatMessage;
use Tests\TestCase;

class OnlineChatMessageMentionTest extends TestCase
{
    public function test_extract_mention_ids_from_body(): void
    {
        $body = 'سلام @[Ali Test](user:12) و @[Sara](user:5) و @[Ali Test](user:12)';

        $ids = OnlineChatMessage::extractMentionIds($body);

        $this->assertSame([12, 5], $ids);
    }

    public function test_render_body_for_display_replaces_tokens(): void
    {
        $body = 'سلام @[Ali Test](user:12) خوش آمدی';

        $display = OnlineChatMessage::renderBodyForDisplay($body);

        $this->assertSame('سلام @Ali Test خوش آمدی', $display);
    }
}
