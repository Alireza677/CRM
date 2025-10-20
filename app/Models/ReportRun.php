<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReportRun extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'report_id','user_id','executed_at','exec_ms','rows_count','cache_used'
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'cache_used' => 'boolean',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function statsLast30Days(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $rows = DB::table('report_runs')
            ->select(DB::raw('DATE(executed_at) as d'), DB::raw('COUNT(*) as c'))
            ->where('executed_at', '>=', $start)
            ->groupBy(DB::raw('DATE(executed_at)'))
            ->orderBy(DB::raw('DATE(executed_at)'))
            ->get();

        $map = [];
        foreach ($rows as $r) { $map[$r->d] = (int) $r->c; }

        $labels = [];
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $day = Carbon::now()->subDays(29 - $i)->toDateString();
            $labels[] = $day;
            $data[] = $map[$day] ?? 0;
        }
        return ['labels' => $labels, 'data' => $data];
    }
}

