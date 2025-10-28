<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Imports\OpportunityImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OpportunityImportController extends Controller
{
    public function create()
    {
        $this->authorize('create', \App\Models\Opportunity::class);

        $breadcrumb = [
            ['title' => 'داشبورد', 'url' => route('dashboard')],
            ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
            ['title' => 'ورود گروهی فرصت‌ها'],
        ];

        return view('sales.opportunities.import', compact('breadcrumb'));
    }

    public function dryRun(Request $request, OpportunityImportService $service)
    {
        $this->authorize('create', \App\Models\Opportunity::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt',
            'match_by' => 'nullable|string',
        ]);

        $path = $request->file('file')->store('imports');

        $report = $service->dryRun(
            Storage::path($path),
            auth()->id(),
            (string) $request->input('match_by', '')
        );

        return back()->with([
            'dry_run' => $report,
            'uploaded_path' => $path,
            'match_by' => (string) $request->input('match_by', ''),
        ]);
    }

    public function store(Request $request, OpportunityImportService $service)
    {
        $this->authorize('create', \App\Models\Opportunity::class);

        $request->validate([
            'uploaded_path' => 'required|string',
            'match_by' => 'nullable|string',
        ]);

        $result = $service->import(
            Storage::path($request->uploaded_path),
            auth()->id(),
            (string) $request->input('match_by', '')
        );

        return back()->with('import_result', $result);
    }
}

