<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderWorkflowSetting;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\FormApprovalNotification;
use App\Notifications\PurchaseOrderReadyForDeliveryNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;
use Spatie\Activitylog\Models\Activity;

class PurchaseOrderController extends Controller
{
    private function userCanAct(PurchaseOrder $purchaseOrder, ?PurchaseOrderWorkflowSetting $settings = null): bool
    {
        $settings = $settings ?: PurchaseOrderWorkflowSetting::first();
        $status = (string) ($purchaseOrder->status ?? 'created');
        $currentUserId = (int) (auth()->id() ?? 0);

        $mainId = null;
        $subId  = null;
        if ($status === 'accounting_approval') {
            $mainId = optional($settings)->accounting_user_id;
            $subId  = optional($settings)->accounting_approver_substitute_id;
        } elseif ($status === 'manager_approval') {
            $mainId = optional($settings)->second_approver_id;
            $subId  = optional($settings)->second_approver_substitute_id;
        } else { // created or supervisor_approval
            $mainId = optional($settings)->first_approver_id;
            $subId  = optional($settings)->first_approver_substitute_id;
        }

        return ($currentUserId > 0)
            && ($currentUserId === (int) ($mainId ?? 0) || $currentUserId === (int) ($subId ?? 0));
    }

    private function effectiveApproverId(?int $mainId, ?int $subId): ?int
    {
        $currentId = (int) (auth()->id() ?? 0);
        if ($currentId && ($currentId === (int) ($mainId ?? 0) || $currentId === (int) ($subId ?? 0))) {
            return $currentId;
        }
        if (empty($mainId)) {
            return $subId ?: null;
        }
        try {
            $user = \App\Models\User::find($mainId);
            $onLeave = (bool) ($user->is_on_leave ?? false);
            if ($onLeave && ! empty($subId)) {
                return $subId;
            }
        } catch (\Throwable $e) {
        }
        return $mainId;
    }

    public function __construct()
    {
        $this->middleware('permission:purchase_orders.view.own|purchase_orders.view.team|purchase_orders.view.department|purchase_orders.view.company')
            ->only(['index', 'show', 'loadTab']);
        $this->middleware('permission:purchase_orders.create')
            ->only(['create', 'store']);
        $this->middleware('permission:purchase_orders.update.own|purchase_orders.update.team|purchase_orders.update.department')
            ->only(['edit', 'update', 'approve', 'reject', 'updateStatus', 'deliverToWarehouse']);
        $this->middleware('permission:purchase_orders.delete.own')
            ->only(['destroy']);
    }
    public function index(Request $request)
    {
        Log::debug('PurchaseOrder.index: filters', [
            'search' => $request->get('search'),
            'subject' => $request->get('subject'),
            'supplier' => $request->get('supplier'),
            'purchase_date' => $request->get('purchase_date'),
            'status' => $request->get('status'),
            'requested_by' => $request->get('requested_by'),
            'sort' => $request->get('sort', 'created_at'),
            'direction' => $request->get('direction', 'desc'),
        ]);

        $query = PurchaseOrder::query()
            ->select([
                'purchase_orders.*',
                'suppliers.name as supplier_name',
                'requested_by_user.name as requested_by_name',
            ])
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users as requested_by_user', 'purchase_orders.requested_by', '=', 'requested_by_user.id');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('purchase_orders.subject', 'like', "%{$search}%")
                    ->orWhere('suppliers.name', 'like', "%{$search}%")
                    ->orWhere('requested_by_user.name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('subject')) {
            $query->where('purchase_orders.subject', 'like', '%'.$request->subject.'%');
        }

        if ($request->filled('supplier')) {
            $query->where('suppliers.name', 'like', '%'.$request->supplier.'%');
        }

        if ($request->filled('purchase_date')) {
            $query->whereDate('purchase_orders.purchase_date', $request->purchase_date);
        }

        if ($request->filled('status')) {
            $query->where('purchase_orders.status', $request->status);
        }

        if ($request->filled('requested_by')) {
            $query->where('requested_by_user.name', 'like', '%'.$request->requested_by.'%');
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        if ($sortField === 'supplier') {
            $query->orderBy('suppliers.name', $sortDirection);
        } elseif ($sortField === 'requested_by') {
            $query->orderBy('requested_by_user.name', $sortDirection);
        } else {
            $query->orderBy("purchase_orders.{$sortField}", $sortDirection);
        }

        $purchaseOrders = $query->paginate(10)->withQueryString();
        return view('inventory.purchase-orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return view('inventory.purchase-orders.create', compact('suppliers', 'users'));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'requestedByUser', 'assignedUser', 'items', 'documents']);
        return view('inventory.purchase-orders.show', compact('purchaseOrder'));
    }
    public function loadTab(PurchaseOrder $purchaseOrder, string $tab)
    {
        $view = "inventory.purchase-orders.tabs.$tab";
        if (! view()->exists($view)) {
            abort(404);
        }
        $data = ['purchaseOrder' => $purchaseOrder];
        if ($tab === 'notes') {
            $data['allUsers'] = User::whereNotNull('username')->get();
        }
        if ($tab === 'updates') {
            $data['activities'] = Activity::where('subject_type', PurchaseOrder::class)
                ->where('subject_id', $purchaseOrder->id)
                ->latest()
                ->get();
        }
        if (in_array($tab, ['documents', 'info'], true)) {
            $purchaseOrder->loadMissing('documents');
        }
        if ($tab === 'items') {
            $purchaseOrder->loadMissing('items');
        }
        if ($tab === 'info') {
            $data['poSettings'] = PurchaseOrderWorkflowSetting::first();
        }
        return view($view, $data);
    }
    public function store(Request $request)
    {
        $requestId = (string) Str::uuid();
        Log::withContext([
            'request_id' => $requestId,
            'user_id' => optional($request->user())->id,
            'route' => $request->path(),
        ]);
        $t0 = microtime(true);

        Log::info('PurchaseOrder.store: شروع ثبت سفارش خرید', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'summary' => [
                'subject' => $request->input('subject'),
                'purchase_type' => $request->input('purchase_type'),
                'supplier_id' => $request->input('supplier_id'),
                'requested_by' => $request->input('requested_by'),
                'purchase_date' => $request->input('purchase_date'),
                'items_count' => is_array($request->input('items')) ? count($request->input('items')) : 0,
            ],
        ]);

        // --- Pre-normalize input: dates & numbers + items structure ---
        try {
            $input = $request->all();

            $normalizeDigits = static function ($v) {
                if ($v === null || $v === '') {
                    return $v;
                }
                $s = (string) $v;
                $mapFrom = [
                    '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹',
                    '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩',
                    '٬', '،', ',', '٫',
                ];
                $mapTo = [
                    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                    '', '', '', '.',
                ];
                $s = preg_replace('/\x{200C}|\x{200B}|\x{00A0}|\x{FEFF}/u', '', $s);
                return str_replace($mapFrom, $mapTo, $s);
            };

            $parseDate = static function ($raw) use ($normalizeDigits) {
                if ($raw === null || $raw === '') {
                    return null;
                }
                $raw = trim((string) $normalizeDigits($raw));
                $normalized = preg_replace('/\s+/', '', $raw) ?? '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
                    $year = (int) substr($normalized, 0, 4);
                    if ($year >= 1300 && $year <= 1599) {
                        return Jalalian::fromFormat('Y/m/d', str_replace('-', '/', $normalized))->toCarbon();
                    }
                    return Carbon::createFromFormat('Y-m-d', $normalized)->startOfDay();
                }
                $slash = str_replace('-', '/', $normalized);
                if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $slash)) {
                    return Jalalian::fromFormat('Y/m/d', $slash)->toCarbon();
                }
                try {
                    return Carbon::parse($normalized)->startOfDay();
                } catch (\Throwable $e) {
                    return null;
                }
            };

            foreach (['request_date', 'purchase_date', 'needed_by_date'] as $df) {
                if (array_key_exists($df, $input) && $input[$df] !== null && $input[$df] !== '') {
                    $dt = $parseDate($input[$df]);
                    if ($dt instanceof Carbon) {
                        $input[$df] = $dt;
                    }
                }
            }

            // Normalize potential parallel arrays into nested items[n][field]
            $parallelKeys = ['item_name', 'quantity', 'unit', 'unit_price'];
            $hasParallel = false;
            foreach ($parallelKeys as $k) {
                if (isset($input[$k]) && is_array($input[$k])) {
                    $hasParallel = true;
                    break;
                }
            }
            if ($hasParallel) {
                // Build nested items from parallel arrays, preserving original order by index
                $max = 0;
                foreach ($parallelKeys as $k) {
                    $cnt = is_array($input[$k] ?? null) ? count($input[$k]) : 0;
                    if ($cnt > $max) {
                        $max = $cnt;
                    }
                }
                $rebuiltPar = [];
                for ($i = 0; $i < $max; $i++) {
                    $row = [
                        'item_name' => (is_array($input['item_name'] ?? null) && array_key_exists($i, $input['item_name'])) ? $input['item_name'][$i] : '',
                        'quantity' => (is_array($input['quantity'] ?? null) && array_key_exists($i, $input['quantity'])) ? $input['quantity'][$i] : null,
                        'unit' => (is_array($input['unit'] ?? null) && array_key_exists($i, $input['unit'])) ? $input['unit'][$i] : null,
                        'unit_price' => (is_array($input['unit_price'] ?? null) && array_key_exists($i, $input['unit_price'])) ? $input['unit_price'][$i] : null,
                    ];
                    $rebuiltPar[] = $row;
                }
                $input['items'] = $rebuiltPar;
                // Remove parallel arrays to avoid confusion downstream
                foreach ($parallelKeys as $k) { unset($input[$k]); }
            }

            if (! empty($input['items']) && is_array($input['items'])) {
                $rebuilt = [];
                foreach ($input['items'] as $row) {
                    $row = is_array($row) ? $row : (array) $row;
                    if (isset($row['quantity'])) { $row['quantity'] = $normalizeDigits($row['quantity']); }
                    if (isset($row['unit_price'])) { $row['unit_price'] = $normalizeDigits($row['unit_price']); }
                    $rebuilt[] = [
                        'item_name' => $row['item_name'] ?? '',
                        'quantity' => $row['quantity'] ?? null,
                        'unit' => $row['unit'] ?? null,
                        'unit_price' => $row['unit_price'] ?? null,
                    ];
                }
                // Keep only rows with a non-empty item name
                $rebuilt = array_values(array_filter($rebuilt, function ($row) {
                    $name = trim((string) ($row['item_name'] ?? ''));
                    $allEmpty = $name === ''
                        && ($row['quantity'] === null || $row['quantity'] === '')
                        && ($row['unit'] === null || $row['unit'] === '')
                        && ($row['unit_price'] === null || $row['unit_price'] === '');
                    if ($allEmpty) { return false; }
                    return $name !== '';
                }));
                $input['items'] = $rebuilt;
            }

            if (array_key_exists('vat_percent', $input)) {
                $input['vat_percent'] = $normalizeDigits($input['vat_percent']);
            }

            $request->replace($input);
        } catch (\Throwable $e) {
            Log::warning('PurchaseOrder.store: pre-normalization failed', [ 'message' => $e->getMessage() ]);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'purchase_type' => 'required|in:official,unofficial',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'requested_by' => 'nullable|exists:users,id',
            'request_date' => 'nullable|date',
            'purchase_date' => 'required|date',
            'needed_by_date' => 'nullable|date',
            'status' => 'nullable|string|max:50',
            'settlement_type' => 'nullable|in:cash,credit,cheque',
            'usage_type' => 'nullable|in:inventory,project,both,operational_expense',
            'project_name' => 'nullable|required_if:usage_type,project,both|string|max:255',
            'operational_expense_type' => 'nullable|required_if:usage_type,operational_expense|in:commission,installation,shipping,workshop_running',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'previously_paid_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,jpg,png|max:10240',
        ]);

        $items = collect($validated['items']);
        $total = $items->reduce(function ($carry, $item) {
            $line = (float) $item['quantity'] * (float) $item['unit_price'];
            return $carry + $line;
        }, 0.0);

        $vatPercent = isset($validated['vat_percent']) ? (float) $validated['vat_percent'] : 0.0;
        $vatAmount = round($total * ($vatPercent / 100), 2);
        $grandTotal = $total + $vatAmount;
        $previouslyPaid = (float) ($validated['previously_paid_amount'] ?? 0);
        $remaining = max($grandTotal - $previouslyPaid, 0);

        Log::debug('PurchaseOrder.store: محاسبات مبلغ', [ 'total' => $total, 'previously_paid' => $previouslyPaid, 'remaining' => $remaining, 'items_sample' => $items->take(3)->values(), ]);

        try {
            DB::beginTransaction();
            $data = [
                'subject' => $validated['subject'],
                'purchase_type' => $validated['purchase_type'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'requested_by' => $validated['requested_by'] ?? auth()->id(),
                'request_date' => $validated['request_date'] ?? now(),
                'purchase_date' => $validated['purchase_date'],
                'needed_by_date' => $validated['needed_by_date'] ?? null,
                'status' => $validated['status'] ?? 'created',
                'vat_percent' => $validated['vat_percent'] ?? null,
                'vat_amount' => $vatAmount,
                'total_amount' => $total,
                'total_with_vat' => $grandTotal,
                'previously_paid_amount' => $previouslyPaid,
                'remaining_payable_amount' => $remaining,
                'assigned_to' => $validated['requested_by'] ?? auth()->id(),
                'description' => $validated['description'] ?? null,
            ];
            if (Schema::hasColumn('purchase_orders', 'settlement_type')) { $data['settlement_type'] = $validated['settlement_type'] ?? null; }
            if (Schema::hasColumn('purchase_orders', 'usage_type')) { $data['usage_type'] = $validated['usage_type'] ?? null; }
            if (Schema::hasColumn('purchase_orders', 'project_name')) { $data['project_name'] = $validated['project_name'] ?? null; }
            if (Schema::hasColumn('purchase_orders', 'operational_expense_type')) { $data['operational_expense_type'] = $validated['operational_expense_type'] ?? null; }
            $po = PurchaseOrder::create($data);

            // Assign first approver (if configured) and notify
            try {
                $wf = PurchaseOrderWorkflowSetting::first();
                $firstApproverId = $this->effectiveApproverId(optional($wf)->first_approver_id, optional($wf)->first_approver_substitute_id);
                if ($firstApproverId) {
                    $po->assigned_to = $firstApproverId;
                    $po->save();
                    $firstApprover = User::find($firstApproverId);
                    if ($firstApprover && method_exists($firstApprover, 'notify')) {
                        $firstApprover->notify(FormApprovalNotification::fromModel($po, auth()->id() ?? 0));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('PurchaseOrder.store: failed to notify first approver', [ 'error' => $e->getMessage() ]);
            }

            foreach ($items as $item) {
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $po->items()->create([
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);
            }

            if ($request->hasFile('attachments')) {
                foreach ((array) $request->file('attachments') as $file) {
                    if (! $file) { continue; }
                    $path = $file->store('documents', 'public');
                    $docData = [ 'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 'file_path' => $path ];
                    if (Schema::hasColumn('documents', 'purchase_order_id')) { $docData['purchase_order_id'] = $po->id; }
                    if (Schema::hasColumn('documents', 'user_id')) { $docData['user_id'] = optional($request->user())->id; }
                    \App\Models\Document::create($docData);
                }
            }

            DB::commit();
            Log::info('PurchaseOrder.store: ثبت با موفقیت انجام شد', [ 'purchase_order_id' => $po->id, 'supplier_id' => $po->supplier_id, 'items_count' => $items->count(), 'amounts' => [ 'total' => $total, 'previously_paid' => $previouslyPaid, 'remaining' => $remaining ], 'duration_ms' => round((microtime(true) - $t0) * 1000) ]);
            return redirect()->route('inventory.purchase-orders.index')->with('success', 'سفارش خرید با موفقیت ثبت شد.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PurchaseOrder.store: خطا در ثبت سفارش خرید', [ 'message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace_top' => collect(explode("\n", $e->getTraceAsString()))->take(5)->implode("\n"), 'duration_ms' => round((microtime(true) - $t0) * 1000) ]);
            return back()->withInput()->with('error', 'خطا در ثبت سفارش خرید. لطفاً مجدداً تلاش کنید.');
        }
    }
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'status' => ['required', 'in:created,supervisor_approval,manager_approval,accounting_approval,purchased,rejected,purchasing,warehouse_delivered'],
        ]);
        $newStatus = $data['status'];
        $currentStatus = (string) $purchaseOrder->status;
        $order = [
            'created' => 0,
            'supervisor_approval' => 1,
            'manager_approval' => 2,
            'accounting_approval' => 3,
            'purchased' => 4,
            'purchasing' => 5,
            'warehouse_delivered' => 6,
            'rejected' => 99,
        ];
        if (($order[$newStatus] ?? -1) < ($order[$currentStatus] ?? -1)) {
            return back()->with('error', 'امکان بازگشت به مرحله قبلی وجود ندارد.');
        }

        $settings = PurchaseOrderWorkflowSetting::first();
        $requiredUserId = null;
        if ($currentStatus === 'supervisor_approval') {
            $requiredUserId = $this->effectiveApproverId(optional($settings)->first_approver_id, optional($settings)->first_approver_substitute_id);
        } elseif ($currentStatus === 'manager_approval') {
            $requiredUserId = $this->effectiveApproverId(optional($settings)->second_approver_id, optional($settings)->second_approver_substitute_id);
        } elseif ($currentStatus === 'accounting_approval') {
            $requiredUserId = $this->effectiveApproverId(optional($settings)->accounting_user_id, optional($settings)->accounting_approver_substitute_id);
        }
        if ($requiredUserId && (int) auth()->id() !== (int) $requiredUserId) {
            return back()->with('error', 'تنها تاییدکننده این مرحله می‌تواند وضعیت را تغییر دهد.');
        }

        DB::transaction(function () use ($purchaseOrder, $newStatus, $settings, $currentStatus) {
            $purchaseOrder->status = $newStatus;
            $nextUserId = null;
            if ($settings) {
                if ($newStatus === 'supervisor_approval') {
                    $nextUserId = $this->effectiveApproverId(optional($settings)->first_approver_id, optional($settings)->first_approver_substitute_id);
                } elseif ($newStatus === 'manager_approval') {
                    $nextUserId = $this->effectiveApproverId(optional($settings)->second_approver_id, optional($settings)->second_approver_substitute_id);
                } elseif ($newStatus === 'accounting_approval') {
                    $nextUserId = $this->effectiveApproverId(optional($settings)->accounting_user_id, optional($settings)->accounting_approver_substitute_id);
                }
            }
            if ($nextUserId) { $purchaseOrder->assigned_to = $nextUserId; }
            $purchaseOrder->save();

            $mapStep = [ 'supervisor_approval' => 1, 'manager_approval' => 2, 'accounting_approval' => 3 ];
            $currentStep = $mapStep[$currentStatus] ?? null;
            if ($currentStep) {
                $approverId = match ($currentStatus) {
                    'supervisor_approval' => $this->effectiveApproverId(optional($settings)->first_approver_id, optional($settings)->first_approver_substitute_id),
                    'manager_approval' => $this->effectiveApproverId(optional($settings)->second_approver_id, optional($settings)->second_approver_substitute_id),
                    'accounting_approval' => $this->effectiveApproverId(optional($settings)->accounting_user_id, optional($settings)->accounting_approver_substitute_id),
                    default => null,
                };
                if ($approverId) {
                    \App\Models\Approval::updateOrCreate(
                        [
                            'approvable_type' => \App\Models\PurchaseOrder::class,
                            'approvable_id' => $purchaseOrder->id,
                            'user_id' => auth()->id() ?? $approverId,
                            'step' => $currentStep,
                        ],
                        ['status' => 'approved', 'approved_at' => now()]
                    );
                }
            }

            if ($nextUserId) {
                $user = User::find($nextUserId);
                if ($user && method_exists($user, 'notify')) {
                    try { $user->notify(FormApprovalNotification::fromModel($purchaseOrder, auth()->id() ?? 0)); }
                    catch (\Throwable $e) { Log::error('PurchaseOrder.updateStatus: failed to send notification', ['error' => $e->getMessage(), 'po_id' => $purchaseOrder->id, 'user_id' => $nextUserId]); }
                }
            }

            try {
                if (app()->bound(\App\Services\Notifications\NotificationRouter::class)) {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $context = [
                        'purchase_order' => $purchaseOrder,
                        'prev_status' => $currentStatus,
                        'new_status' => $newStatus,
                        'actor' => auth()->user(),
                        'url' => route('inventory.purchase-orders.show', $purchaseOrder->id),
                    ];
                    $recipients = [];
                    if (! empty($purchaseOrder->requested_by)) { $recipients[] = (int) $purchaseOrder->requested_by; }
                    if (! empty($purchaseOrder->assigned_to)) { $recipients[] = (int) $purchaseOrder->assigned_to; }
                    $router->route('purchase_orders', 'status.changed', $context, $recipients);
                }
            } catch (\Throwable $e) { Log::warning('PurchaseOrder.updateStatus: NotificationRouter failed', ['error' => $e->getMessage()]); }
        });

        return back()->with('success', 'وضعیت سفارش خرید به‌روزرسانی شد.');
    }
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $settings = PurchaseOrderWorkflowSetting::first();
        if (! $this->userCanAct($purchaseOrder, $settings)) {
            return back()->with('error', 'O_O3O�O�O3UO O"O�OUO O�O�UOUOO_ OUOU+ U.O�O-U,U� U+O_OO�UOO_.');
        }
        $stage = 'supervisor_approval';
        $step = 1;
        $expectedId = $this->effectiveApproverId(optional($settings)->first_approver_id, optional($settings)->first_approver_substitute_id);
        if ($purchaseOrder->status === 'manager_approval') {
            $stage = 'manager_approval';
            $step = 2;
            $expectedId = $this->effectiveApproverId(optional($settings)->second_approver_id, optional($settings)->second_approver_substitute_id);
        } elseif ($purchaseOrder->status === 'accounting_approval') {
            $stage = 'accounting_approval';
            $step = 3;
            $expectedId = $this->effectiveApproverId(optional($settings)->accounting_user_id, optional($settings)->accounting_approver_substitute_id);
        } elseif ($purchaseOrder->status === 'created' || $purchaseOrder->status === null) {
            $stage = 'supervisor_approval';
            $step = 1;
            $expectedId = $this->effectiveApproverId(optional($settings)->first_approver_id, optional($settings)->first_approver_substitute_id);
        }
        if (! $expectedId || (int) auth()->id() !== (int) $expectedId) {
            return back()->with('error', 'دسترسی برای تأیید این مرحله ندارید.');
        }
        DB::transaction(function () use ($purchaseOrder, $settings, $step, $expectedId) {
            \App\Models\Approval::updateOrCreate(
                [
                    'approvable_type' => \App\Models\PurchaseOrder::class,
                    'approvable_id' => $purchaseOrder->id,
                    'user_id' => auth()->id() ?? $expectedId,
                    'step' => $step,
                ],
                ['status' => 'approved', 'approved_at' => now()]
            );
            $nextAssignId = null;
            if ($step === 1) {
                if (optional($settings)->second_approver_id) {
                    $purchaseOrder->status = 'manager_approval';
                    $nextAssignId = $this->effectiveApproverId(optional($settings)->second_approver_id, optional($settings)->second_approver_substitute_id);
                } elseif (optional($settings)->accounting_user_id) {
                    $purchaseOrder->status = 'accounting_approval';
                    $nextAssignId = $this->effectiveApproverId(optional($settings)->accounting_user_id, optional($settings)->accounting_approver_substitute_id);
                } else {
                    $purchaseOrder->status = 'purchasing';
                    $nextAssignId = $purchaseOrder->requested_by;
                }
            } elseif ($step === 2) {
                if (optional($settings)->accounting_user_id) {
                    $purchaseOrder->status = 'accounting_approval';
                    $nextAssignId = $this->effectiveApproverId(optional($settings)->accounting_user_id, optional($settings)->accounting_approver_substitute_id);
                } else {
                    $purchaseOrder->status = 'purchasing';
                    $nextAssignId = $purchaseOrder->requested_by;
                }
            } else {
                $purchaseOrder->status = 'purchasing';
                $nextAssignId = $purchaseOrder->requested_by;
            }
            if ($nextAssignId) { $purchaseOrder->assigned_to = $nextAssignId; }
            $purchaseOrder->save();
            if ($nextAssignId) {
                $user = User::find($nextAssignId);
                if ($user && method_exists($user, 'notify')) {
                    try { $user->notify(FormApprovalNotification::fromModel($purchaseOrder, auth()->id() ?? 0)); }
                    catch (\Throwable $e) { Log::error('PurchaseOrder.approve: failed to notify next user', ['error' => $e->getMessage(), 'po_id' => $purchaseOrder->id, 'user_id' => $nextAssignId]); }
                }
            }

            // After step 3 approval, when PO advances to post-approval stage
            // send a one-time notification to requester to deliver to warehouse
            if ($step === 3
                && $purchaseOrder->status === 'purchasing'
                && empty($purchaseOrder->ready_for_delivery_notified_at)
                && ! empty($purchaseOrder->requested_by)) {
                try {
                    $requester = User::find((int) $purchaseOrder->requested_by);
                    if ($requester && method_exists($requester, 'notify')) {
                        $requester->notify(
                            PurchaseOrderReadyForDeliveryNotification::fromModel($purchaseOrder, (int) (auth()->id() ?? 0))
                        );
                        $purchaseOrder->ready_for_delivery_notified_at = now();
                        $purchaseOrder->save();
                    }
                } catch (\Throwable $e) {
                    Log::warning('PurchaseOrder.approve: ready-for-delivery notification failed', [
                        'error' => $e->getMessage(),
                        'po_id' => $purchaseOrder->id,
                    ]);
                }
            }
            try {
                if (app()->bound(\App\Services\Notifications\NotificationRouter::class)) {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $context = [
                        'purchase_order' => $purchaseOrder,
                        'prev_status' => 'approval_step_'.$step,
                        'new_status' => $purchaseOrder->status,
                        'actor' => auth()->user(),
                        'url' => route('inventory.purchase-orders.show', $purchaseOrder->id),
                    ];
                    $recipients = [];
                    if (! empty($purchaseOrder->requested_by)) { $recipients[] = (int) $purchaseOrder->requested_by; }
                    if (! empty($purchaseOrder->assigned_to)) { $recipients[] = (int) $purchaseOrder->assigned_to; }
                    $router->route('purchase_orders', 'status.changed', $context, $recipients);
                }
            } catch (\Throwable $e) { Log::warning('PurchaseOrder.approve: NotificationRouter failed', ['error' => $e->getMessage()]); }
        });
        return back()->with('success', 'سفارش در این مرحله تأیید شد.');
    }
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $settings = PurchaseOrderWorkflowSetting::first();
        if (! $this->userCanAct($purchaseOrder, $settings)) {
            return back()->with('error', 'O_O3O�O�O3UO O"O�OUO O�O_ OUOU+ U.O�O-U,U� U+O_OO�UOO_.');
        }
        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:2000'],
        ], [ 'reject_reason.required' => 'لطفا دلیل رد سفارش خرید را وارد کنید.', ]);
        $step = 1;
        $expectedId = $this->effectiveApproverId(optional($settings)->first_approver_id, optional($settings)->first_approver_substitute_id);
        if ($purchaseOrder->status === 'manager_approval') {
            $step = 2;
            $expectedId = $this->effectiveApproverId(optional($settings)->second_approver_id, optional($settings)->second_approver_substitute_id);
        } elseif ($purchaseOrder->status === 'accounting_approval') {
            $step = 3;
            $expectedId = $this->effectiveApproverId(optional($settings)->accounting_user_id, optional($settings)->accounting_approver_substitute_id);
        } elseif ($purchaseOrder->status === 'created' || $purchaseOrder->status === null) {
            $step = 1;
            $expectedId = $this->effectiveApproverId(optional($settings)->first_approver_id, optional($settings)->first_approver_substitute_id);
        }
        if (! $expectedId || (int) auth()->id() !== (int) $expectedId) {
            return back()->with('error', 'دسترسی برای رد این مرحله ندارید.');
        }
        DB::transaction(function () use ($purchaseOrder, $step, $expectedId, $validated) {
            try {
                $purchaseOrder->notes()->create([
                    'body' => "دلیل رد سفارش خرید:\n".trim((string) $validated['reject_reason']),
                    'user_id' => auth()->id(),
                ]);
            } catch (\Throwable $e) { Log::warning('PurchaseOrder.reject: failed to create note', ['error' => $e->getMessage(), 'po_id' => $purchaseOrder->id]); }
            $purchaseOrder->status = 'rejected';
            $purchaseOrder->assigned_to = $purchaseOrder->requested_by;
            $purchaseOrder->save();
            \App\Models\Approval::updateOrCreate(
                [ 'approvable_type' => \App\Models\PurchaseOrder::class, 'approvable_id' => $purchaseOrder->id, 'user_id' => auth()->id() ?? $expectedId, 'step' => $step ],
                ['status' => 'rejected', 'approved_at' => now()]
            );
            $creator = User::find($purchaseOrder->requested_by);
            if ($creator && method_exists($creator, 'notify')) {
                try { $creator->notify(FormApprovalNotification::fromModel($purchaseOrder, auth()->id() ?? 0)); }
                catch (\Throwable $e) { Log::error('PurchaseOrder.reject: failed to notify creator', ['error' => $e->getMessage(), 'po_id' => $purchaseOrder->id, 'user_id' => $purchaseOrder->requested_by]); }
            }
            try {
                if (app()->bound(\App\Services\Notifications\NotificationRouter::class)) {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $context = [
                        'purchase_order' => $purchaseOrder,
                        'prev_status' => 'approval_step_'.$step,
                        'new_status' => 'rejected',
                        'actor' => auth()->user(),
                        'url' => route('inventory.purchase-orders.show', $purchaseOrder->id),
                    ];
                    $recipients = [];
                    if (! empty($purchaseOrder->requested_by)) { $recipients[] = (int) $purchaseOrder->requested_by; }
                    if (! empty($purchaseOrder->assigned_to)) { $recipients[] = (int) $purchaseOrder->assigned_to; }
                    $router->route('purchase_orders', 'status.changed', $context, $recipients);
                }
            } catch (\Throwable $e) { Log::warning('PurchaseOrder.reject: NotificationRouter failed', ['error' => $e->getMessage()]); }
        });
        return back()->with('success', 'سفارش رد شد.');
    }

    public function deliverToWarehouse(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'purchasing') {
            return back()->with('error', 'تنها در وضعیت «در حال خرید» می‌توان تحویل به انباردار را ثبت کرد.');
        }
        if ((int) auth()->id() !== (int) $purchaseOrder->requested_by) {
            return back()->with('error', 'فقط ایجادکننده سفارش می‌تواند تحویل به انباردار را ثبت کند.');
        }
        $purchaseOrder->status = 'warehouse_delivered';
        $purchaseOrder->save();
        return back()->with('success', 'وضعیت به «تحویل انبار» تغییر یافت.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->authorize('delete', $purchaseOrder);

            DB::transaction(function () use ($purchaseOrder) {
                foreach (['notes', 'documents', 'items', 'approvals', 'activities'] as $relation) {
                    if (! method_exists($purchaseOrder, $relation)) {
                        continue;
                    }

                    try {
                        $purchaseOrder->{$relation}()->delete();
                    } catch (\Throwable $e) {
                        Log::warning('PurchaseOrder.destroy: relation delete failed', [
                            'relation' => $relation,
                            'purchase_order_id' => $purchaseOrder->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $purchaseOrder->delete();
            });

            return redirect()
                ->route('inventory.purchase-orders.index')
                ->with('success', 'سفارش خرید با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            Log::error('PurchaseOrder.destroy: failed to delete purchase order', [
                'purchase_order_id' => $purchaseOrder->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در حذف سفارش خرید. لطفاً دوباره تلاش کنید.');
        }
    }
}
