<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\FormOptionsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleReportController extends Controller
{
    /**
     * Show roles/permissions matrix and export actions.
     */
    public function index(Request $request)
    {
        [$modules, $actions, $scopes, $matrix, $roles] = $this->buildMatrix();

        return view('admin.role_report', [
            'modules' => $modules,
            'actions' => $actions,
            'scopes'  => $scopes,
            'matrix'  => $matrix,
            'roles'   => $roles,
        ]);
    }

    /**
     * Export CSV of the matrix.
     */
    public function exportCsv(Request $request)
    {
        [$modules, $actions, $scopes, $matrix, $roles] = $this->buildMatrix();
        $scopeLabels = FormOptionsHelper::permissionScopeLabels();
        $roleLabels = [
            'admin' => 'مدیر سیستم',
            'finance' => 'مالی',
            'admin_manager' => 'مدیر ادمین',
            'admin_staff' => 'کارمند ادمین',
            'factory_supervisor' => 'سرپرست کارخانه',
            'support' => 'پشتیبانی',
            'sales_manager' => 'مدیر فروش',
            'salesperson' => 'کارشناس فروش',
            'sales_agent' => 'نماینده فروش',
            'user' => 'کاربر',
        ];
        $actionLabels = [
            'view' => 'مشاهده',
            'create' => 'ایجاد',
            'update' => 'ویرایش',
            'delete' => 'حذف',
            'reassign' => 'اختصاص مجدد',
            'export' => 'خروجی',
            'manage' => 'مدیریت',
            'sales' => 'گزارش فروش',
            'finance' => 'گزارش مالی',
        ];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="role-permissions.csv"',
        ];

        $callback = function () use ($modules, $actions, $scopes, $matrix, $roles) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['نقش', 'ماژول', 'عملیات', 'حوزه‌ها']);

            foreach ($roles as $role) {
                foreach ($modules as $module) {
                    foreach ($actions as $action) {
                        $cell = $matrix[$role->name][$module][$action] ?? null;
                        if ($cell === null) {
                            continue; // no permission defined for this combination
                        }
                        if (is_array($cell)) {
                            $scopesStr = implode(',', array_map(fn($s) => $scopeLabels[$s] ?? $s, $cell));
                        } else {
                            $scopesStr = $cell ? 'بله' : '';
                        }
                        $roleName = $roleLabels[$role->name] ?? $role->name;
                        $actionName = $actionLabels[$action] ?? $action;
                        fputcsv($out, [$roleName, $module, $actionName, $scopesStr]);
                    }
                }
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Markdown of the matrix (per module table).
     */
    public function exportMarkdown(Request $request)
    {
        [$modules, $actions, $scopes, $matrix, $roles] = $this->buildMatrix();
        $scopeLabels = FormOptionsHelper::permissionScopeLabels();

        $md = [];
        $md[] = '# گزارش نقش‌ها و دسترسی‌ها';
        $md[] = '';

        foreach ($modules as $module) {
            $md[] = '## ' . $module;
            // header
            $head = array_merge(['Role'], $actions);
            $md[] = '| ' . implode(' | ', $head) . ' |';
            $md[] = '| ' . implode(' | ', array_fill(0, count($head), '---')) . ' |';
            foreach ($roles as $role) {
                $row = [$role->name];
                foreach ($actions as $action) {
                    $cell = $matrix[$role->name][$module][$action] ?? null;
                    if ($cell === null) {
                        $row[] = '—';
                    } elseif (is_array($cell)) {
                        if (empty($cell)) {
                            $row[] = '—';
                        } else {
                            $row[] = implode(', ', array_map(fn($s) => $scopeLabels[$s] ?? $s, $cell));
                        }
                    } else {
                        $row[] = $cell ? '✓' : '—';
                    }
                }
                $md[] = '| ' . implode(' | ', $row) . ' |';
            }
            $md[] = '';
        }

        $content = implode("\n", $md);
        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="role-permissions.md"',
        ]);
    }

    /**
     * Build roles/modules/actions/scopes matrix from DB permissions.
     *
     * @return array{0:array,1:array,2:array,3:array,4:\Illuminate\Support\Collection}
     */
    protected function buildMatrix(): array
    {
        $allPermissions = Permission::all()->pluck('name')->values();

        // Known actions and scopes as requested
        $actions = ['view', 'create', 'update', 'delete', 'reassign', 'export', 'manage', 'sales', 'finance'];
        $scopes  = FormOptionsHelper::permissionScopes();

        // Determine modules from permissions list
        $modules = $allPermissions
            ->map(function ($name) {
                $parts = explode('.', $name);
                return $parts[0] ?? null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Roles
        $roles = Role::with('permissions')->orderBy('name')->get();

        // Build matrix: [role][module][action] => array(scopes) | bool
        $matrix = [];
        foreach ($roles as $role) {
            $names = $role->permissions->pluck('name')->all();
            foreach ($modules as $module) {
                foreach ($actions as $action) {
                    // Scoped permissions
                    $foundScopes = [];
                    foreach ($scopes as $scope) {
                        $perm = $module . '.' . $action . '.' . $scope;
                        if (in_array($perm, $names, true)) {
                            $foundScopes[] = $scope;
                        }
                    }

                    if (!empty($foundScopes)) {
                        $matrix[$role->name][$module][$action] = $foundScopes;
                        continue;
                    }

                    // Non-scoped permission (create/reassign/export or generic view)
                    $plain = $module . '.' . $action;
                    if (in_array($plain, $names, true)) {
                        $matrix[$role->name][$module][$action] = true;
                    }
                }
            }
        }

        return [$modules, $actions, $scopes, $matrix, $roles];
    }
}
