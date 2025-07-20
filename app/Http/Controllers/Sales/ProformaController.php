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
    public function index(Request $request)
    {
        $query = Proforma::query()
            ->select('proformas.*',
                'users.name as assigned_to_name',
                'organizations.name as organization_name',
                DB::raw("contacts.first_name || ' ' || contacts.last_name as contact_name"),
                'opportunities.name as opportunity_name')
            ->leftJoin('users', 'proformas.assigned_to', '=', 'users.id')
            ->leftJoin('organizations', 'proformas.organization_id', '=', 'organizations.id')
            ->leftJoin('contacts', 'proformas.contact_id', '=', 'contacts.id')
            ->leftJoin('opportunities', 'proformas.opportunity_id', '=', 'opportunities.id');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('proformas.subject', 'like', "%{$search}%")
                  ->orWhere('organizations.name', 'like', "%{$search}%");
            });
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        if ($sortField === 'assigned_to_name') {
            $query->orderBy('users.name', $sortDirection);
        } elseif ($sortField === 'organization_name') {
            $query->orderBy('organizations.name', $sortDirection);
        } elseif ($sortField === 'contact_name') {
            $query->orderBy(DB::raw("contacts.first_name || ' ' || contacts.last_name"), $sortDirection);
        } elseif ($sortField === 'opportunity_name') {
            $query->orderBy('opportunities.name', $sortDirection);
        } else {
            $query->orderBy("proformas.{$sortField}", $sortDirection);
        }

        $proformas = $query->paginate(10)->withQueryString();

        return view('sales.proformas.index', compact('proformas'));
    }

    public function create()
    {
        $organizations = Organization::all();
        $contacts = Contact::all();
        $opportunities = Opportunity::all();
        $users = User::all();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $proformaStages = config('proforma.stages'); 

        return view('sales.proformas.create', compact(
            'organizations', 'contacts', 'opportunities', 'users', 'products',  'proformaStages'
        ));
    }

    public function store(Request $request)
    {
        Log::debug('Proforma request payload:', $request->all());

        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'proforma_date' => 'nullable|string',
                'contact_name' => 'nullable|string|max:255',
                'proforma_number' => 'nullable|string|max:255',
                'proforma_stage' => ['required', Rule::in(array_keys(config('proforma.stages')))],
                'organization_name' => 'nullable|string|max:255',
                'address_type' => 'required|in:invoice,product',
                'customer_address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'assigned_to' => 'required|exists:users,id',
                'opportunity_id' => 'nullable|exists:opportunities,id',
                'products' => 'required|array|min:1',
                'products.*.name' => 'required|string|max:255',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.price' => 'required|numeric|min:0',
                'products.*.unit' => 'required|string|max:50',
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
                'proforma_number' => $validated['proforma_number'],
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

            foreach ($validated['products'] as $item) {
                $unitPrice = floatval($item['price']);
                $quantity = floatval($item['quantity']);
                $baseTotal = $unitPrice * $quantity;

                // تخفیف
                $discountType = $item['discount_type'] ?? null;
                $discountValue = floatval($item['discount_value'] ?? 0);
                $discountAmount = 0;
                if ($discountType === 'percentage') {
                    $discountAmount = ($baseTotal * $discountValue) / 100;
                } elseif ($discountType === 'fixed') {
                    $discountAmount = $discountValue;
                }

                $afterDiscount = $baseTotal - $discountAmount;

                // مالیات
                $taxType = $item['tax_type'] ?? null;
                $taxValue = floatval($item['tax_value'] ?? 0);
                $taxAmount = 0;
                if ($taxType === 'percentage') {
                    $taxAmount = ($afterDiscount * $taxValue) / 100;
                } elseif ($taxType === 'fixed') {
                    $taxAmount = $taxValue;
                }

                $totalAfterTax = $afterDiscount + $taxAmount;

                // ذخیره آیتم
                $proforma->items()->create([
                    'name' => $item['name'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_of_use' => $item['unit'],
                    'total_price' => $baseTotal,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'tax_type' => $taxType,
                    'tax_value' => $taxValue,
                    'tax_amount' => $taxAmount,
                    'total_after_tax' => $totalAfterTax,
                ]);

                $totalAmount += $totalAfterTax;
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
        $proforma->load(['organization', 'contact', 'opportunity', 'assignedTo', 'items']);
        return view('sales.proformas.show', compact('proforma'));
    }

    public function edit(Proforma $proforma)
    {
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
                'proforma_number' => 'nullable|string|max:255',
                'proforma_stage' => ['required', Rule::in(array_keys(config('proforma.stages')))],
                'organization_name' => 'nullable|string|max:255',
                'address_type' => 'required|in:invoice,product',
                'customer_address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:255',
                'assigned_to' => 'required|exists:users,id',
                'opportunity_id' => 'nullable|exists:opportunities,id',
                'products' => 'required|array|min:1',
                'products.*.name' => 'required|string|max:255',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.price' => 'required|numeric|min:0',
                'products.*.unit' => 'required|string|max:50',
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
                'proforma_number' => $validated['proforma_number'],
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
        try {
            $proforma->delete();
            return redirect()->route('sales.proformas.index')
                ->with('success', 'پیش‌فاکتور با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در حذف پیش‌فاکتور. لطفا دوباره تلاش کنید.');
        }
    }

    
    protected function runAutomationRulesIfNeeded(Proforma $proforma)
    {
        Log::info('🚀 runAutomationRulesIfNeeded triggered');

        if ($proforma->proforma_stage === 'send_for_approval') {
            $rules = AutomationRule::with('approvers')->where('proforma_stage', 'send_for_approval')->get();
            Log::info('🔍 Rules count: ' . $rules->count());

            foreach ($rules as $rule) {
                Log::info('📦 Approvers for rule:', $rule->approvers->pluck('id', 'name')->toArray());

                foreach ($rule->approvers as $user) {
                    \App\Models\Approval::create([
                        'approvable_type' => get_class($proforma),
                        'approvable_id' => $proforma->id,
                        'approver_id' => $user->id,
                        'user_id' => $user->id,
                        'status' => 'pending',
                    ]);

                    NotificationHelper::send($user, \App\Notifications\ProformaApprovalRequest::class, [
                        'title' => 'درخواست تایید پیش‌فاکتور',
                        'message' => 'پیش‌فاکتور "' . $proforma->subject . '" برای تایید ارسال شده است.',
                        'url' => route('sales.proformas.show', $proforma->id),
                    ]);
                }
            }
        }
    }




    public function sendForApproval(Proforma $proforma)
    {
        $proforma->proforma_stage = 'ارسال برای تاییدیه';
        $proforma->save();

        $this->runAutomationRulesIfNeeded($proforma);

        return redirect()->route('sales.proformas.index')->with('success', 'پیش‌فاکتور با موفقیت برای تاییدیه ارسال شد.');
    }

    public function approve(Proforma $proforma)
    {
        $userId = auth()->id();

        $condition = AutomationCondition::where('model_type', 'Proforma')
            ->where('field', 'proforma_stage')
            ->where('operator', '=')
            ->where('value', $proforma->proforma_stage)
            ->first();

        if (!$condition) {
            return back()->with('error', 'هیچ شرطی برای این مرحله تعریف نشده است.');
        }

        if ($userId !== $condition->approver1_id && $userId !== $condition->approver2_id) {
            return back()->with('error', 'شما مجاز به تأیید این مرحله نیستید.');
        }

        $alreadyApprovedByCurrentUser = Approval::where('approvable_id', $proforma->id)
            ->where('approvable_type', Proforma::class)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyApprovedByCurrentUser) {
            return back()->with('error', 'شما قبلاً این مرحله را تایید کرده‌اید.');
        }

        $approval = new Approval();
        $approval->approvable_id = $proforma->id;
        $approval->approvable_type = Proforma::class;
        $approval->user_id = $userId;
        $approval->status = 'approved';
        $approval->approved_at = now();
        $approval->save();

        $otherApprover = ($userId == $condition->approver1_id) ? $condition->approver2_id : $condition->approver1_id;

        $alreadyApproved = Approval::where('approvable_id', $proforma->id)
            ->where('approvable_type', Proforma::class)
            ->where('user_id', $otherApprover)
            ->exists();

            if (!$alreadyApproved && $otherApprover) {
                $user = \App\Models\User::find($otherApprover);
                if ($user) {
                    $user->notify(new \App\Notifications\FormApprovalNotification($proforma, auth()->user()));
                }
            }
            

        $totalApprovers = collect([$condition->approver1_id, $condition->approver2_id])->filter()->unique()->count();

        $totalApproved = Approval::where('approvable_id', $proforma->id)
            ->where('approvable_type', Proforma::class)
            ->where('status', 'approved')
            ->distinct('user_id')
            ->count('user_id');

        if ($totalApproved >= $totalApprovers) {
            $proforma->proforma_stage = 'approved';
            $proforma->save();
        }

        return back()->with('success', 'پیش‌فاکتور با موفقیت تأیید شد.');
    }

}
