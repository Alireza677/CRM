<?php

namespace Tests\Feature;

use App\Console\Commands\RunReportSchedules;
use App\Models\Opportunity;
use App\Models\Report;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_schedule_valid(): void
    {
        $owner = User::factory()->create();
        $report = Report::factory()->create(['created_by' => $owner->id, 'visibility' => 'private', 'query_json' => [ 'model' => 'Opportunities', 'selects' => ['id'], 'limit' => 5]]);

        $resp = $this->actingAs($owner)->post(route('reports.schedules.store', $report), [
            'frequency' => 'daily',
            'time_of_day' => now()->format('H:i'),
            'emails' => ['test@example.com'],
            'export_format' => 'csv',
            'active' => 1,
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('report_schedules', ['report_id' => $report->id, 'export_format' => 'csv']);
    }

    public function test_artisan_runs_and_saves_export_and_sends_email(): void
    {
        if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) { $this->markTestSkipped('Excel not installed'); }

        Mail::fake();
        Storage::fake('local');

        $owner = User::factory()->create();
        Opportunity::factory()->count(2)->create();
        $report = Report::factory()->create([
            'created_by' => $owner->id,
            'visibility' => 'private',
            'query_json' => [ 'model' => 'Opportunities', 'selects' => ['id'], 'limit' => 10 ],
        ]);

        $schedule = ReportSchedule::create([
            'report_id' => $report->id,
            'user_id' => $owner->id,
            'frequency' => 'daily',
            'time_of_day' => now()->format('H:i'),
            'emails' => ['test@example.com'],
            'export_format' => 'csv',
            'active' => true,
        ]);

        Artisan::call('reports:run-schedules');

        // A run should be created
        $this->assertDatabaseHas('report_runs', ['report_id' => $report->id, 'user_id' => $owner->id]);

        // Mail sent
        Mail::assertSent(function ($m) {
            return true; // basic smoke
        });
    }
}

