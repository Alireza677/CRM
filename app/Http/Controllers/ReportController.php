<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Reports\QueryEngine;
use App\Services\Reports\ExportService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\ReportRun;
use App\Models\ReportSchedule;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $userId = $request->user()->id;
        $visibility = $request->get('visibility');
        $search = $request->get('q');

        $query = Report::query()
            ->with(['creator'])
            ->where(function ($q) use ($userId) {
                $q->where('visibility', 'public')
                  ->orWhere('created_by', $userId)
                  ->orWhereHas('sharedUsers', function ($q2) use ($userId) {
                      $q2->where('users.id', $userId);
                  });
            });

        if (in_array($visibility, ['private','public','shared'], true)) {
            if ($visibility === 'private') {
                $query->where('created_by', $userId)->where('visibility', 'private');
            } elseif ($visibility === 'public') {
                $query->where('visibility', 'public');
            } elseif ($visibility === 'shared') {
                $query->where('visibility', 'shared')
                    ->whereHas('sharedUsers', function ($q2) use ($userId) {
                        $q2->where('users.id', $userId);
                    });
            }
        }

        if ($search) {
            $query->where('title', 'like', "%".$search."%");
        }

        // Sorting whitelist
        $sort = $request->get('sort');
        $dir = strtolower($request->get('dir','desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['title','created_at','created_by'];
        if (in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderByDesc('is_pinned')->orderByDesc('created_at');
        }

        $reports = $query
            ->paginate(15)
            ->withQueryString();

        return view('reports.index', compact('reports', 'visibility', 'search'));
    }

    public function create()
    {
        $this->authorize('create', Report::class);

        $users = User::query()->orderBy('name')->get(['id','name']);
        return view('reports.create', compact('users'));
    }

    public function store(StoreReportRequest $request)
    {
        $this->authorize('create', Report::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $report = Report::create($data);

        // Handle sharing on create if visibility is shared
        $this->syncShares($report, $request);

        return redirect()->route('reports.show', $report)
            ->with('success', 'گزارش با موفقیت ایجاد شد.');
    }

    public function show(Report $report)
    {
        $this->authorize('view', $report);

        $report->load(['creator','sharedUsers']);
        $users = User::query()->orderBy('name')->get(['id','name']);

        return view('reports.show', compact('report','users'));
    }

    public function edit(Report $report)
    {
        $this->authorize('update', $report);

        $users = User::query()->orderBy('name')->get(['id','name']);
        $report->load('sharedUsers');

        return view('reports.edit', compact('report','users'));
    }

    public function update(UpdateReportRequest $request, Report $report)
    {
        $this->authorize('update', $report);

        $report->update($request->validated());

        // Optionally update shares if provided
        $this->syncShares($report, $request);

        return redirect()->route('reports.show', $report)
            ->with('success', 'گزارش با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Report $report)
    {
        $this->authorize('delete', $report);
        $report->delete();
        return redirect()->route('reports.index')
            ->with('success', 'گزارش حذف شد.');
    }

    public function preview(Request $request, QueryEngine $engine)
    {
        $this->authorize('viewAny', Report::class);
        $data = $request->validate([
            'query_json' => ['required','array'],
        ]);

        try {
            $result = $engine->build($data['query_json']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        \Log::channel('reports')->info('preview', [
            'report_id' => null,
            'user_id' => optional($request->user())->id,
            'exec_ms' => $result['meta']['exec_ms'] ?? null,
            'rows_count' => $result['meta']['total'] ?? null,
            'timestamp' => now()->toDateTimeString(),
        ]);
        return response()->json($result);
    }

    public function run(Request $request, Report $report, QueryEngine $engine)
    {
        $this->authorize('view', $report);
        $data = $report->query_json ?? null;
        if (empty($data) || !is_array($data) || empty($data['model'])) {
            return view('reports.run', [
                'report' => $report,
                'result' => null,
                'message' => 'این گزارش هنوز پیکربندی کوئری ندارد.',
            ]);
        }

        $page = max(1, (int)$request->get('page', 1));
        $cacheEnabled = (int)$request->get('cache', 0) === 1;
        $result = null;

        if ($cacheEnabled) {
            $sorted = Arr::sortRecursive($data);
            $hash = hash('sha256', json_encode($sorted));
            $cacheKey = sprintf('report:%d:%s:page:%d', $report->id, $hash, $page);
            $result = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($engine, $data, $page) {
                $data['page'] = $page;
                return $engine->build($data);
            });
        } else {
            $data['page'] = $page;
            $result = $engine->build($data);
        }

        // Log the run
        try {
            ReportRun::create([
                'report_id' => $report->id,
                'user_id' => $request->user()->id,
                'executed_at' => now(),
                'exec_ms' => (int)($result['meta']['exec_ms'] ?? 0),
                'rows_count' => (int)($result['meta']['total'] ?? 0),
                'cache_used' => $cacheEnabled,
            ]);
        } catch (\Throwable $e) { /* ignore logging errors */ }

        \Log::channel('reports')->info('run', [
            'report_id' => $report->id,
            'user_id' => $request->user()->id,
            'exec_ms' => $result['meta']['exec_ms'] ?? null,
            'rows_count' => $result['meta']['total'] ?? null,
            'timestamp' => now()->toDateTimeString(),
        ]);

        return view('reports.run', [
            'report' => $report,
            'result' => $result,
            'message' => null,
        ]);
    }

    public function share(Request $request, Report $report)
    {
        $this->authorize('share', $report);

        $request->validate([
            'shared_user_ids' => ['nullable','array'],
            'shared_user_ids.*' => ['integer','exists:users,id'],
            'shared_can_edit_ids' => ['nullable','array'],
            'shared_can_edit_ids.*' => ['integer','exists:users,id'],
        ]);

        // filter to only active users if column exists
        $ids = collect($request->input('shared_user_ids', []))->map(fn($v)=>(int)$v)->filter()->unique();
        if ($ids->isNotEmpty() && \Schema::hasColumn('users','active')) {
            $activeIds = \App\Models\User::whereIn('id', $ids)->where('active', true)->pluck('id');
            $request->merge(['shared_user_ids' => $activeIds->all()]);
        }

        $this->syncShares($report, $request);

        return back()->with('success', 'اشتراک‌گذاری به‌روزرسانی شد.');
    }

    protected function syncShares(Report $report, Request $request): void
    {
        // Only apply if visibility is 'shared'
        if ($request->get('visibility', $report->visibility) !== 'shared') {
            // detach all shares when not shared visibility
            $report->sharedUsers()->sync([]);
            return;
        }

        $ids = collect($request->input('shared_user_ids', []))->filter()->map(fn($v) => (int)$v)->unique();
        $canEditIds = collect($request->input('shared_can_edit_ids', []))->filter()->map(fn($v) => (int)$v)->unique();

        $syncData = [];
        foreach ($ids as $uid) {
            $syncData[$uid] = ['can_edit' => $canEditIds->contains($uid)];
        }

        $report->sharedUsers()->sync($syncData);
    }

    // --------- Exports ---------
    public function exportCsv(Request $request, Report $report, ExportService $export)
    {
        $this->authorize('view', $report);
        $page = $request->filled('page') ? max(1, (int)$request->get('page')) : null;
        $cache = (int)$request->get('cache', 0) === 1;
        return $export->exportCsv($report, $page, $cache);
    }

    public function exportXlsx(Request $request, Report $report, ExportService $export)
    {
        $this->authorize('view', $report);
        $page = $request->filled('page') ? max(1, (int)$request->get('page')) : null;
        $cache = (int)$request->get('cache', 0) === 1;
        return $export->exportXlsx($report, $page, $cache);
    }

    public function exportPdf(Request $request, Report $report, ExportService $export)
    {
        $this->authorize('view', $report);
        $page = $request->filled('page') ? max(1, (int)$request->get('page')) : null;
        $cache = (int)$request->get('cache', 0) === 1;
        return $export->exportPdf($report, $page, $cache);
    }

    public function dashboard(Request $request)
    {
        $this->middleware('auth');

        $user = $request->user();
        $userId = $user->id;

        // Cache dashboard data 5 minutes
        [$lastRuns, $popularReports, $pinned, $totalVisible, $privateCount, $publicCount, $sharedCount, $totalRuns, $chart] = \Cache::remember(
            'reports:dashboard:'.$userId,
            now()->addMinutes(5),
            function () use ($userId) {
                $lastRuns = ReportRun::with('report')
                    ->where('user_id', $userId)
                    ->orderByDesc('executed_at')
                    ->limit(5)
                    ->get();

                $popularRaw = ReportRun::select('report_id', DB::raw('COUNT(*) as runs'))
                    ->groupBy('report_id')
                    ->orderByDesc('runs')
                    ->limit(5)
                    ->get();
                $popularReports = [];
                if ($popularRaw->isNotEmpty()) {
                    $reports = Report::whereIn('id', $popularRaw->pluck('report_id'))->get()->keyBy('id');
                    foreach ($popularRaw as $row) {
                        if (isset($reports[$row->report_id])) {
                            $popularReports[] = ['report' => $reports[$row->report_id], 'runs' => (int)$row->runs];
                        }
                    }
                }

                $pinned = Report::with('creator')
                    ->where('is_pinned', true)
                    ->where(function ($q) use ($userId) {
                        $q->where('visibility', 'public')
                            ->orWhere('created_by', $userId)
                            ->orWhereHas('sharedUsers', function ($q2) use ($userId) { $q2->where('users.id', $userId); });
                    })
                    ->orderByDesc('updated_at')
                    ->limit(10)
                    ->get();

                $totalVisible = Report::where(function ($q) use ($userId) {
                    $q->where('visibility', 'public')
                    ->orWhere('created_by', $userId)
                    ->orWhereHas('sharedUsers', function ($q2) use ($userId) { $q2->where('users.id', $userId); });
                })->count();
                $privateCount = Report::where('created_by', $userId)->where('visibility', 'private')->count();
                $publicCount  = Report::where('visibility', 'public')->count();
                $sharedCount  = Report::where('visibility', 'shared')->whereHas('sharedUsers', function ($q2) use ($userId) { $q2->where('users.id', $userId); })->count();
                $totalRuns    = ReportRun::count();

                $chart = ReportRun::statsLast30Days();
                return [$lastRuns,$popularReports,$pinned,$totalVisible,$privateCount,$publicCount,$sharedCount,$totalRuns,$chart];
            }
        );

        return view('reports.dashboard', compact(
            'lastRuns','popularReports','pinned','totalVisible','privateCount','publicCount','sharedCount','totalRuns','chart'
        ));
    }

    // --------- Schedules management ---------
    public function schedules(Request $request, Report $report)
    {
        $this->authorize('update', $report);
        $schedules = ReportSchedule::where('report_id', $report->id)->orderByDesc('created_at')->get();
        return view('reports.schedules', compact('report','schedules'));
    }

    public function storeSchedule(Request $request, Report $report)
    {
        $this->authorize('update', $report);

        $data = $request->validate([
            'frequency' => ['required','in:daily,weekly,monthly,custom'],
            'time_of_day' => ['required','date_format:H:i'],
            'weekday' => ['nullable','integer','between:0,6'],
            'day_of_month' => ['nullable','integer','between:1,31'],
            'emails' => ['required','array','min:1'],
            'emails.*' => ['email:rfc,dns'],
            'export_format' => ['required','in:csv,xlsx,pdf'],
            'active' => ['nullable','boolean'],
        ]);

        // normalize emails to array
        $emails = $data['emails'];
        if (is_string($emails)) {
            $emails = array_filter(array_map('trim', explode(',', $emails)));
        }

        $sched = ReportSchedule::create([
            'report_id' => $report->id,
            'user_id' => $request->user()->id,
            'frequency' => $data['frequency'],
            'time_of_day' => $data['time_of_day'],
            'weekday' => $data['weekday'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'emails' => array_values($emails),
            'export_format' => $data['export_format'],
            'active' => (bool)($data['active'] ?? true),
        ]);

        return redirect()->route('reports.schedules', $report)->with('success','زمان‌بندی ثبت شد.');
    }

    public function destroySchedule(Request $request, Report $report, ReportSchedule $schedule)
    {
        $this->authorize('update', $report);
        abort_unless($schedule->report_id === $report->id, 404);
        $schedule->delete();
        return back()->with('success','زمان‌بندی حذف شد.');
    }
}
