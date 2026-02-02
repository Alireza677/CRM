<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Models\Contact;
use App\Models\Organization;


class ProjectController extends Controller
{
    // لیست پروژه‌ها
    public function index(Request $request)
    {
        $projects = Project::query()
            ->with('manager')
            ->withCount([
                'members',
                'tasks',
                'tasks as tasks_done_count' => function ($query) {
                    $query->where('status', Task::STATUS_DONE);
                },
            ])
            ->latest('id')
            ->paginate(15);

        return view('projects.index', compact('projects'));
    }

    // فرم ایجاد پروژه
    public function create()
    {
        $users = User::query()->select('id','name','email')->orderBy('name')->get();
        return view('projects.create', compact('users'));
    }


    // ذخیره پروژه
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'manager_id'  => ['required','integer','exists:users,id'],
            'members'     => ['nullable','array'],
            'members.*'   => ['integer','exists:users,id'],
        ]);
    
        // ساخت پروژه
        $project = Project::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'manager_id'  => $validated['manager_id'],
        ]);
    
        // اطمینان: مدیر داخل اعضا هم باشد
        $memberIds = collect($validated['members'] ?? [])
                        ->push($validated['manager_id'])
                        ->unique()
                        ->values()
                        ->all();
    
        $project->members()->sync($memberIds);
    
        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'پروژه با موفقیت ایجاد شد.');
    }
    

    // نمایش پروژه + لیست تسک‌ها (فیلتر اختیاری بر اساس اولویت)
    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        // ...
        $priority = $request->query('priority');

        $tasksQuery = $project->tasks()->orderByRaw("FIELD(priority,'urgent','normal')")->latest('id');
        if ($priority && in_array($priority, ['normal','urgent'])) {
            $tasksQuery->where('priority', $priority);
        }
        $tasks = $tasksQuery->paginate(20)->withQueryString();

        // کاربران «عضو نیستند» برای فرم افزودن عضو (فقط برای مسئول)
        $nonMembers = User::select('id','name','email')
            ->whereNotIn('id', $project->members()->pluck('users.id'))
            ->orderBy('name')->get();

        // لیست اعضای پروژه برای ارجاع تسک
        $users = $project->members()->select('users.id','users.name','users.email')->orderBy('users.name')->get();

        $contacts = Contact::select('id', 'first_name', 'last_name', 'mobile')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $organizations = Organization::select('id', 'name', 'phone')->orderBy('name')->get();

        return view('projects.show', compact('project','tasks','priority','users','nonMembers','contacts','organizations'));
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'پروژه با موفقیت حذف شد.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:projects,id'],
        ]);

        $projects = Project::query()->whereIn('id', $validated['ids'])->get();
        if ($projects->isEmpty()) {
            return back()->with('error', 'هیچ پروژه‌ای برای حذف انتخاب نشده است.');
        }

        foreach ($projects as $project) {
            $this->authorize('delete', $project);
        }

        $deletedCount = Project::query()->whereIn('id', $projects->pluck('id'))->delete();

        return back()->with('success', $deletedCount . ' پروژه حذف شد.');
    }

    public function complete(Project $project)
    {
        $this->authorize('complete', $project);

        if ($project->status === Project::STATUS_COMPLETED) {
            return back()->with('success', 'این پروژه قبلاً تمام شده است.');
        }

        $project->update(['status' => Project::STATUS_COMPLETED]);

        return back()->with('success', 'وضعیت پروژه به تمام شده تغییر یافت.');
    }

    public function addMember(Request $request, Project $project)
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'user_id' => ['required','integer','exists:users,id',
                // کاربری که عضو نیست:
                Rule::notIn($project->members()->pluck('users.id')->toArray()),
            ],
        ]);

        $project->members()->attach($validated['user_id']);

        return back()->with('success', 'کاربر به اعضای پروژه اضافه شد.');
    }

    public function removeMember(Request $request, Project $project, User $member)
    {
        $this->authorize('manageMembers', $project);

        // مدیر پروژه را اجازه حذف نده (برای پایداری دسترسی)
        if ($member->id === $project->manager_id) {
            return back()->with('error', 'نمی‌توانید مسئول پروژه را حذف کنید.');
        }

        $project->members()->detach($member->id);

        return back()->with('success', 'کاربر از اعضای پروژه حذف شد.');
    }

}
