<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Proforma;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\Product;
use App\Models\AutomationRule;
use App\Models\AutomationRuleApprover;
use App\Models\AutomationCondition;
use App\Notifications\FormApprovalNotification;
use App\Models\Approval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use App\Helpers\NotificationHelper;
use Exception;

class ProformaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin')->only('destroy');
    }
    public function index(Request $request)
    {
        $organizations = Organization::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $query = Proforma::with(['organization', 'contact', 'opportunity', 'assignedTo']);

        // فیلتر جستجو
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('organization', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('contact', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }
         // فیلتر براساس سازمان
    if ($request->filled('organization_id')) {
        $query->where('organization_id', $request->organization_id);
    }

    // فیلتر براساس مرحله
    if ($request->filled('stage')) {
        $query->where('proforma_stage', $request->stage);
    }

    // فیلتر براساس ارجاع به
    if ($request->filled('assigned_to')) {
        $query->where('assigned_to', $request->assigned_to);
    }
        $proformas = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('sales.proformas.index', compact('proformas', 'organizations', 'users'));
    }

    public function create(Request $request)
    {
        $prefill = [];

        // اگر از صفحه فرصت وارد شده‌ایم
        if ($request->filled('opportunity_id')) {
            $opportunity = Opportunity::with(['organization','contact'])->find($request->opportunity_id);

            if ($opportunity) {
                $contactFullName = trim(
                    ($opportunity->contact->first_name ?? '').' '.($opportunity->contact->last_name ?? '')
                );

                $prefill = [
                    'opportunity_id'     => $opportunity->id,
                    'organization_id'    => optional($opportunity->organization)->id,
                    'organization_name'  => optional($opportunity->organization)->name,
                    'contact_id'         => optional($opportunity->contact)->id,
                    'contact_name'       => $contactFullName ?: ($opportunity->contact->last_name ?? ''),
                    'customer_address'   => optional($opportunity->organization)->address ?: '',
                    'city'               => optional($opportunity->organization)->city   ?: '',
                    'state'              => optional($opportunity->organization)->state  ?: '',
                ];
            }
        }

        $organizations   = Organization::orderBy('name')->get();
        $contacts        = Contact::orderBy('id','desc')->get();
        $opportunities   = Opportunity::orderBy('id','desc')->get();
        $users           = User::orderBy('id')->get();
        $products        = Product::where('is_active', true)->orderBy('name')->get();
        $proformaStages  = config('proforma.stages'); 

        return view('sales.proformas.create', compact(
            'organizations', 'contacts', 'opportunities', 'users', 'products', 'proformaStages', 'prefill'
        ));
    }

    public function store(Request $request)
    {
        \Log::info('Creating Proforma', [
            'stage' => $request->proforma_stage,
            'data' => $request->all()
        ]);
        
        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'proforma_date' => 'nullable|string',
                'contact_name' => 'nullable|string|max:255',
                'proforma_stage' => ['required', Rule::in(array_keys(config('proforma.stages')))],
                'organization_name' => 'nullable|string|max:255',
                'address_type' => 'required|in:invoice,product',
                'customer_address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'assigned_to' => 'required|exists:users,id',
                'opportunity_id' => 'nullable|exists:opportunities,id',
                'products' => 'nullable|array',
                'products.*.name' => 'nullable|string|max:255',
                'products.*.quantity' => 'nullable|numeric|min:0.01',
                'products.*.price' => 'nullable|numeric|min:0',
                'products.*.unit' => 'nullable|string|max:50',
                'products.*.discount_type' => 'nullable|in:percentage,fixed',
                'products.*.discount_value' => 'nullable|numeric|min:0',
                'products.*.tax_type' => 'nullable|in:percentage,fixed',
                'products.*.tax_value' => 'nullable|numeric|min:0',
            ]);
            Log::debug('✅ Passed validation:', $validated);

            // تاریخ میلادی از تاریخ شمسی
            $miladiDate = null;
            if (!empty($validated['proforma_date'])) {
                try {
                    $jalaliDate = str_replace('-', '/', $validated['proforma_date']);
                    if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $jalaliDate)) {
                        $miladiDate = Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon();
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Invalid Jalali Date:', ['exception' => $e->getMessage()]);
                    return back()->withInput()->with('error', 'تاریخ وارد شده معتبر نیست.');
                }
            }

            DB::beginTransaction();

            $totalAmount = 0;

            $proforma = Proforma::create([
                'subject' => $validated['subject'],
                'proforma_date' => $miladiDate,
                'contact_name' => $validated['contact_name'],
                'proforma_stage' => $validated['proforma_stage'],
                'organization_name' => $validated['organization_name'],
                'address_type' => $validated['address_type'],
                'customer_address' => $validated['customer_address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'assigned_to' => $validated['assigned_to'],
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'total_amount' => 0, // مقدار اولیه، بعداً آپدیت می‌کنیم
            ]);
            Log::info('📄 Proforma Created:', ['id' => $proforma->id]);
            
            $totalAmount = 0;

            if (!empty($validated['products'])) {
                foreach ($validated['products'] as $item) {
                    $unitPrice  = (float) ($item['price'] ?? 0);
                    $quantity   = (float) ($item['quantity'] ?? 0);
                    $baseTotal  = $unitPrice * $quantity;

                    // محاسبه تخفیف
                    $discountType  = $item['discount_type'] ?? null;
                    $discountValue = (float) ($item['discount_value'] ?? 0);
                    $discountAmount = match ($discountType) {
                        'percentage' => ($baseTotal * $discountValue) / 100,
                        'fixed'      => $discountValue,
                        default      => 0,
                    };

                    $afterDiscount = $baseTotal - $discountAmount;

                    // محاسبه مالیات
                    $taxType  = $item['tax_type'] ?? null;
                    $taxValue = (float) ($item['tax_value'] ?? 0);
                    $taxAmount = match ($taxType) {
                        'percentage' => ($afterDiscount * $taxValue) / 100,
                        'fixed'      => $taxValue,
                        default      => 0,
                    };

                    $totalAfterTax = $afterDiscount + $taxAmount;

                    // ذخیره آیتم پروفرما
                    $proforma->items()->create([
                        'name'            => $item['name'] ?? '',
                        'quantity'        => $quantity,
                        'unit_price'      => $unitPrice,
                        'unit_of_use'     => $item['unit'] ?? '',
                        'total_price'     => $baseTotal,
                        'discount_type'   => $discountType,
                        'discount_value'  => $discountValue,
                        'discount_amount' => $discountAmount,
                        'tax_type'        => $taxType,
                        'tax_value'       => $taxValue,
                        'tax_amount'      => $taxAmount,
                        'total_after_tax' => $totalAfterTax,
                    ]);

                    $totalAmount += $totalAfterTax;
                }

                // ذخیره جمع کل در خود پروفرما
                $proforma->update([
                    'total_amount' => $totalAmount
                ]);
            }

            

            $proforma->update(['total_amount' => $totalAmount]);
            Log::debug('🧮 Total Amount Saved:', ['total_amount' => $totalAmount]);

            $proforma->notifyIfAssigneeChanged(null);

            if ($proforma->proforma_stage === 'send_for_approval') {
                $condition = AutomationCondition::where('model_type', 'Proforma')
                    ->where('field', 'proforma_stage')
                    ->where('operator', '=')
                    ->where('value', 'send_for_approval')
                    ->first();

                if ($condition) {
                    Log::info('🔔 Automation condition matched for send_for_approval');
                    $sender = Auth::user();
                    foreach ([$condition->approver1_id, $condition->approver2_id] as $approverId) {
                        if ($approverId && ($user = User::find($approverId))) {
                            $user->notify(new \App\Notifications\FormApprovalNotification($proforma, $sender));
                        }
                    }
                }
            }

            DB::commit();
            $proforma->refresh();
            $this->runAutomationRulesIfNeeded($proforma);

            return redirect()->route('sales.proformas.index')->with('success', 'پیش‌فاکتور با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error Creating Proforma:', ['exception' => $e->getMessage()]);
            return back()->withInput()->with('error', 'خطا در ایجاد پیش‌فاکتور. لطفا دوباره تلاش کنید.');
        }
    }



    public function show(Proforma $proforma)
    {
        $proforma->load([
            'organization', 'contact', 'opportunity', 'assignedTo',
            'items',
            'approvals.approver',   // برای سیستم قدیمی approvals
        ]);
    
        // 1) اگر سیستم approvals رکورد pending دارد، همان را استفاده کن
        $approval = $proforma->approvals()
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();
    
        $pendingApproval = $proforma->approvals
            ->where('status', 'pending')
            ->first();
    
        $pendingApproverName = $pendingApproval?->approver?->name;
    
        // 2) در غیر این صورت، از قوانین اتوماسیون محاسبه کن
        if (empty($pendingApproverName)) {
            $stage = $proforma->approval_stage ?? $proforma->proforma_stage;
    
            if ($stage === 'send_for_approval') {
                $rule = AutomationRule::with(['approvers.user'])
                    ->where('proforma_stage', 'send_for_approval')
                    ->first();
    
                if ($rule) {
                    $pendingApproverId = null;
    
                    if (empty($proforma->first_approved_by)) {
                        // هنوز مرحله اول تایید نشده
                        $pendingApproverId = optional($rule->approvers->firstWhere('priority', 1))->user_id;
                    } elseif (empty($proforma->approved_by)) {
                        // مرحله اول تایید شده ولی نهایی نشده
                        $pendingApproverId =
                            optional($rule->approvers->firstWhere('priority', 2))->user_id
                            ?? $rule->emergency_approver_id;
                    }
    
                    $pendingApproverName = $pendingApproverId
                        ? optional(User::find($pendingApproverId))->name
                        : null;
                }
            }
        }
    
        return view('sales.proformas.show', compact('proforma', 'approval', 'pendingApproverName'));
    }
    
    

    public function edit(Proforma $proforma)
    {
        if ($proforma->proforma_stage === 'send_for_approval') {
            return redirect()
                ->route('sales.proformas.show', $proforma)
                ->with('alert_error', 'پیش‌فاکتور در انتظار تایید است و امکان ویرایش ندارد.');
        }
        $proforma->load('items');
        $users = User::all();
        $organizations = Organization::all();
        $contacts = Contact::all();
        $opportunities = Opportunity::all();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $proformaStages = config('proforma.stages'); 

        return view('sales.proformas.edit', compact(
            'proforma', 'users', 'organizations', 'contacts', 'opportunities', 'products',  'proformaStages'
        ));
    }

    public function update(Request $request, Proforma $proforma)
    {
        Log::debug('✏️ Update Request Payload:', $request->all());
    
        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'proforma_date' => 'nullable|string',
                'contact_name' => 'nullable|string|max:255',
                'inventory_manager' => 'nullable|string|max:255',
                'proforma_stage' => ['required', Rule::in(array_keys(config('proforma.stages')))],
                'organization_name' => 'nullable|string|max:255',
                'address_type' => 'required|in:invoice,product',
                'customer_address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:255',
                'assigned_to' => 'required|exists:users,id',
                'opportunity_id' => 'nullable|exists:opportunities,id',
    
                // محصولات دیگر اجباری نیستند
                'products' => 'nullable|array',
                'products.*.name' => 'nullable|string|max:255',
                'products.*.quantity' => 'nullable|numeric|min:0.01',
                'products.*.price' => 'nullable|numeric|min:0',
                'products.*.unit' => 'nullable|string|max:50',
                'products.*.discount_type' => 'nullable|in:percentage,fixed',
                'products.*.discount_value' => 'nullable|numeric|min:0',
                'products.*.tax_type' => 'nullable|in:percentage,fixed',
                'products.*.tax_value' => 'nullable|numeric|min:0',
            ]);
            Log::debug('✅ Passed Update Validation:', $validated);
    
            $miladiDate = null;
            if (!empty($validated['proforma_date'])) {
                try {
                    $jalaliDateString = str_replace('-', '/', $validated['proforma_date']);
                    $miladiDate = Jalalian::fromFormat('Y/m/d', $jalaliDateString)->toCarbon();
                } catch (\Exception $e) {
                    Log::error('❌ Invalid Jalali Date on Update:', ['exception' => $e->getMessage()]);
                    return back()->withInput()->with('error', 'تاریخ وارد شده معتبر نیست.');
                }
            }
    
            DB::beginTransaction();
    
            $totalAmount = 0;
            $proformaItems = [];
    
            foreach ($validated['products'] as $item) {
                $quantity = floatval($item['quantity']);
                $unitPrice = floatval($item['price']);
                $discountValue = floatval($item['discount_value'] ?? 0);
                $taxValue = floatval($item['tax_value'] ?? 0);
    
                // محاسبه تخفیف
                $discountAmount = ($item['discount_type'] === 'percentage') 
                    ? ($unitPrice * $discountValue / 100)
                    : $discountValue;
    
                $priceAfterDiscount = $unitPrice - $discountAmount;
    
                // محاسبه مالیات
                $taxAmount = ($item['tax_type'] === 'percentage')
                    ? ($priceAfterDiscount * $taxValue / 100)
                    : $taxValue;
    
                $totalPrice = $unitPrice * $quantity;
                $totalAfterTax = ($priceAfterDiscount + $taxAmount) * $quantity;
    
                $totalAmount += $totalAfterTax;
    
                $proformaItems[] = [
                    'name' => $item['name'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_of_use' => $item['unit'],
                    'total_price' => $totalPrice,
                    'discount_type' => $item['discount_type'] ?? null,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'tax_type' => $item['tax_type'] ?? null,
                    'tax_value' => $taxValue,
                    'tax_amount' => $taxAmount,
                    'total_after_tax' => $totalAfterTax,
                ];
            }
    
            $oldAssignedTo = $proforma->assigned_to;
            $oldStage = $proforma->proforma_stage;
    
            $proforma->update([
                'subject' => $validated['subject'],
                'proforma_date' => $miladiDate,
                'contact_name' => $validated['contact_name'],
                'inventory_manager' => $validated['inventory_manager'],
                'proforma_stage' => $validated['proforma_stage'],
                'organization_name' => $validated['organization_name'],
                'address_type' => $validated['address_type'],
                'customer_address' => $validated['customer_address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'assigned_to' => $validated['assigned_to'],
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'total_amount' => $totalAmount,
            ]);
            Log::info('✅ Proforma Updated:', ['id' => $proforma->id]);
    
            $proforma->items()->delete();
            $proforma->items()->createMany($proformaItems);
    
            $proforma->notifyIfAssigneeChanged($oldAssignedTo);
    
            // اعلان تایید در صورت تغییر به مرحله مربوطه
            if ($validated['proforma_stage'] === 'send_for_approval' && $oldStage !== 'send_for_approval') {
                $condition = \App\Models\AutomationCondition::where('model_type', 'Proforma')
                    ->where('field', 'proforma_stage')
                    ->where('operator', '=')
                    ->where('value', 'send_for_approval')
                    ->first();
    
                if ($condition) {
                    Log::info('🔔 Automation condition matched for send_for_approval');
                    $sender = auth()->user();
                    if ($condition->approver1_id) {
                        $approver1 = \App\Models\User::find($condition->approver1_id);
                        if ($approver1) {
                            $approver1->notify(new \App\Notifications\FormApprovalNotification($proforma, $sender));
                        }
                    }
                    if ($condition->approver2_id) {
                        $approver2 = \App\Models\User::find($condition->approver2_id);
                        if ($approver2) {
                            $approver2->notify(new \App\Notifications\FormApprovalNotification($proforma, $sender));
                        }
                    }
                }
            }
    
            DB::commit();
            return redirect()->route('sales.proformas.show', $proforma)->with('success', 'پیش‌فاکتور با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error Updating Proforma:', ['exception' => $e->getMessage()]);
            return back()->withInput()->with('error', 'خطا در بروزرسانی پیش‌فاکتور.');
        }
    }
    


    public function destroy(Proforma $proforma)
    {
        if ($proforma->proforma_stage === 'send_for_approval') {
            return redirect()
                ->route('sales.proformas.show', $proforma)
                ->with('alert_error', 'پیش‌فاکتور در انتظار تایید است و امکان حذف ندارد.');
        }
        try {
            $proforma->delete();
            return redirect()->route('sales.proformas.index')
                ->with('success', 'پیش‌فاکتور با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در حذف پیش‌فاکتور. لطفا دوباره تلاش کنید.');
        }
    }

    
    private function runAutomationRulesIfNeeded(\App\Models\Proforma $proforma): void
    {
        try {
            $stage = strtolower(trim($proforma->approval_stage ?? $proforma->proforma_stage));
    
            Log::debug('🚀 runAutomationRulesIfNeeded', [
                'proforma_id'      => $proforma->id,
                'stage'            => $stage,
                'first_approved_by'=> $proforma->first_approved_by,
                'approved_by'      => $proforma->approved_by,
            ]);
    
            // فقط وقتی مرحله یکی از این دو باشه ادامه بده
            if (! in_array($stage, ['send_for_approval', 'awaiting_second_approval'])) {
                Log::info('⏭️ Skipped: Stage not relevant for approvals', ['current_stage' => $stage]);
                return;
            }
    
            $rule = AutomationRule::with(['approvers.user'])
                ->where('proforma_stage', 'send_for_approval')
                ->first();
    
            if (! $rule) {
                Log::warning('⚠️ No automation rule found for send_for_approval');
                return;
            }
    
            // 📌 ذخیره‌سازی automation_rule_id در پروفرما
            if ($proforma->automation_rule_id !== $rule->id) {
                $proforma->automation_rule_id = $rule->id;
                $proforma->save();
                Log::info('💾 automation_rule_id saved to proforma', [
                    'proforma_id'       => $proforma->id,
                    'automation_rule_id'=> $rule->id
                ]);
            }
    
            $approvers = $rule->approvers ?? collect();
    
            Log::info('👥 Approvers found', [
                'count' => $approvers->count(),
                'list'  => $approvers->map(fn($a) => [
                    'priority' => $a->priority,
                    'user_id'  => $a->user_id,
                    'name'     => optional($a->user)->name,
                ])->toArray(),
                'emergency_approver_id' => $rule->emergency_approver_id,
            ]);
    
            // تعیین نفر بعدی
            if (empty($proforma->first_approved_by)) {
                $nextApproverId = optional($approvers->firstWhere('priority', 1))->user_id;
                $nextStep = 1;
            } elseif (empty($proforma->approved_by)) {
                $nextApproverId = optional($approvers->firstWhere('priority', 2))->user_id
                    ?? $rule->emergency_approver_id;
                $nextStep = 2;
            } else {
                Log::info('✅ Proforma already fully approved');
                return;
            }
    
            if (! $nextApproverId) {
                Log::warning('⚠️ No next approver determined', ['proforma_id' => $proforma->id]);
                return;
            }
    
            // پاک‌سازی pending‌های قبلی به جز نفر بعدی
            $proforma->approvals()
                ->where('status', 'pending')
                ->where('user_id', '!=', $nextApproverId)
                ->delete();
    
            // ایجاد یا بروزرسانی رکورد تایید
            $approval = $proforma->approvals()->updateOrCreate(
                ['user_id' => $nextApproverId, 'status' => 'pending'], 
                ['step' => $nextStep]
            );
    
            Log::info('📝 Pending approval set', [
                'approval_id' => $approval->id,
                'user_id'     => $nextApproverId,
                'step'        => $nextStep
            ]);
    
            // ارسال نوتیفیکیشن
            $user = User::find($nextApproverId);
            if ($user && method_exists($user, 'notify')) {
                try {
                    $user->notify(FormApprovalNotification::fromModel($proforma, auth()->id() ?? 0));
                    Log::info('📨 Notification sent', [
                        'to_user_id'   => $user->id,
                        'to_user_name' => $user->name,
                        'proforma_id'  => $proforma->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('📭 Notification failed', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        }
        catch (\Exception $e) {
            Log::error('❌ Error in runAutomationRulesIfNeeded', [
                'proforma_id' => $proforma->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
    
    



    public function sendForApproval(Proforma $proforma)
    {
        \DB::transaction(function () use ($proforma) {
            $proforma->fill([
                'approval_stage' => 'send_for_approval',
                'proforma_stage' => 'send_for_approval', // 🔹 همگام‌سازی
            ])->save();
    
            // اجرای اتوماسیون بعد از آپدیت
            $this->runAutomationRulesIfNeeded($proforma);
        });
    
        return redirect()
            ->route('sales.proformas.index')
            ->with('success', 'پیش‌فاکتور با موفقیت برای تاییدیه ارسال شد.');
    }
    

    public function approve(Proforma $proforma)
    {
        $this->authorize('approve', $proforma);
    
        try {
            \DB::transaction(function () use ($proforma) {
                $userId = auth()->id();
    
                $approvals = $proforma->approvals()
                    ->with('approver')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();
    
                // رکوردِ مرحله‌ی در انتظار
                $pending = $approvals->firstWhere('status', 'pending');
                if (! $pending) {
                    throw new \RuntimeException('هیچ مرحله‌ی در انتظاری برای تایید وجود ندارد.');
                }
    
                // حالت 1: خودِ تاییدکننده‌ی اصلی
                $current = $approvals->firstWhere('user_id', $userId);
    
                // حالت 2: اگر اصلی نبود، بررسی emergency approver روی همان pending
                $asEmergency = false;
                if (! $current) {
                    $rule = $proforma->automationRule()->first();
                    if ($rule && (int) $rule->emergency_approver_id === (int) $userId) {
                        $current = $pending;   // اجازه بده emergency همان مرحله‌ی pending را تایید کند
                        $asEmergency = true;
                    }
                }
    
                if (! $current) {
                    throw new \RuntimeException('شما مجاز به تایید این پیش‌فاکتور نیستید.');
                }
                if ($current->status !== 'pending') {
                    throw new \RuntimeException('شما قبلاً این پیش‌فاکتور را تایید کرده‌اید.');
                }
    
                // رعایت ترتیب مراحل: اگر قبل از این رکورد، آیتمی هنوز approved نشده، خطا بده
                $idx     = $approvals->search(fn ($a) => (int) $a->id === (int) $current->id);
                $blocker = $approvals->take($idx)->first(fn ($a) => $a->status !== 'approved');
                if ($blocker) {
                    $who = optional($blocker->approver)->name ?: ('کاربر #' . $blocker->user_id);
                    throw new \RuntimeException("پیش‌فاکتور در انتظار تایید {$who} است.");
                }
    
                // تایید این مرحله
                $current->update([
                    'status'      => 'approved',
                    'approved_at' => now(),
                ]);
    
                $step = (int) ($current->step ?? 1);
    
                if ($step === 1) {
                    if (empty($proforma->first_approved_by)) {
                        // چه اصلی چه اضطراری، همان کاربر فعلی را ثبت کن
                        $proforma->first_approved_by = $userId;
                    }
    
                    $proforma->fill([
                        'approval_stage' => 'awaiting_second_approval',
                        'proforma_stage' => 'awaiting_second_approval', // همگام‌سازی
                    ])->save();
    
                    $this->runAutomationRulesIfNeeded($proforma);
    
                } elseif ($step === 2) {
                    $proforma->fill([
                        'approved_by'    => $userId,
                        'approval_stage' => 'approved',
                        'proforma_stage' => 'approved', // همگام‌سازی
                    ])->save();
                }
    
                // بررسی اینکه نفر دوم تعریف نشده و pending دیگری وجود ندارد
                $rule = $proforma->automationRule()->with('approvers')->first();
                $hasSecondApprover = $rule && $rule->approvers()->where('priority', 2)->exists();
    
                $hasPending = $proforma->approvals()
                    ->where('status', 'pending')
                    ->exists();
    
                if (! $hasPending && $step === 1 && ! $hasSecondApprover) {
                    $proforma->fill([
                        'approved_by'    => $userId,
                        'approval_stage' => 'approved',
                        'proforma_stage' => 'approved', // همگام‌سازی
                    ])->save();
                }
    
                \Log::info('✅ Proforma approval progressed', [
                    'proforma_id' => $proforma->id,
                    'by_user'     => $userId,
                    'step'        => $step,
                    'stage'       => $proforma->approval_stage,
                    'as_emergency'=> $asEmergency,
                ]);
            });
    
            return back()->with('success', 'پیش‌فاکتور با موفقیت تایید شد.');
    
        } catch (\Throwable $e) {
            \Log::error('❌ Proforma approve failed', [
                'proforma_id' => $proforma->id ?? null,
                'error'       => $e->getMessage(),
            ]);
    
            return back()->with('error', $e->getMessage());
        }
    }
    



    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer','distinct'],
            'force_delete' => ['nullable','boolean'],
        ]);

        // جلوگیری از حذف آیتم‌های در وضعیت تایید
        $ids = Proforma::query()
            ->whereIn('id', $data['ids'])
            ->where('proforma_stage', '!=', 'send_for_approval')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return back()->with('error', 'هیچ آیتم قابل حذفی انتخاب نشده است.');
        }

        try {
            DB::transaction(function () use ($ids) {
                Proforma::query()->whereIn('id', $ids)->delete(); // همین کافی است
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در حذف گروهی: '.$e->getMessage());
        }

        return back()->with('success', $ids->count().' مورد حذف شد.');
    }

    

}
