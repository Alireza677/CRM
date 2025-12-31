<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Jobs\ScanDuplicateGroupsJob;
use App\Models\Contact;
use App\Models\DuplicateGroup;
use App\Services\Merge\Configs\ContactMergeConfig;
use App\Services\Merge\MergeService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ContactDuplicateController extends Controller
{
    public function index()
    {
        $groups = DuplicateGroup::query()
            ->where('entity_type', ContactMergeConfig::ENTITY_TYPE)
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('sales.contacts.duplicates.index', compact('groups'));
    }

    public function scan()
    {
        ScanDuplicateGroupsJob::dispatchSync(ContactMergeConfig::ENTITY_TYPE);

        return redirect()
            ->route('sales.contacts.duplicates.index')
            ->with('success', 'اسکن موارد تکراری انجام شد.');
    }

    public function review(DuplicateGroup $group)
    {
        if ($group->entity_type !== ContactMergeConfig::ENTITY_TYPE) {
            abort(404);
        }

        $contactIds = $group->items()->pluck('entity_id')->all();
        $fields = (new ContactMergeConfig())->mergeableFields();

        $contacts = Contact::withoutGlobalScopes()
            ->whereIn('id', $contactIds)
            ->withCount(['leads', 'opportunities', 'proformas'])
            ->get();

        $notesCounts = DB::table('notes')
            ->select('noteable_id', DB::raw('count(*) as total'))
            ->where('noteable_type', Contact::class)
            ->whereIn('noteable_id', $contactIds)
            ->groupBy('noteable_id')
            ->pluck('total', 'noteable_id')
            ->all();

        $relationsSummary = [];
        foreach ($contacts as $contact) {
            $relationsSummary[$contact->id] = [
                'leads' => $contact->leads_count ?? 0,
                'opportunities' => $contact->opportunities_count ?? 0,
                'proformas' => $contact->proformas_count ?? 0,
                'organizations' => $contact->organization_id ? 1 : 0,
                'notes' => $notesCounts[$contact->id] ?? 0,
            ];
        }

        $conflicts = $this->detectConflicts($contacts, $fields);

        $defaultWinnerId = $contacts->first()?->id;

        return view('sales.contacts.duplicates.review', compact(
            'group',
            'contacts',
            'fields',
            'relationsSummary',
            'conflicts',
            'defaultWinnerId'
        ));
    }

    public function merge(Request $request, MergeService $mergeService)
    {
        $group = DuplicateGroup::findOrFail($request->input('group_id'));
        if ($group->entity_type !== ContactMergeConfig::ENTITY_TYPE) {
            abort(404);
        }

        $contactIds = $group->items()->pluck('entity_id')->all();
        $winnerId = (int) $request->input('winner_id');
        $loserIds = array_map('intval', (array) $request->input('loser_ids', []));
        $loserIds = array_values(array_unique(array_diff($loserIds, [$winnerId])));

        if (!in_array($winnerId, $contactIds, true)) {
            return back()->withErrors(['winner_id' => 'Invalid winner selection.'])->withInput();
        }

        if (empty($loserIds)) {
            return back()->withErrors(['loser_ids' => 'Select at least one record to merge.'])->withInput();
        }

        foreach ($loserIds as $loserId) {
            if (!in_array($loserId, $contactIds, true)) {
                return back()->withErrors(['loser_ids' => 'Invalid loser selection.'])->withInput();
            }
        }

        $fieldResolution = (array) $request->input('field_resolution', []);
        foreach ($fieldResolution as $field => $selectedId) {
            $fieldResolution[$field] = (int) $selectedId;
            if (!in_array((int) $selectedId, $contactIds, true)) {
                return back()->withErrors(['field_resolution' => 'Invalid field resolution values.'])->withInput();
            }
        }

        $fields = (new ContactMergeConfig())->mergeableFields();
        $contacts = Contact::withoutGlobalScopes()
            ->whereIn('id', array_merge([$winnerId], $loserIds))
            ->get();
        $conflicts = $this->detectConflicts($contacts, $fields);

        foreach ($conflicts as $field => $values) {
            if (!Arr::get($fieldResolution, $field)) {
                return back()->withErrors([
                    'field_resolution' => "Field resolution required for {$field}.",
                ])->withInput();
            }
        }

        $mergeService->merge(
            new ContactMergeConfig(),
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
            ->route('sales.contacts.duplicates.index')
            ->with('success', 'Contacts merged successfully.');
    }

    private function detectConflicts($contacts, array $fields): array
    {
        $conflicts = [];

        foreach ($fields as $field) {
            $values = [];
            foreach ($contacts as $contact) {
                $value = $contact->{$field} ?? null;
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
