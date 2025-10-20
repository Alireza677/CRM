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
    // ورودی‌ها
    $search          = trim((string) $request->get('search', ''));
    $organizationId  = $request->get('organization_id');
    $stage           = $request->get('stage');
    $assignedTo      = $request->get('assigned_to');

    // دیتای کم‌حجم برای ویو (فقط فیلدهای لازم)
    $organizations = Organization::select('id', 'name')->orderBy('name')->get();
    $users         = User::select('id', 'name')->orderBy('name')->get();

    // کوئری اصلی
    $query = Proforma::query()
        ->with(['organization', 'contact', 'opportunity', 'assignedTo'])
        ->orderByDesc('proforma_date')
        ->orderByDesc('created_at');

    // جستجو
    $query->when($search !== '', function ($q) use ($search) {
        $q->where(function ($qq) use ($search) {
            $qq->where('subject', 'like', "%{$search}%")
               ->orWhereHas('organization', function ($q2) use ($search) {
                   $q2->where('name', 'like', "%{$search}%");
               })
               ->orWhereHas('contact', function ($q3) use ($search) {
                   $q3->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name',  'like', "%{$search}%");
                   // اگر مدل contact ستون full_name دارد، می‌توانی این را هم اضافه کنی:
                   // ->orWhere('full_name', 'like', "%{$search}%");
               });
        });
    });

    // فیلتر سازمان (هماهنگ با input hidden[name=organization_id])
    $query->when(!empty($organizationId), function ($q) use ($organizationId) {
        $q->where('organization_id', (int) $organizationId);
    });

    // فیلتر مرحله
    $query->when(!empty($stage), function ($q) use ($stage) {
        $q->where('proforma_stage', $stage);
    });

    // فیلتر ارجاع‌به (کاربر)
    $query->when(!empty($assignedTo), function ($q) use ($assignedTo) {
        $q->where('assigned_to', (int) $assignedTo);
    });

    // صفحه‌بندی + حفظ کوئری‌استرینگ
    // Page size (per-page) with whitelist
    $allowedPerPage = [10, 25, 50, 100];
    $perPage = (int) $request->get('per_page', 10);
    if (!in_array($perPage, $allowedPerPage, true)) {
        $perPage = 10;
    }

    // Paginate with current query string preserved
    $proformas = $query->paginate($perPage)->withQueryString();

    return view('sales.proformas.index', compact('proformas', 'organizations', 'users'));
}


    public function create(Request $request)
    {
        $prefill = [];
    
        if ($request->filled('opportunity_id')) {
            $opportunity = Opportunity::with(['organization','contact'])->find($request->opportunity_id);
    
            if ($opportunity) {
                $contactFullName = trim(
                    ($opportunity->contact->first_name ?? '').' '.($opportunity->contact->last_name ?? '')
                );
    
                $prefill = [
                    'opportunity_id'     => $opportunity->id,
                    'opportunity_name'   => $opportunity->name ?? $opportunity->subject ?? '', // ← اضافه شد
                    'sales_opportunity'  => $opportunity->name ?? $opportunity->subject ?? '', // ← اگر ستون‌تان این نام را می‌خواهد
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
    \Log::info('Creating Proforma (global discount/tax)', [
        'stage' => $request->proforma_stage,
        'data'  => $request->all(),
    ]);

    try {
        // -------------------- 1) HARD PRE-CLEAN: اعداد فارسی/جداکننده‌ها قبل از validate --------------------
        $in = $request->all();

        $removeJunk = static function ($v) {
            if ($v === null || $v === '') return $v;
            $v = (string) $v;

            // حذف فاصله‌های نامرئی/غیراستاندارد
            $v = str_replace(
                ["\u{200C}", "\u{200B}", "\u{00A0}", "\u{FEFF}", " "],
                '',
                $v
            );

            // تبدیل ارقام فارسی/عربی و جداکننده‌ها
            $mapFrom = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩','٬','٫','،',','];
            $mapTo   = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','','.','',''];
            $v = str_replace($mapFrom, $mapTo, $v);

            // نگه‌داشتن فقط عدد/نقطه/منفی
            $v = preg_replace('/[^0-9.\-]/', '', $v) ?? '';

            // اگر چند نقطه بود، به یک نقطه تقلیل یابد
            if (substr_count($v, '.') > 1) {
                $first = strpos($v, '.');
                $v = substr($v, 0, $first + 1) . str_replace('.', '', substr($v, $first + 1));
            }

            return ($v === '' || $v === '-') ? null : $v;
        };

        // فیلدهای عددی سراسری
        foreach (['global_discount_value','global_tax_value','total_subtotal','total_discount','total_tax','total_amount'] as $f) {
            if (array_key_exists($f, $in)) {
                $in[$f] = $removeJunk($in[$f]);
            }
        }

        // فیلدهای عددی محصولات
        if (!empty($in['products']) && is_array($in['products'])) {
            $cleanProducts = [];
            foreach ($in['products'] as $k => $p) {
                $p = is_array($p) ? $p : (array) $p;
                foreach (['price','quantity','discount_value','tax_value'] as $nf) {
                    if (array_key_exists($nf, $p)) {
                        $p[$nf] = $removeJunk($p[$nf]);
                    }
                }
                $cleanProducts[$k] = $p;
            }
            $in['products'] = $cleanProducts;
        }

        $request->replace($in);
        // -------------------- END PRE-CLEAN --------------------

        // -------------------- 2) VALIDATE --------------------
        $validated = $request->validate([
            'subject'           => 'required|string|max:255',
            'proforma_date'     => 'nullable|string',
            'contact_name'      => 'nullable|string|max:255',
            'proforma_stage'    => ['required', Rule::in(array_keys(config('proforma.stages')))],
            'organization_name' => 'nullable|string|max:255',
            'address_type'      => 'required|in:invoice,product',
            'customer_address'  => 'nullable|string',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'assigned_to'       => 'required|exists:users,id',
            'opportunity_id'    => 'nullable|exists:opportunities,id',

            // محصولات
            'products'                 => 'nullable|array',
            'products.*.name'          => 'nullable|string|max:255',
            'products.*.quantity'      => 'nullable|numeric|min:0.01',
            'products.*.price'         => 'nullable|numeric|min:0',
            'products.*.unit'          => 'nullable|string|max:50',
            // (چون قرار است تخفیف/مالیات سراسری باشد، فیلدهای سطری اجباری نیستند)
            'products.*.discount_type' => 'nullable|in:percentage,fixed',
            'products.*.discount_value'=> 'nullable|numeric|min:0',
            'products.*.tax_type'      => 'nullable|in:percentage,fixed',
            'products.*.tax_value'     => 'nullable|numeric|min:0',

            // کنترل‌های سراسری (اختیاری)
            'global_discount_type' => 'nullable|in:none,percentage,fixed',
            'global_discount_value'=> 'nullable|numeric|min:0',
            'global_tax_type'      => 'nullable|in:none,percentage,fixed',
            'global_tax_value'     => 'nullable|numeric|min:0',
        ]);
        \Log::debug('✅ Passed validation (store)', $validated);

        // -------------------- 3) تاریخ ورودی → میلادی (پشتیبانی هر دو فرمت) --------------------
        // سناریوها:
        // - اگر خالی بود: امروز ذخیره می‌شود.
        // - اگر "YYYY-MM-DD" (میلادی) بود: مستقیم Carbon می‌شود.
        // - اگر "YYYY/MM/DD" یا «YYYY-MM-DD» (جلالی) بود: به میلادی تبدیل می‌شود.
        $miladiDate = null;
        try {
            $rawDate = trim((string)($validated['proforma_date'] ?? ''));
            // Normalize unicode digits (Persian/Arabic) to ASCII and strip ZW chars
            $rawDate = preg_replace('/\x{200C}|\x{200B}|\x{00A0}|\x{FEFF}/u', '', $rawDate);
            $rawDate = str_replace(
                ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],
                ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'],
                $rawDate
            );
            if ($rawDate === '') {
                // پیش‌فرض: امروز
                $miladiDate = \Carbon\Carbon::today();
            } else {
                $normalized = preg_replace('/\s+/', '', $rawDate) ?? '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
                    $year = (int) substr($normalized, 0, 4);
                    if ($year >= 1300 && $year <= 1599) {
                        // جلالی با خط‌تیره
                        $miladiDate = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', str_replace('-', '/', $normalized))->toCarbon();
                    } else {
                        // میلادی: YYYY-MM-DD
                        $miladiDate = \Carbon\Carbon::createFromFormat('Y-m-d', $normalized)->startOfDay();
                    }
                } else {
                    // تلاش برای جلالی: YYYY/MM/DD (یا با - که به / تبدیل می‌کنیم)
                    $jalaliDate = str_replace('-', '/', $normalized);
                    if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $jalaliDate)) {
                        $miladiDate = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon();
                    } else {
                        return back()->withInput()->with('error', 'تاریخ وارد شده معتبر نیست.');
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('❌ Invalid Date (store)', ['exception' => $e->getMessage(), 'raw' => $validated['proforma_date'] ?? null]);
            return back()->withInput()->with('error', 'تاریخ وارد شده معتبر نیست.');
        }

        // -------------------- 4) DB & محاسبات --------------------
        DB::beginTransaction();

        $proforma = Proforma::create([
            'subject'           => $validated['subject'],
            'proforma_date'     => $miladiDate,
            'contact_name'      => $validated['contact_name']      ?? null,
            'proforma_stage'    => $validated['proforma_stage'],
            'organization_name' => $validated['organization_name'] ?? null,
            'address_type'      => $validated['address_type'],
            'customer_address'  => $validated['customer_address']  ?? null,
            'city'              => $validated['city']              ?? null,
            'state'             => $validated['state']             ?? null,
            'assigned_to'       => $validated['assigned_to'],
            'opportunity_id'    => $validated['opportunity_id']    ?? null,
            'total_amount'      => 0, // بعداً آپدیت می‌کنیم
        ]);
        \Log::info('📄 Proforma Created', ['id' => $proforma->id]);

        // استراتژی: تخفیف/مالیات سراسری روی مجموع اقلام اعمال می‌شود
        $subtotal = 0.0;

        if (!empty($validated['products'])) {
            foreach ($validated['products'] as $item) {
                $unitPrice = (float) ($item['price']    ?? 0);
                $quantity  = (float) ($item['quantity'] ?? 0);
                $lineBase  = $unitPrice * $quantity;

                // جمع پایه
                $subtotal += $lineBase;

                // ذخیره آیتم؛ تخفیف/مالیات سطری را صفر می‌گذاریم تا دوباره اعمال نشود
                $proforma->items()->create([
                    'name'            => $item['name'] ?? '',
                    'quantity'        => $quantity,
                    'unit_price'      => $unitPrice,
                    'unit_of_use'     => $item['unit'] ?? '',
                    'total_price'     => $lineBase,
                    'discount_type'   => null,
                    'discount_value'  => 0,
                    'discount_amount' => 0,
                    'tax_type'        => null,
                    'tax_value'       => 0,
                    'tax_amount'      => 0,
                    'total_after_tax' => $lineBase, // فعلاً برابر با خط پایه
                ]);
            }
        }

        // تخفیف/مالیات سراسری
        $gDiscType  = $validated['global_discount_type'] ?? 'none';
        $gDiscVal   = (float) ($validated['global_discount_value'] ?? 0);
        $gTaxType   = $validated['global_tax_type'] ?? 'none';
        $gTaxVal    = (float) ($validated['global_tax_value'] ?? 0);

        $globalDiscount = 0.0;
        if ($gDiscType === 'percentage') {
            $globalDiscount = ($subtotal * $gDiscVal) / 100;
        } elseif ($gDiscType === 'fixed') {
            $globalDiscount = $gDiscVal;
        }
        // جلوگیری از منفی شدن
        $globalDiscount = min($globalDiscount, $subtotal);
        $afterDiscount  = $subtotal - $globalDiscount;

        $globalTax = 0.0;
        if ($gTaxType === 'percentage') {
            $globalTax = ($afterDiscount * $gTaxVal) / 100;
        } elseif ($gTaxType === 'fixed') {
            $globalTax = $gTaxVal;
        }
        $globalTax = max($globalTax, 0);

        $grandTotal = $afterDiscount + $globalTax;

       // تبدیل safe به عدد صحیح (ریال)
        $toInt = fn($x) => (int) round((float) $x, 0);

        // اگر enum دیتابیس 'none' نداره، none => null
        $dbDiscType = ($gDiscType === 'none') ? null : $gDiscType;
        $dbTaxType  = ($gTaxType  === 'none') ? null : $gTaxType;

        $proforma->update([
            'items_subtotal'        => $toInt($subtotal),

            'global_discount_type'  => $dbDiscType,
            'global_discount_value' => $toInt($gDiscVal),        // اگر درصد بود، همون عدد درصد ذخیره می‌شود
            'global_discount_amount'=> $toInt($globalDiscount),  // مبلغ واقعی تخفیف اعمال‌شده

            'global_tax_type'       => $dbTaxType,
            'global_tax_value'      => $toInt($gTaxVal),         // اگر درصد بود، همون عدد درصد ذخیره می‌شود
            'global_tax_amount'     => $toInt($globalTax),       // مبلغ واقعی مالیات اعمال‌شده

            'total_amount'          => $toInt($grandTotal),
        ]);

        \Log::debug('🧮 Totals (global mode)', [
            'subtotal'        => $subtotal,
            'global_discount' => $globalDiscount,
            'after_discount'  => $afterDiscount,
            'global_tax'      => $globalTax,
            'grand_total'     => $grandTotal,
        ]);

        // نوتیفیکیشن «ارجاع به»
        $proforma->notifyIfAssigneeChanged(null);

        // اتوماسیون "ارسال برای تاییدیه"
        if ($proforma->proforma_stage === 'send_for_approval') {
            $condition = AutomationCondition::where('model_type', 'Proforma')
                ->where('field', 'proforma_stage')
                ->where('operator', '=')
                ->where('value', 'send_for_approval')
                ->first();

            if ($condition) {
                \Log::info('🔔 Automation condition matched for send_for_approval');
                $sender = \Auth::user();
                foreach ([$condition->approver1_id, $condition->approver2_id] as $approverId) {
                    if ($approverId && ($user = User::find($approverId))) {
                        $user->notify(new \App\Notifications\FormApprovalNotification($proforma, $sender));
                    }
                }
            }
        }

        DB::commit();

        // اجرای هر Rule دیگری که به state پایدار نیاز دارد
        $proforma->refresh();
        $this->runAutomationRulesIfNeeded($proforma);

        return redirect()->route('sales.proformas.index')->with('success', 'پیش‌فاکتور با موفقیت ایجاد شد.');
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('❌ Error Creating Proforma:', ['exception' => $e->getMessage()]);
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
        // 1) قانون اصلی: فقط در پیش‌نویس
        if (! $proforma->canEdit()) {
            return redirect()
                ->route('sales.proformas.show', $proforma)
                ->with('alert_error', 'فقط در وضعیت «پیش‌نویس» قابل ویرایش است.');
        }
    
        // 2) احراز مجوز (ادمین/کاربرِ assign‌شده و ...)
        $this->authorize('update', $proforma);
    
        // 3) لود داده‌های لازم برای فرم
        $proforma->load('items');
        $users         = User::select('id','name')->get();
        $organizations = Organization::select('id','name')->get();
        $contacts      = Contact::select('id','first_name','last_name')->get();
        $opportunities = Opportunity::select('id','title')->get();
        $products      = Product::where('is_active', true)->orderBy('name')->get();
        $proformaStages = config('proforma.stages');
    
        return view('sales.proformas.edit', compact(
            'proforma','users','organizations','contacts','opportunities','products','proformaStages'
        ));
    }
    

    public function update(Request $request, Proforma $proforma)
    {
        Log::debug('✏️ Update Request Payload:', $request->all());
        $this->authorize('update', $proforma);
        if (! $proforma->canEdit()) {
            return back()->with('error', 'فقط در وضعیت پیش‌نویس قابل ویرایش است.');
        }

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
    
            // تاریخ ورودی در ویرایش → میلادی (پشتیبانی هر دو فرمت + نگه‌داشتن مقدار قبلی در صورت خالی)
            $miladiDate = $proforma->proforma_date; // پیش‌فرض: مقدار قبلی را نگه دار
            $rawDateUpd = trim((string)($validated['proforma_date'] ?? ''));
            if ($rawDateUpd !== '') {
                try {
                    // نرمال‌سازی ارقام و کاراکترهای نامرئی
                    $rawDateUpd = preg_replace('/\x{200C}|\x{200B}|\x{00A0}|\x{FEFF}/u', '', $rawDateUpd);
                    $rawDateUpd = str_replace(
                        ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],
                        ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'],
                        $rawDateUpd
                    );
                    $normalizedUpd = preg_replace('/\s+/', '', $rawDateUpd) ?? '';
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalizedUpd)) {
                        $year = (int) substr($normalizedUpd, 0, 4);
                        if ($year >= 1300 && $year <= 1599) {
                            // جلالی با خط تیره
                            $miladiDate = Jalalian::fromFormat('Y/m/d', str_replace('-', '/', $normalizedUpd))->toCarbon();
                        } else {
                            // میلادی با خط تیره
                            $miladiDate = \Carbon\Carbon::createFromFormat('Y-m-d', $normalizedUpd)->startOfDay();
                        }
                    } else {
                        // تلاش برای جلالی با اسلش
                        $jalaliDateString = str_replace('-', '/', $normalizedUpd);
                        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $jalaliDateString)) {
                            $miladiDate = Jalalian::fromFormat('Y/m/d', $jalaliDateString)->toCarbon();
                        } else {
                            return back()->withInput()->with('error', 'تاریخ وارد شده معتبر نیست.');
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Invalid Jalali/Gregorian Date on Update:', ['exception' => $e->getMessage(), 'raw' => $validated['proforma_date']]);
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
    \Log::info('🟢 Destroy called', [
        'route_parameters' => request()->route()->parameters(),
        'proforma_id'      => $proforma->id ?? null,
        'proforma_number'  => $proforma->number ?? null,
    ]);

    // تصمیم نهایی با Policy
    try {
        $this->authorize('delete', $proforma);
        \Log::info('✅ Authorization passed', ['proforma_id' => $proforma->id]);

        \DB::transaction(function () use ($proforma) {
            \Log::info('🛠 Deleting relations', ['proforma_id' => $proforma->id]);

            if (method_exists($proforma, 'items')) {
                $deleted = $proforma->items()->delete();
                \Log::info('🔸 Items deleted', ['count' => $deleted]);
            }
            if (method_exists($proforma, 'approvals')) {
                $deleted = $proforma->approvals()->delete();
                \Log::info('🔸 Approvals deleted', ['count' => $deleted]);
            }

            $proforma->delete();
            \Log::info('🗑 Proforma deleted (soft)', ['proforma_id' => $proforma->id]);
        });

        return redirect()
            ->route('sales.proformas.index')
            ->with('success', 'پیش‌فاکتور با موفقیت حذف شد.');
    } catch (\Throwable $e) {
        \Log::error('❌ Proforma delete failed', [
            'proforma_id' => $proforma->id ?? null,
            'error'       => $e->getMessage(),
            'trace'       => $e->getTraceAsString(),
        ]);

        return back()->with('error', 'خطا در حذف پیش‌فاکتور. لطفاً دوباره تلاش کنید.');
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

        public function reject(Proforma $proforma)
    {
        $this->authorize('approve', $proforma); // همان policy که برای approve استفاده می‌کنی

        try {
            \DB::transaction(function () use ($proforma) {
                $userId = auth()->id();

                // اگر قبلاً نهایی شده (approved/rejected) ادامه نده
                if (in_array($proforma->approval_stage, ['approved','rejected'], true)) {
                    throw new \RuntimeException('این پیش‌فاکتور قبلاً نهایی شده است.');
                }

                // approvals را با لاک بخوان
                $approvals = $proforma->approvals()
                    ->with('approver')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                // مرحله‌ی در انتظار
                $pending = $approvals->firstWhere('status', 'pending');
                if (! $pending) {
                    throw new \RuntimeException('هیچ مرحله‌ی در انتظاری برای رد وجود ندارد.');
                }

                // حالت 1: تاییدکننده/ردکننده اصلی همین pending است
                $current = $approvals->firstWhere('user_id', $userId);

                // حالت 2: اگر اصلی نبود، بررسی emergency approver برای همان pending
                $asEmergency = false;
                if (! $current) {
                    $rule = $proforma->automationRule()->first();
                    if ($rule && (int) $rule->emergency_approver_id === (int) $userId) {
                        $current = $pending;   // اجازه بده اضطراری همان pending را رد کند
                        $asEmergency = true;
                    }
                }

                if (! $current) {
                    throw new \RuntimeException('شما مجاز به رد این پیش‌فاکتور نیستید.');
                }

                // فقط روی pending می‌توان تصمیم گرفت
                if ($current->status !== 'pending') {
                    throw new \RuntimeException('برای این مرحله قبلاً تصمیم‌گیری شده است.');
                }

                // رعایت ترتیب مراحل (اگر قبل از این رکورد، آیتمی هنوز approved نشده، بلوکه)
                $idx     = $approvals->search(fn ($a) => (int) $a->id === (int) $current->id);
                $blocker = $approvals->take($idx)->first(fn ($a) => $a->status !== 'approved');
                if ($blocker) {
                    $who = optional($blocker->approver)->name ?: ('کاربر #' . $blocker->user_id);
                    throw new \RuntimeException("رد امکان‌پذیر نیست؛ پیش‌فاکتور در انتظار تصمیم {$who} است.");
                }

                // رد همین مرحله
                $current->update([
                    'status'      => 'rejected',
                    'approved_at' => now(),
                    'approved_by' => $userId,
                ]);

                // ست کردن وضعیت کلی پروفورما به رد‌شده
                $proforma->fill([
                    'approval_stage' => 'rejected',
                    'proforma_stage' => 'rejected',
                ])->save();

                // پاک کردن تمام pendingهای دیگر تا فرایند متوقف شود
                $proforma->approvals()
                    ->where('status', 'pending')
                    ->delete();

                \Log::info('⛔ Proforma rejected', [
                    'proforma_id' => $proforma->id,
                    'by_user'     => $userId,
                    'step'        => (int) ($current->step ?? 1),
                    'as_emergency'=> $asEmergency,
                ]);
            });

            return back()->with('success', 'پیش‌فاکتور با موفقیت رد شد.');

        } catch (\Throwable $e) {
            \Log::error('❌ Proforma reject failed', [
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
