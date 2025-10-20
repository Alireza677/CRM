<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        // ensure some users exist
        if (User::count() < 3) {
            User::factory(3)->create();
        }

        // create sample reports
        $reports = Report::factory()->count(9)->create();

        // add shares for reports with visibility=shared
        $userIds = User::pluck('id');
        foreach ($reports as $report) {
            if ($report->visibility !== 'shared') {
                continue;
            }
            $shareTo = $userIds->shuffle()->take(rand(1, min(3, $userIds->count())));
            $canEditSubset = $shareTo->shuffle()->take(rand(0, $shareTo->count()));
            $sync = [];
            foreach ($shareTo as $uid) {
                // avoid sharing to creator
                if ($uid == $report->created_by) continue;
                $sync[$uid] = ['can_edit' => $canEditSubset->contains($uid)];
            }
            if ($sync) {
                $report->sharedUsers()->syncWithoutDetaching($sync);
            }
        }
    }
}

