<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use App\Models\PurchaseOrderWorkflowSetting;
use App\Notifications\FormApprovalNotification;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        // View access: any of the scoped view permissions
        $this->middleware('permission:purchase_orders.view.own|purchase_orders.view.team|purchase_orders.view.department|purchase_orders.view.company')
            ->only(['index', 'show', 'loadTab']);

        // Create access
        $this->middleware('permission:purchase_orders.create')
            ->only(['create', 'store']);

        // Update-related access (scoped)
        $this->middleware('permission:purchase_orders.update.own|purchase_orders.update.team|purchase_orders.update.department')
            ->only(['edit', 'update', 'approve', 'reject', 'updateStatus', 'deliverToWarehouse']);

        // Delete access (own)
        $this->middleware('permission:purchase_orders.delete.own')
            ->only(['destroy']);
    }
    public function index(Request $request)
    {
        // (اختیاری) برای ردیابی فیلترها:
        Log::debug('PurchaseOrder.index: filters', [
            'search'        => $request->get('search'),
            'subject'       => $request->get('subject'),
            'supplier'      => $request->get('supplier'),
            'purchase_date' => $request->get('purchase_date'),
            'status'        => $request->get('status'),
            'requested_by'  => $request->get('requested_by'),
            'sort'          => $request->get('sort', 'created_at'),
            'direction'     => $request->get('direction', 'desc'),
        ]);

        $query = PurchaseOrder::query()
            ->select([
                'purchase_orders.*',
                'suppliers.name as supplier_name',
                'requested_by_user.name as requested_by_name'
            ])
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users as requested_by_user', 'purchase_orders.requested_by', '=', 'requested_by_user.id');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_orders.subject', 'like', "%{$search}%")
                    ->orWhere('suppliers.name', 'like', "%{$search}%");
            });
        }

        if ($request->has('subject')) {
            $query->where('purchase_orders.subject', 'like', "%{$request->subject}%");
        }
        if ($request->has('supplier')) {
            $query->where('suppliers.name', 'like', "%{$request->supplier}%");
        }
        if ($request->has('purchase_date')) {
            $query->whereDate('purchase_orders.purchase_date', $request->purchase_date);
        }
        if ($request->has('status')) {
            $query->where('purchase_orders.status', $request->status);
        }
        if ($request->has('requested_by')) {
            $query->where('requested_by_user.name', 'like', "%{$request->requested_by}%");
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

    /**
     * Load a tab partial for the purchase order show page (AJAX).
     */
    public function loadTab(PurchaseOrder $purchaseOrder, string $tab)
    {
        $view = "inventory.purchase-orders.tabs.$tab";
        if (!view()->exists($view)) {
            abort(404);
        }

        $data = ['purchaseOrder' => $purchaseOrder];

        if ($tab === 'notes') {
            $data['allUsers'] = \App\Models\User::whereNotNull('username')->get();
        }

        if ($tab === 'updates') {
            $data['activities'] = \Spatie\Activitylog\Models\Activity::where('subject_type', \App\Models\PurchaseOrder::class)
                ->where('subject_id', $purchaseOrder->id)
                ->latest()
                ->get();
        }

        if (in_array($tab, ['documents','info'], true)) {
            $purchaseOrder->loadMissing('documents');
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
            'user_id'    => optional($request->user())->id,
            'route'      => $request->path(),
        ]);

        $t0 = microtime(true);

        // ورودی‌ها را خلاصه/ماسک می‌کنیم تا لاگ سبک و امن باشد
        Log::info('PurchaseOrder.store: شروع ثبت سفارش خرید', [
            'url'     => $request->fullUrl(),
            'method'  => $request->method(),
            'summary' => [
                'subject'          => $request->input('subject'),
                'purchase_type'    => $request->input('purchase_type'),
                'supplier_id'      => $request->input('supplier_id'),
                'requested_by'     => $request->input('requested_by'),
                'purchase_date'    => $request->input('purchase_date'),
                'items_count'      => is_array($request->input('items')) ? count($request->input('items')) : 0,
                // مبلغ‌های دقیق را در debug لاگ می‌کنیم
            ],
        ]);

        // Pre-normalize input: dates (Jalali -> Gregorian) and items array structure
        try {
            $input = $request->all();

            // Helper: normalize Persian/Arabic digits and separators to ASCII
            $normalizeDigits = static function ($v) {
                if ($v === null || $v === '') return $v;
                $s = (string) $v;
                $mapFrom = [
                    '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹', // Persian digits
                    '٠','١','٢','٣','٤','٥','٦','٧','٨','٩', // Arabic-Indic digits
                    '٬','،',',','٫' // separators
                ];
                $mapTo   = [
                    '0','1','2','3','4','5','6','7','8','9',
                    '0','1','2','3','4','5','6','7','8','9',
                    '','','','.'
                ];
                // Remove zero-width / non-breaking spaces
                $s = preg_replace('/\x{200C}|\x{200B}|\x{00A0}|\x{FEFF}/u', '', $s);
                return str_replace($mapFrom, $mapTo, $s);
            };

            // Helper: parse a possible Jalali or Gregorian date string to Carbon
            $parseDate = static function ($raw) use ($normalizeDigits) {
                if ($raw === null || $raw === '') return null;
                $raw = $normalizeDigits($raw);
                $raw = trim((string) $raw);
                $normalized = preg_replace('/\s+/', '', $raw) ?? '';

                // If format YYYY-MM-DD decide by year range; if 1300..1599 assume Jalali
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
                    $year = (int) substr($normalized, 0, 4);
                    if ($year >= 1300 && $year <= 1599) {
                        return Jalalian::fromFormat('Y/m/d', str_replace('-', '/', $normalized))->toCarbon();
                    }
                    return Carbon::createFromFormat('Y-m-d', $normalized)->startOfDay();
                }

                // If format YYYY/MM/DD treat as Jalali
                $slash = str_replace('-', '/', $normalized);
                if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $slash)) {
                    return Jalalian::fromFormat('Y/m/d', $slash)->toCarbon();
                }

                // Fallback: try Carbon parse (Gregorian)
                try {
                    return Carbon::parse($normalized)->startOfDay();
                } catch (\Throwable $e) {
                    return null;
                }
            };

            // Normalize expected date fields
            foreach (['request_date', 'purchase_date', 'needed_by_date'] as $df) {
                if (array_key_exists($df, $input) && $input[$df] !== null && $input[$df] !== '') {
                    $dt = $parseDate($input[$df]);
                    if ($dt instanceof Carbon) {
                        $input[$df] = $dt; // Let validator accept as date
                    }
                }
            }

            // Rebuild items: ensure each element has all fields together
            if (!empty($input['items']) && is_array($input['items'])) {
                $rebuilt = [];
                $current = [];
                $expectedKeys = ['item_name', 'quantity', 'unit', 'unit_price'];

                foreach ($input['items'] as $idx => $row) {
                    // Cast to array and normalize digits for numeric fields
                    $row = is_array($row) ? $row : (array) $row;

                    // If the row already looks complete, normalize and push
                    $rowHasExpected = array_intersect(array_keys($row), $expectedKeys);
                    if (count($rowHasExpected) >= 2 || (isset($row['item_name']) && isset($row['unit_price'])) ) {
                        if (isset($row['quantity']))   { $row['quantity']   = $normalizeDigits($row['quantity']); }
                        if (isset($row['unit_price'])) { $row['unit_price'] = $normalizeDigits($row['unit_price']); }
                        $rebuilt[] = [
                            'item_name'  => $row['item_name']  ?? '',
                            'quantity'   => $row['quantity']   ?? null,
                            'unit'       => $row['unit']       ?? null,
                            'unit_price' => $row['unit_price'] ?? null,
                        ];
                        $current = [];
                        continue;
                    }

                    // Merge single-field objects sequentially into $current
                    foreach ($expectedKeys as $k) {
                        if (array_key_exists($k, $row)) {
                            $val = $row[$k];
                            if (in_array($k, ['quantity','unit_price'], true)) {
                                $val = $normalizeDigits($val);
                            }
                            $current[$k] = $val;
                        }
                    }

                    if (count(array_intersect(array_keys($current), $expectedKeys)) === count($expectedKeys)) {
                        $rebuilt[] = [
                            'item_name'  => $current['item_name']  ?? '',
                            'quantity'   => $current['quantity']   ?? null,
                            'unit'       => $current['unit']       ?? null,
                            'unit_price' => $current['unit_price'] ?? null,
                        ];
                        $current = [];
                    }
                }

                // Flush any remaining partial item if it has at least a name
                if (!empty($current)) {
                    $rebuilt[] = [
                        'item_name'  => $current['item_name']  ?? '',
                        'quantity'   => $current['quantity']   ?? null,
                        'unit'       => $current['unit']       ?? null,
                        'unit_price' => $current['unit_price'] ?? null,
                    ];
                }

                // Filter out empty rows (no name and no numbers)
                $rebuilt = array_values(array_filter($rebuilt, function ($row) {
                    $name = trim((string)($row['item_name'] ?? ''));
                    $qty  = (float)($row['quantity'] ?? 0);
                    $price= (float)($row['unit_price'] ?? 0);
                    return $name !== '' || $qty > 0 || $price > 0;
                }));

                $input['items'] = $rebuilt;
            }

            // Normalize VAT percent if present
            if (array_key_exists('vat_percent', $input)) {
                $input['vat_percent'] = $normalizeDigits($input['vat_percent']);
            }

            // Replace request input with normalized data before validation
            $request->replace($input);
        } catch (\Throwable $e) {
            Log::warning('PurchaseOrder.store: pre-normalization failed', [
                'message' => $e->getMessage(),
            ]);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'purchase_type' => 'required|in:official,unofficial',
            'supplier_id' => 'required|exists:suppliers,id',
            'requested_by' => 'nullable|exists:users,id',
            'request_date' => 'nullable|date',
            'purchase_date' => 'required|date',
            'needed_by_date' => 'nullable|date',
            'status' => 'nullable|string|max:50',
            'settlement_type' => 'nullable|in:cash,credit,cheque',
            'usage_type' => 'nullable|in:inventory,project,both',
            'project_name' => 'nullable|required_if:usage_type,project,both|string|max:255',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'previously_paid_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,jpg,png|max:10240',
        ]);

        // محاسبات را با دقت بیشتر و در سطح debug لاگ می‌کنیم
        $items = collect($validated['items']);
        $total = $items->reduce(function ($carry, $item) {
            $line = (float)$item['quantity'] * (float)$item['unit_price'];
            return $carry + $line;
        }, 0.0);
        $vatPercent = isset($validated['vat_percent']) ? (float)$validated['vat_percent'] : 0.0;
        $vatAmount  = round($total * ($vatPercent / 100), 2);
        $grandTotal = $total + $vatAmount;

        $previouslyPaid = (float)($validated['previously_paid_amount'] ?? 0);
        $remaining = max($grandTotal - $previouslyPaid, 0);

        Log::debug('PurchaseOrder.store: محاسبات مبلغ', [
            'total'            => $total,
            'previously_paid'  => $previouslyPaid,
            'remaining'        => $remaining,
            'items_sample'     => $items->take(3)->values(), // فقط چند قلم نمونه
        ]);

        try {
            DB::beginTransaction();

            $data = [
                'subject' => $validated['subject'],
                'purchase_type' => $validated['purchase_type'],
                'supplier_id' => $validated['supplier_id'],
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

            // Optional columns if exist in schema
            if (\Illuminate\Support\Facades\Schema::hasColumn('purchase_orders', 'settlement_type')) {
                $data['settlement_type'] = $validated['settlement_type'] ?? null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('purchase_orders', 'usage_type')) {
                $data['usage_type'] = $validated['usage_type'] ?? null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('purchase_orders', 'project_name')) {
                $data['project_name'] = $validated['project_name'] ?? null;
            }

            $po = PurchaseOrder::create($data);

            // After creation: assign to first approver (if any) and notify them
            try {
                $wf = PurchaseOrderWorkflowSetting::first();
                $firstApproverId = optional($wf)->first_approver_id;
                if ($firstApproverId) {
                    $po->assigned_to = $firstApproverId;
                    $po->save();

                    $firstApprover = \App\Models\User::find($firstApproverId);
                    if ($firstApprover && method_exists($firstApprover, 'notify')) {
                        $firstApprover->notify(FormApprovalNotification::fromModel($po, auth()->id() ?? 0));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('PurchaseOrder.store: failed to notify first approver', [
                    'error' => $e->getMessage(),
                ]);
            }

            foreach ($items as $item) {
                $lineTotal = (float)$item['quantity'] * (float)$item['unit_price'];
                $po->items()->create([
                    'item_name'  => $item['item_name'],
                    'quantity'   => $item['quantity'],
                    'unit'       => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);
            }

            // Save uploaded attachments as documents linked to the PO
            if ($request->hasFile('attachments')) {
                foreach ((array) $request->file('attachments') as $file) {
                    if (!$file) continue;
                    $path = $file->store('documents', 'public');

                    $data = [
                        'title'     => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'file_path' => $path,
                    ];

                    // Set optional columns only if they exist
                    if (\Illuminate\Support\Facades\Schema::hasColumn('documents', 'purchase_order_id')) {
                        $data['purchase_order_id'] = $po->id;
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('documents', 'user_id')) {
                        $data['user_id'] = optional($request->user())->id;
                    }

                    \App\Models\Document::create($data);
                }
            }

            DB::commit();

            Log::info('PurchaseOrder.store: ثبت با موفقیت انجام شد', [
                'purchase_order_id' => $po->id,
                'supplier_id'       => $po->supplier_id,
                'items_count'       => $items->count(),
                'amounts'           => [
                    'total'           => $total,
                    'previously_paid' => $previouslyPaid,
                    'remaining'       => $remaining,
                ],
                'duration_ms'       => round((microtime(true) - $t0) * 1000),
            ]);

            return redirect()
                ->route('inventory.purchase-orders.index')
                ->with('success', 'سفارش خرید با موفقیت ثبت شد.');

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('PurchaseOrder.store: خطا در ثبت سفارش خرید', [
                'message'      => $e->getMessage(),
                'code'         => $e->getCode(),
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
                'trace_top'    => collect(explode("\n", $e->getTraceAsString()))->take(5)->implode("\n"),
                'duration_ms'  => round((microtime(true) - $t0) * 1000),
            ]);

            return back()
                ->withInput()
                ->with('error', 'خطا در ثبت سفارش خرید. لطفاً مجدداً تلاش کنید.');
        }
    }

    /**
     * Update status and auto-assign next approver based on workflow settings.
     */
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'status' => ['required','in:created,supervisor_approval,manager_approval,accounting_approval,purchased,rejected,purchasing,warehouse_delivered'],
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

        // Prevent moving backwards
        if (($order[$newStatus] ?? -1) < ($order[$currentStatus] ?? -1)) {
            return back()->with('error', 'امکان بازگشت به مرحله قبلی وجود ندارد.');
        }

        // If currently awaiting an approver, restrict who can change status
        $settings = PurchaseOrderWorkflowSetting::first();
        $requiredUserId = null;
        if ($currentStatus === 'supervisor_approval') {
            $requiredUserId = optional($settings)->first_approver_id;
        } elseif ($currentStatus === 'manager_approval') {
            $requiredUserId = optional($settings)->second_approver_id;
        } elseif ($currentStatus === 'accounting_approval') {
            $requiredUserId = optional($settings)->accounting_user_id;
        }

        if ($requiredUserId && (int) auth()->id() !== (int) $requiredUserId) {
            return back()->with('error', 'تنها تاییدکننده این مرحله می‌تواند وضعیت را تغییر دهد.');
        }

        DB::transaction(function () use ($purchaseOrder, $newStatus, $settings, $currentStatus) {
            $purchaseOrder->status = $newStatus;

            // Determine next responsible user from workflow settings
            $nextUserId = null;
            if ($settings) {
                if ($newStatus === 'supervisor_approval') {
                    $nextUserId = $settings->first_approver_id;
                } elseif ($newStatus === 'manager_approval') {
                    $nextUserId = $settings->second_approver_id;
                } elseif ($newStatus === 'accounting_approval') {
                    $nextUserId = $settings->accounting_user_id;
                }
            }

            if ($nextUserId) {
                $purchaseOrder->assigned_to = $nextUserId;
            }

            $purchaseOrder->save();

            // Record approval step when moving forward from an approval stage
            $mapStep = [
                'supervisor_approval'  => 1,
                'manager_approval'     => 2,
                'accounting_approval'  => 3,
            ];
            $currentStep = $mapStep[$currentStatus] ?? null;
            if ($currentStep) {
                $approverId = match ($currentStatus) {
                    'supervisor_approval' => optional($settings)->first_approver_id,
                    'manager_approval' => optional($settings)->second_approver_id,
                    'accounting_approval' => optional($settings)->accounting_user_id,
                    default => null,
                };
                if ($approverId) {
                    $purchaseOrder->approvals()->updateOrCreate(
                        ['user_id' => $approverId, 'step' => $currentStep],
                        ['status' => 'approved', 'approved_at' => now()]
                    );
                }
            }

            if ($nextUserId) {
                $user = \App\Models\User::find($nextUserId);
                if ($user && method_exists($user, 'notify')) {
                    try {
                        $user->notify(FormApprovalNotification::fromModel($purchaseOrder, auth()->id() ?? 0));
                    } catch (\Throwable $e) {
                        Log::error('PurchaseOrder.updateStatus: failed to send notification', [
                            'error' => $e->getMessage(),
                            'po_id' => $purchaseOrder->id,
                            'user_id' => $nextUserId,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'وضعیت سفارش خرید به‌روزرسانی شد.');
    }

    /**
     * Approve current step in the workflow and advance to the next approver.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $settings = PurchaseOrderWorkflowSetting::first();

        // Determine current stage and expected approver
        $stage = 'supervisor_approval';
        $step = 1;
        $expectedId = optional($settings)->first_approver_id;

        if ($purchaseOrder->status === 'manager_approval') {
            $stage = 'manager_approval';
            $step = 2;
            $expectedId = optional($settings)->second_approver_id;
        } elseif ($purchaseOrder->status === 'accounting_approval') {
            $stage = 'accounting_approval';
            $step = 3;
            $expectedId = optional($settings)->accounting_user_id;
        } elseif ($purchaseOrder->status === 'created' || $purchaseOrder->status === null) {
            // Freshly created: first approver acts
            $stage = 'supervisor_approval';
            $step = 1;
            $expectedId = optional($settings)->first_approver_id;
        }

        if (!$expectedId || (int) auth()->id() !== (int) $expectedId) {
            return back()->with('error', 'دسترسی برای تأیید این مرحله ندارید.');
        }

        DB::transaction(function () use ($purchaseOrder, $settings, $step, $expectedId) {
            // Record approval for this step
            $purchaseOrder->approvals()->updateOrCreate(
                ['user_id' => $expectedId, 'step' => $step],
                ['status' => 'approved', 'approved_at' => now()]
            );

            // Decide next stage
            $nextAssignId = null;
            $notifyUser = null;

            if ($step === 1) {
                if (optional($settings)->second_approver_id) {
                    $purchaseOrder->status = 'manager_approval';
                    $nextAssignId = $settings->second_approver_id;
                } elseif (optional($settings)->accounting_user_id) {
                    $purchaseOrder->status = 'accounting_approval';
                    $nextAssignId = $settings->accounting_user_id;
                } else {
                    $purchaseOrder->status = 'purchasing';
                    $nextAssignId = $purchaseOrder->requested_by;
                }
            } elseif ($step === 2) {
                if (optional($settings)->accounting_user_id) {
                    $purchaseOrder->status = 'accounting_approval';
                    $nextAssignId = $settings->accounting_user_id;
                } else {
                    $purchaseOrder->status = 'purchasing';
                    $nextAssignId = $purchaseOrder->requested_by;
                }
            } else { // step 3 (accounting)
                $purchaseOrder->status = 'purchasing';
                $nextAssignId = $purchaseOrder->requested_by;
            }

            if ($nextAssignId) {
                $purchaseOrder->assigned_to = $nextAssignId;
            }
            $purchaseOrder->save();

            // Notify next approver or creator
            if ($nextAssignId) {
                $user = \App\Models\User::find($nextAssignId);
                if ($user && method_exists($user, 'notify')) {
                    try {
                        $user->notify(FormApprovalNotification::fromModel($purchaseOrder, auth()->id() ?? 0));
                    } catch (\Throwable $e) {
                        Log::error('PurchaseOrder.approve: failed to notify next user', [
                            'error' => $e->getMessage(),
                            'po_id' => $purchaseOrder->id,
                            'user_id' => $nextAssignId,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'سفارش در این مرحله تأیید شد.');
    }

    /**
     * Reject the purchase order at the current step.
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $settings = PurchaseOrderWorkflowSetting::first();

        $validated = $request->validate([
            'reject_reason' => ['required','string','max:2000'],
        ], [
            'reject_reason.required' => 'لطفا دلیل رد سفارش خرید را وارد کنید.',
        ]);

        $step = 1;
        $expectedId = optional($settings)->first_approver_id;
        if ($purchaseOrder->status === 'manager_approval') {
            $step = 2;
            $expectedId = optional($settings)->second_approver_id;
        } elseif ($purchaseOrder->status === 'accounting_approval') {
            $step = 3;
            $expectedId = optional($settings)->accounting_user_id;
        } elseif ($purchaseOrder->status === 'created' || $purchaseOrder->status === null) {
            $step = 1;
            $expectedId = optional($settings)->first_approver_id;
        }

        if (!$expectedId || (int) auth()->id() !== (int) $expectedId) {
            return back()->with('error', 'دسترسی برای رد این مرحله ندارید.');
        }

        DB::transaction(function () use ($purchaseOrder, $step, $expectedId, $validated) {
            // Save note for rejection reason
            try {
                $purchaseOrder->notes()->create([
                    'body'    => "دلیل رد سفارش خرید:\n" . trim((string) $validated['reject_reason']),
                    'user_id' => auth()->id(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('PurchaseOrder.reject: failed to create note', [
                    'error' => $e->getMessage(),
                    'po_id' => $purchaseOrder->id,
                ]);
            }
            $purchaseOrder->status = 'rejected';
            $purchaseOrder->assigned_to = $purchaseOrder->requested_by;
            $purchaseOrder->save();

            $purchaseOrder->approvals()->updateOrCreate(
                ['user_id' => $expectedId, 'step' => $step],
                ['status' => 'rejected', 'approved_at' => now()]
            );

            // Optionally notify creator on rejection
            $creator = \App\Models\User::find($purchaseOrder->requested_by);
            if ($creator && method_exists($creator, 'notify')) {
                try {
                    $creator->notify(FormApprovalNotification::fromModel($purchaseOrder, auth()->id() ?? 0));
                } catch (\Throwable $e) {
                    Log::error('PurchaseOrder.reject: failed to notify creator', [
                        'error' => $e->getMessage(),
                        'po_id' => $purchaseOrder->id,
                        'user_id' => $purchaseOrder->requested_by,
                    ]);
                }
            }
        });

        return back()->with('success', 'سفارش رد شد.');
    }

    /**
     * Creator marks items delivered to warehouse after purchasing in progress.
     */
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
}
