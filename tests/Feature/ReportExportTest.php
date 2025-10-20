<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create();
    }

    protected function sampleReport(User $owner): Report
    {
        Opportunity::factory()->count(5)->create(['name' => 'E', 'amount' => 10]);
        return Report::factory()->create([
            'created_by' => $owner->id,
            'visibility' => 'public',
            'query_json' => [
                'model' => 'Opportunities',
                'selects' => ['id','name','amount'],
                'aggregates' => [['fn' => 'sum','field' => 'amount','as' => 'sum_amount']],
                'group_by' => ['name'],
                'limit' => 50,
            ],
        ]);
    }

    public function test_export_csv_downloads_file(): void
    {
        if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) { $this->markTestSkipped('Excel not installed'); }
        $owner = User::factory()->create();
        $report = $this->sampleReport($owner);

        $resp = $this->actingAs($owner)->get(route('reports.export.csv', $report));
        $resp->assertOk();
        $this->assertStringContainsString('text/csv', $resp->headers->get('content-type'));
    }

    public function test_export_xlsx_downloads_file(): void
    {
        if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) { $this->markTestSkipped('Excel not installed'); }
        $owner = User::factory()->create();
        $report = $this->sampleReport($owner);
        $resp = $this->actingAs($owner)->get(route('reports.export.xlsx', $report));
        $resp->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $resp->headers->get('content-type'));
    }

    public function test_export_pdf_downloads_file(): void
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) { $this->markTestSkipped('DomPDF not installed'); }
        $owner = User::factory()->create();
        $report = $this->sampleReport($owner);
        $resp = $this->actingAs($owner)->get(route('reports.export.pdf', $report));
        $resp->assertOk();
        $this->assertStringContainsString('application/pdf', $resp->headers->get('content-type'));
    }

    public function test_export_respects_max_rows(): void
    {
        if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) { $this->markTestSkipped('Excel not installed'); }
        config()->set('reports.max_export_rows', 1);
        $owner = User::factory()->create();
        $report = $this->sampleReport($owner);
        $this->actingAs($owner)->get(route('reports.export.csv', $report))->assertStatus(422);
    }

    public function test_run_view_has_chart_data_smoke(): void
    {
        $owner = User::factory()->create();
        $report = $this->sampleReport($owner);
        $this->actingAs($owner)
            ->get(route('reports.run', $report))
            ->assertOk()
            ->assertSee('window.reportChartData');
    }

    public function test_run_view_shows_large_rows_warning(): void
    {
        $owner = User::factory()->create();
        // make meta total > 50000 by setting low limit and many rows
        \App\Models\Opportunity::factory()->count(60000)->create();
        $report = Report::factory()->create([
            'created_by' => $owner->id,
            'visibility' => 'public',
            'query_json' => [ 'model' => 'Opportunities', 'selects' => ['id'], 'limit' => 10 ],
        ]);
        $this->actingAs($owner)
            ->get(route('reports.run', $report))
            ->assertOk()
            ->assertSee('تعداد نتایج زیاد است، لطفاً فیلتر کنید.');
    }
}
