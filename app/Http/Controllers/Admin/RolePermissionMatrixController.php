<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\FormOptionsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionMatrixController extends Controller
{
    /**
     * Show editable role permission matrix for a selected role.
     */
    public function index(Request $request)
    {
        $roles = Role::orderBy('name')->get();
        $roleId = (int) $request->input('role_id', $roles->first()?->id);
        $role = $roles->firstWhere('id', $roleId) ?? $roles->first();

        $dynamic = (bool) $request->boolean('dynamic');
        [$modules, $actions, $scopes, $definitions] = $this->buildDefinitions($dynamic);

        // Current assigned permissions for selected role
        $granted = $role
            ? $role->permissions->pluck('name')->values()->all()
            : [];

        // Build current values map for form: [module][action] => null|bool|string(scope)
        $current = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $def = $definitions[$module][$action] ?? null;
                if (!$def) {
                    continue;
                }

                if ($def['type'] === 'scoped') {
                    // Choose the highest scope present among granted
                    $sel = null;
                    foreach (array_reverse($scopes) as $scope) { // highest precedence is the last item in the scopes list
                        $p = $module . '.' . $action . '.' . $scope;
                        if (in_array($p, $granted, true)) {
                            $sel = $scope;
                            break;
                        }
                    }
                    $current[$module][$action] = $sel; // null means none
                } elseif ($def['type'] === 'boolean') {
                    $p = $module . '.' . $action;
                    $current[$module][$action] = in_array($p, $granted, true);
                }
            }
        }

        // Simple version/etag based on current assigned permission names
        $version = $this->makeVersion($role?->permissions->pluck('name')->values()->all() ?? []);

        return view('admin.role_permission_matrix', [
            'roles'       => $roles,
            'role'        => $role,
            'modules'     => $modules,
            'actions'     => $actions,
            'scopes'      => $scopes,
            'definitions' => $definitions,
            'current'     => $current,
            'version'     => $version,
            'dynamic'     => $dynamic,
        ]);
    }

    /**
     * Store updates from matrix for selected role.
     */
    public function store(Request $request)
    {
        $dynamic = (bool) $request->boolean('dynamic');
        [$modules, $actions, $scopes, $definitions] = $this->buildDefinitions($dynamic);

        $request->validate([
            'role_id' => ['required', Rule::exists(config('permission.table_names.roles', 'roles'), 'id')],
            'perm'    => ['array'],
            'version' => ['nullable','string'],
        ]);

        /** @var Role $role */
        $role = Role::findOrFail((int) $request->input('role_id'));

        $input = $request->input('perm', []);

        // Concurrency guard
        $submittedVersion = (string) $request->input('version', '');
        $currentVersion = $this->makeVersion($role->permissions->pluck('name')->values()->all());
        if ($submittedVersion !== '' && $submittedVersion !== $currentVersion) {
            return redirect()
                ->to(route('roles.matrix', ['role_id' => $role->id, 'dynamic' => $dynamic ? 1 : 0]))
                ->withErrors(['version' => 'اطلاعات تغییر کرده—لطفاً صفحه را به‌روزرسانی کنید']);
        }

        // Compute the set of permission names managed by this matrix
        $managed = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $def = $definitions[$module][$action] ?? null;
                if (!$def) continue;
                if ($def['type'] === 'scoped') {
                    foreach ($def['options'] as $scope) {
                        $managed[] = $module . '.' . $action . '.' . $scope;
                    }
                } else { // boolean
                    $managed[] = $module . '.' . $action;
                }
            }
        }

        $managed = array_values(array_unique($managed));

        // Keep permissions outside the managed set as-is
        $existing = $role->permissions->pluck('name')->all();
        $kept = array_values(array_diff($existing, $managed));

        // Build selected list from input
        $selected = [];
        foreach ($input as $key => $value) { // $key = "module.action"
            [$module, $action] = array_pad(explode('.', (string) $key, 2), 2, null);
            if (!$module || !$action) continue;

            $def = $definitions[$module][$action] ?? null;
            if (!$def) continue; // ignore unknown

            if ($def['type'] === 'scoped') {
                $val = trim((string) $value);
                if ($val === '' || $val === '-') {
                    continue; // no selection means remove all scoped perms for this action
                }
                if (!in_array($val, $def['options'], true)) {
                    continue; // invalid option ignored silently
                }
                $selected[] = $module . '.' . $action . '.' . $val;
            } else {
                // Checkbox sends 'on' if checked; missing if unchecked
                if ($value) {
                    $selected[] = $module . '.' . $action;
                }
            }
        }

        $target = array_values(array_unique(array_merge($kept, $selected)));

        // Map names to Permission models that exist
        // Audit: before/after
        $before = $role->permissions->pluck('name')->values()->all();

        $permModels = Permission::whereIn('name', $target)->get();
        $role->syncPermissions($permModels);

        // Clear Spatie cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Audit log
        try {
            activity('role-permissions')
                ->causedBy($request->user())
                ->performedOn($role)
                ->withProperties([
                    'role_id' => $role->id,
                    'before' => $before,
                    'after' => $role->permissions()->pluck('name')->values()->all(),
                    'action' => 'matrix.store',
                ])->log('Role permissions updated via matrix');
        } catch (\Throwable $e) {
            \Log::info('role-permissions update', [
                'role_id' => $role->id,
                'user_id' => optional($request->user())->id,
                'before' => $before,
                'after' => $role->permissions()->pluck('name')->values()->all(),
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->to(route('roles.matrix', ['role_id' => $role->id, 'dynamic' => $dynamic ? 1 : 0]))
            ->with('status', 'دسترسی‌های نقش ' . $role->name . ' در ' . now()->format('Y-m-d H:i') . ' ذخیره شد.');
    }

    /**
     * Build module/action definitions and available options from existing permissions.
     *
     * @return array{0:array,1:array,2:array,3:array}
     */
    protected function buildDefinitions(bool $dynamic = false): array
    {
        $all = Permission::orderBy('name')->get()->pluck('name')->all();

        // Standard actions & scopes (columns)
        $defaultActions = ['view', 'create', 'update', 'delete', 'reassign', 'export', 'download', 'manage', 'sales', 'finance'];
        $scopes  = FormOptionsHelper::permissionScopes();
        $scopedColumns = ['view', 'create', 'update', 'delete'];

        // Determine modules from permissions
        $modules = collect($all)
            ->map(fn($n) => explode('.', $n)[0] ?? null)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Actions
        $actions = $defaultActions;
        if ($dynamic) {
            $actions = collect($all)
                ->map(function ($name) {
                    $parts = explode('.', $name);
                    return $parts[1] ?? null;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        // Definition per module/action: type=boolean|scoped, options for scoped
        $definitions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $hasPlain = in_array("$module.$action", $all, true);
                // Only expose scopes that actually exist in permissions table
                $availScopes = array_values(array_filter($scopes, fn($s) => in_array("$module.$action.$s", $all, true)));

                if (!empty($availScopes)) {
                    $definitions[$module][$action] = [
                        'type' => 'scoped',
                        'options' => $availScopes,
                    ];
                } elseif ($hasPlain) {
                    $definitions[$module][$action] = [
                        'type' => 'boolean',
                    ];
                }
            }
        }

        return [$modules, $actions, $scopes, $definitions];
    }

    /** Create a simple version string for concurrency based on granted names */
    protected function makeVersion(array $names): string
    {
        sort($names);
        return sha1(implode('|', $names));
    }

    /** Export role permissions as JSON (only existing names) */
    public function exportJson(Request $request)
    {
        $request->validate([
            'role_id' => ['required', Rule::exists(config('permission.table_names.roles', 'roles'), 'id')],
        ]);
        $role = Role::findOrFail((int) $request->input('role_id'));
        $perms = $role->permissions()->pluck('name')->values()->all();
        return response()->json([
            'role' => ['id' => $role->id, 'name' => $role->name],
            'permissions' => $perms,
            'version' => $this->makeVersion($perms),
        ]);
    }

    /** Import role permissions from JSON array of names */
    public function importJson(Request $request)
    {
        $data = $request->validate([
            'role_id' => ['required', Rule::exists(config('permission.table_names.roles', 'roles'), 'id')],
            'permissions' => ['required','array'],
            'permissions.*' => ['string'],
            'version' => ['nullable','string'],
        ]);

        $role = Role::findOrFail((int) $data['role_id']);

        // Concurrency guard (optional)
        $submitted = (string) ($data['version'] ?? '');
        $current = $this->makeVersion($role->permissions()->pluck('name')->values()->all());
        if ($submitted !== '' && $submitted !== $current) {
            return response()->json(['message' => 'اطلاعات تغییر کرده—لطفاً صفحه را به‌روزرسانی کنید'], 409);
        }

        // Validate names exist
        $existing = Permission::whereIn('name', $data['permissions'])->pluck('name')->values()->all();

        // Audit before/after
        $before = $role->permissions()->pluck('name')->values()->all();

        $models = Permission::whereIn('name', $existing)->get();
        $role->syncPermissions($models);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            activity('role-permissions')
                ->causedBy($request->user())
                ->performedOn($role)
                ->withProperties([
                    'role_id' => $role->id,
                    'before' => $before,
                    'after' => $role->permissions()->pluck('name')->values()->all(),
                    'action' => 'matrix.importJson',
                ])->log('Role permissions imported via JSON');
        } catch (\Throwable $e) {
            \Log::info('role-permissions import', [
                'role_id' => $role->id,
                'user_id' => optional($request->user())->id,
                'before' => $before,
                'after' => $role->permissions()->pluck('name')->values()->all(),
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'ذخیره شد',
            'role' => ['id' => $role->id, 'name' => $role->name],
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ]);
    }
}
