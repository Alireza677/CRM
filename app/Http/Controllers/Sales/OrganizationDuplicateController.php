<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Jobs\ScanDuplicateGroupsJob;
use App\Models\DuplicateGroup;
use App\Models\Organization;
use App\Services\Merge\Configs\OrganizationMergeConfig;
use App\Services\Merge\MergeService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrganizationDuplicateController extends Controller
{
    public function index()
    {
        $groups = DuplicateGroup::query()
            ->where('entity_type', OrganizationMergeConfig::ENTITY_TYPE)
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('sales.organizations.duplicates.index', compact('groups'));
    }

    public function scan()
    {
        ScanDuplicateGroupsJob::dispatchSync(OrganizationMergeConfig::ENTITY_TYPE);

        return redirect()
            ->route('sales.organizations.duplicates.index')
            ->with('success', 'اسکن با موفقیت انجام شد.');
    }

    public function review(DuplicateGroup $group)
    {
        if ($group->entity_type !== OrganizationMergeConfig::ENTITY_TYPE) {
            abort(404);
        }

        $organizationIds = $group->items()->pluck('entity_id')->all();
        $config = new OrganizationMergeConfig();
        $table = (new Organization())->getTable();
        $fields = array_values(array_filter(
            $config->mergeableFields(),
            fn (string $field) => Schema::hasColumn($table, $field)
        ));

        $organizations = Organization::withoutGlobalScopes()
            ->whereIn('id', $organizationIds)
            ->withCount(['contacts', 'opportunities'])
            ->get();

        $notesCounts = DB::table('notes')
            ->select('noteable_id', DB::raw('count(*) as total'))
            ->where('noteable_type', Organization::class)
            ->whereIn('noteable_id', $organizationIds)
            ->groupBy('noteable_id')
            ->pluck('total', 'noteable_id')
            ->all();

        $proformaCounts = DB::table('proformas')
            ->select('organization_id', DB::raw('count(*) as total'))
            ->whereIn('organization_id', $organizationIds)
            ->groupBy('organization_id')
            ->pluck('total', 'organization_id')
            ->all();

        $quotationCounts = DB::table('quotations')
            ->select('organization_id', DB::raw('count(*) as total'))
            ->whereIn('organization_id', $organizationIds)
            ->groupBy('organization_id')
            ->pluck('total', 'organization_id')
            ->all();

        $relationsSummary = [];
        foreach ($organizations as $organization) {
            $relationsSummary[$organization->id] = [
                'contacts' => $organization->contacts_count ?? 0,
                'opportunities' => $organization->opportunities_count ?? 0,
                'proformas' => $proformaCounts[$organization->id] ?? 0,
                'quotations' => $quotationCounts[$organization->id] ?? 0,
                'notes' => $notesCounts[$organization->id] ?? 0,
            ];
        }

        $conflicts = $this->detectConflicts($organizations, $fields);

        $defaultWinnerId = $organizations->first()?->id;

        return view('sales.organizations.duplicates.review', compact(
            'group',
            'organizations',
            'fields',
            'relationsSummary',
            'conflicts',
            'defaultWinnerId'
        ));
    }

    public function merge(Request $request, MergeService $mergeService)
    {
        $group = DuplicateGroup::findOrFail($request->input('group_id'));
        if ($group->entity_type !== OrganizationMergeConfig::ENTITY_TYPE) {
            abort(404);
        }

        $organizationIds = $group->items()->pluck('entity_id')->all();
        $winnerId = (int) $request->input('winner_id');
        $loserIds = array_map('intval', (array) $request->input('loser_ids', []));
        $loserIds = array_values(array_unique(array_diff($loserIds, [$winnerId])));

        if (!in_array($winnerId, $organizationIds, true)) {
            return back()->withErrors(['winner_id' => 'سازمان انتخاب‌شده معتبر نیست.'])->withInput();
        }

        if (empty($loserIds)) {
            return back()->withErrors(['loser_ids' => 'حداقل یک مورد برای ادغام انتخاب کنید.'])->withInput();
        }

        foreach ($loserIds as $loserId) {
            if (!in_array($loserId, $organizationIds, true)) {
                return back()->withErrors(['loser_ids' => 'یکی از موارد انتخاب‌شده معتبر نیست.'])->withInput();
            }
        }

        $fieldResolution = (array) $request->input('field_resolution', []);
        foreach ($fieldResolution as $field => $selectedId) {
            $fieldResolution[$field] = (int) $selectedId;

            if (!in_array((int) $selectedId, $organizationIds, true)) {
                return back()->withErrors(['field_resolution' => 'انتخاب مقدار برای یکی از فیلدها معتبر نیست.'])->withInput();
            }
        }

        $table = (new Organization())->getTable();
        $fields = array_values(array_filter(
            (new OrganizationMergeConfig())->mergeableFields(),
            fn (string $field) => Schema::hasColumn($table, $field)
        ));

        $organizations = Organization::withoutGlobalScopes()
            ->whereIn('id', array_merge([$winnerId], $loserIds))
            ->get();

        $conflicts = $this->detectConflicts($organizations, $fields);

        foreach ($conflicts as $field => $values) {
            if (!Arr::get($fieldResolution, $field)) {
                return back()->withErrors([
                    'field_resolution' => "برای فیلد «{$field}» مقدار نهایی را انتخاب کنید.",
                ])->withInput();
            }
        }

        $mergeService->merge(
            new OrganizationMergeConfig(),
            $winnerId,
            $loserIds,
            $fieldResolution,
            auth()->id()
        );

        $group->items()
            ->whereIn('entity_id', $loserIds)
            ->delete();

        if ($group->items()->count() < 2) {
            $group->delete();
        }

        return redirect()
            ->route('sales.organizations.duplicates.index')
            ->with('success', 'ادغام سازمان‌ها با موفقیت انجام شد.');
    }

    private function detectConflicts($organizations, array $fields): array
    {
        $conflicts = [];

        foreach ($fields as $field) {
            $values = [];
            foreach ($organizations as $organization) {
                $value = $organization->{$field} ?? null;

                if ($this->isBlank($value)) {
                    continue;
                }

                $values[(string) $value] = true;
            }

            if (count($values) > 1) {
                $conflicts[$field] = array_keys($values);
            }
        }

        return $conflicts;
    }

    private function isBlank($value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }
}
