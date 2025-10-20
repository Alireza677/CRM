<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_from_dashboard(): void
    {
        $this->get(route('reports.dashboard'))->assertRedirect('/login');
    }

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('reports.dashboard'))
            ->assertOk()
            ->assertSee('داشبورد گزارش‌ها')
            ->assertSee('x-breadcrumb', false);
    }

    public function test_cards_counts_are_correct(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        // private for user
        Report::factory()->create(['created_by' => $user->id, 'visibility' => 'private']);
        // public
        Report::factory()->create(['created_by' => $other->id, 'visibility' => 'public']);
        // shared with user
        $shared = Report::factory()->create(['created_by' => $other->id, 'visibility' => 'shared']);
        $shared->sharedUsers()->sync([$user->id => ['can_edit' => false]]);

        // a run exists
        ReportRun::create([
            'report_id' => $shared->id,
            'user_id' => $user->id,
            'executed_at' => now(),
            'exec_ms' => 100,
            'rows_count' => 10,
            'cache_used' => false,
        ]);

        $resp = $this->actingAs($user)->get(route('reports.dashboard'));
        $resp->assertOk();
        $resp->assertSee('کل گزارش‌های در دسترس');
        $resp->assertSee('خصوصی');
        $resp->assertSee('عمومی');
        $resp->assertSee('اشتراکی');
        $resp->assertSee('مجموع اجراها');
    }

    public function test_chart_has_30_days_data(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['created_by' => $user->id, 'visibility' => 'private']);
        // two runs on different days
        ReportRun::create(['report_id'=>$report->id,'user_id'=>$user->id,'executed_at'=>now()->subDays(2),'exec_ms'=>50,'rows_count'=>1,'cache_used'=>false]);
        ReportRun::create(['report_id'=>$report->id,'user_id'=>$user->id,'executed_at'=>now(),'exec_ms'=>70,'rows_count'=>2,'cache_used'=>true]);

        $this->actingAs($user)
            ->get(route('reports.dashboard'))
            ->assertOk()
            ->assertSee('window.dashboardChart');
    }
}
