<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Proforma;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Activity as CrmActivity;
use App\Models\User;
use App\Models\Product;
use App\Models\AutomationRule;
use App\Models\AutomationRuleApprover;
use App\Models\AutomationCondition;
use App\Notifications\FormApprovalNotification;
use App\Models\Approval;
use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;
use App\Helpers\NotificationHelper;
use App\Helpers\DateHelper;
use Spatie\Activitylog\Models\Activity;
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
        $query = Proforma::visibleFor(auth()->user(), 'proformas')
            ->with(['organization', 'contact', 'opportunity', 'assignedTo'])
            ->orderByDesc('proforma_date')
            ->orderByDesc('created_at');

        // جست‌وجو
        $query->when($search !== '', function ($q) use ($search) {
            $q->where(function ($qq) use ($search) {
                $qq->where('subject', 'like', "%{$search}%")
                   ->orWhereHas('organization', function ($q2) use ($search) {
                       $q2->where('name', 'like', "%{$search}%");
                   })
                   ->orWhereHas('contact', function ($q3) use ($search) {
                       $q3->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name',  'like', "%{$search}%");
                       // اگر مدل contact ستون full_name دارد، می‌توانید این را هم اضافه کنید:
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
            'submit_mode' => $request->input('submit_mode'),
            'data'  => $request->all(),
        ]);

        try {
            // -------------------- 1) HARD PRE-CLEAN: اعداد فارسی/جداکننده‌ها قبل از validate --------------------
            $in = $request->all();

            // Pre-normalize Persian/Arabic digits and separators to ASCII
            $normalizeFaNum = static function ($v) {
                if ($v === null || $v === '') return $v;
                $s = (string) $v;
                $from = [
                    '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹', // Persian digits
                    '٠','١','٢','٣','٤','٥','٦','٧','٨','٩', // Arabic-Indic digits
                    '٬','،',',',                              // thousand separators
                    '٫'                                       // Arabic decimal
                ];
                $to = [
                    '0','1','2','3','4','5','6','7','8','9',
                    '0','1','2','3','4','5','6','7','8','9',
                    '','','',
                    '.'
                ];
                return str_replace($from, $to, $s);
            };

            // Apply pre-normalization to known numeric fields and date
            foreach (['global_discount_value','global_tax_value','total_subtotal','total_discount','total_tax','total_amount','proforma_date'] as $f) {
                if (array_key_exists($f, $in)) {
                    $in[$f] = $normalizeFaNum($in[$f]);
                }
            }
            if (!empty($in['products']) && is_array($in['products'])) {
                foreach ($in['products'] as $k => $p) {
                    if (!is_array($p)) $p = (array) $p;
                    foreach (['price','quantity','discount_value','tax_value'] as $nf) {
                        if (array_key_exists($nf, $p)) {
                            $p[$nf] = $normalizeFaNum($p[$nf]);
                        }
                    }
                    $in['products'][$k] = $p;
                }
            }

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
                $mapFrom = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩','٬','،',',','٫'];
                $mapTo   = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','','','','.'];
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
                'submit_mode'       => ['required', Rule::in(['draft','send_for_approval'])],
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
            $submitMode = $validated['submit_mode'];
            $targetStage = $submitMode === 'send_for_approval' ? 'send_for_approval' : 'draft';

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
                        // تلاش برای جلالی: YYYY/MM/DD (یا با - که به / تبدیل کنیم)
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
                'proforma_stage'    => $targetStage,
                'approval_stage'    => $targetStage,
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

            // اگر enum دیتابیس 'none' ندارد، none => null
            $dbDiscType = ($gDiscType === 'none') ? null : $gDiscType;
            $dbTaxType  = ($gTaxType  === 'none') ? null : $gTaxType;

            $proforma->update([
                'items_subtotal'        => $toInt($subtotal),

                'global_discount_type'  => $dbDiscType,
                'global_discount_value' => $toInt($gDiscVal),        // اگر درصد بود، همان عدد درصد ذخیره می‌شود
                'global_discount_amount'=> $toInt($globalDiscount),  // مبلغ واقعی تخفیف اعمال‌شده

                'global_tax_type'       => $dbTaxType,
                'global_tax_value'      => $toInt($gTaxVal),         // اگر درصد بود، همان عدد درصد ذخیره می‌شود
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

            // اتومیشن "ارسال برای تأییدیه"
            if ($proforma->proforma_stage === 'send_for_approval') {
                $condition = AutomationCondition::where('model_type', 'Proforma')
                    ->where('field', 'proforma_stage')
                    ->where('operator', '=')
                    ->where('value', 'send_for_approval')
                    ->first();

                if ($condition) {
                    \Log::info('🔐 Automation condition matched for send_for_approval');
                    $sender = \Auth::user();
                    foreach ([$condition->approver1_id, $condition->approver2_id] as $approverId) {
                        if ($approverId && ($user = User::find($approverId))) {
                            $user->notify(new \App\Notifications\FormApprovalNotification($proforma, $sender));
                        }
                    }
                }
            }

            // Log a CRM activity on the linked opportunity so stage-change guard sees a recent action.
            if (!empty($proforma->opportunity_id)) {
                try {
                    $opportunity = Opportunity::find($proforma->opportunity_id);
                    if ($opportunity) {
                        $creatorId  = auth()->id() ?: $proforma->assigned_to ?: $opportunity->assigned_to;
                        $assigneeId = $opportunity->assigned_to ?: $proforma->assigned_to ?: $creatorId;

                        $activity = CrmActivity::create([
                            'subject'        => 'proforma_created',
                            'start_at'       => now(),
                            'due_at'         => now(),
                            'assigned_to_id' => $assigneeId ?: $creatorId,
                            'related_type'   => Opportunity::class,
                            'related_id'     => $opportunity->id,
                            'status'         => 'completed',
                            'priority'       => 'normal',
                            'description'    => 'Automatically logged after proforma issuance.',
                            'is_private'     => false,
                            'created_by_id'  => $creatorId ?: $assigneeId,
                            'updated_by_id'  => $creatorId ?: $assigneeId,
                        ]);

                        if (method_exists($opportunity, 'markFirstActivity')) {
                            $activityTime = $activity->start_at ?? $activity->created_at ?? now();
                            $opportunity->markFirstActivity($activityTime);
                        }

                        // Spatie activity log for opportunity updates tab
                        $properties = [
                            'proforma_id'     => $proforma->id,
                            'proforma_number' => $proforma->proforma_number ?? $proforma->number ?? null,
                        ];

                        activity()
                            ->performedOn($opportunity)
                            ->causedBy(auth()->user())
                            ->event('proforma_created')
                            ->withProperties(array_filter($properties, fn($value) => $value !== null))
                            ->log('پیش‌فاکتور برای این فرصت ثبت شد');
                    }
                } catch (\Throwable $activityException) {
                    \Log::warning('proforma_activity_auto_create_failed', [
                        'proforma_id'    => $proforma->id,
                        'opportunity_id' => $proforma->opportunity_id,
                        'error'          => $activityException->getMessage(),
                    ]);
                }
            }

            DB::commit();

            $createdAt = now();
            $creatorName = auth()->user()->name ?? 'سیستم';
            $createdDescription = $creatorName . ' این پیش‌فاکتور را در تاریخ ' . DateHelper::toJalali($createdAt, 'H:i Y/m/d') . ' ایجاد کرد.';

            activity('proforma')
                ->performedOn($proforma)
                ->causedBy(auth()->user())
                ->event('created')
                ->withProperties(['message' => $createdDescription])
                ->log($createdDescription);

            // اجرای هر Rule دیگری که به state پایدار نیاز دارد
            $proforma->refresh();
            $this->runAutomationRulesIfNeeded($proforma);

            $successMessage = 'پیش‌فاکتور با موفقیت ایجاد شد.';

            if ($request->filled('return_to') && $this->isInternalUrl($request->input('return_to'), $request)) {
                return redirect($request->input('return_to'))->with('success', $successMessage);
            }

            return redirect()->route('sales.proformas.index')->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ Error Creating Proforma:', ['exception' => $e->getMessage()]);
            return back()->withInput()->with('error', 'خطا در ایجاد پیش‌فاکتور. لطفاً دوباره تلاش کنید.');
        }
    }






    public function show(Proforma $proforma)
    {
        $proforma->load([
            'organization', 'contact', 'opportunity', 'assignedTo',
            'items',
            'approvals.approver',   // O"OñOUO O3UOO3O¦U. U,O_UOU.UOU? approvals
            'opportunity.documents',
            'notes.user',
        ]);

        // 1) OU_Oñ O_Oñ OªO_U^U, approvals OñUcU^OñO_UO O"O U^OO1UOO¦ A®pendingA¯ O"OñOUO UcOOñO"Oñ O-OOOñ U^OªU^O_ O_OOñO_OO UØU.OU+ OñO OO3O¦U?OO_UØ UcU+
        $approval = $proforma->approvals()
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        $pendingApproval = $proforma->approvals
            ->where('status', 'pending')
            ->first();

        $pendingApproverName = $pendingApproval?->approver?->name;
        $pending             = $pendingApproval;

        $stageKey   = $proforma->approval_stage ?? $proforma->proforma_stage ?? null;
        $stageLabel = \App\Helpers\FormOptionsHelper::proformaStages()[$stageKey] ?? 'U+OU.O"rOæ';

        try {
            $shamsiDate = ($proforma->proforma_date instanceof \Carbon\Carbon)
                ? Jalalian::fromCarbon($proforma->proforma_date)->format('Y/m/d')
                : 'U+OU.O"rOæ';
        } catch (\Throwable $e) {
            $shamsiDate = 'U+OU.O"rOæ';
        }

        // 2) O_Oñ O¨UOOñ OUOU+ OæU^OñO¦OO OOý U,U^OU+UOU+ OO¦U^U.OO3UOU^U+ U.O-OO3O"UØ UcU+ UcUØ U+U^O"O¦ U+UØ UcO3UO OO3O¦
        if (empty($pendingApproverName)) {
            $stage = $proforma->approval_stage ?? $proforma->proforma_stage;

            if ($stage === 'send_for_approval') {
                $rule = AutomationRule::with(['approvers.user'])
                    ->where('proforma_stage', 'send_for_approval')
                    ->first();

                if ($rule) {
                    $pendingApproverId = null;

                    if (empty($proforma->first_approved_by)) {
                        // UØU+U^Oý U.OñO-U,UØ OU^U, O¨OœUOUOO_ OU+OªOU. U+O'O_UØ
                        $pendingApproverId = optional($rule->approvers->firstWhere('priority', 1))->user_id;
                    } elseif (empty($proforma->approved_by)) {
                        // U.OñO-U,UØ OU^U, O¨OœUOUOO_ O'O_UØ OU.O U+UØOUOUO U+O'O_UØ
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

        $approvalViewData = $this->buildProformaApprovalViewData($proforma);
        if (!empty($pendingApproverName) && empty($approvalViewData['pendingApproverName'] ?? null)) {
            $approvalViewData['pendingApproverName'] = $pendingApproverName;
        }

        $updates = Activity::with('causer')
            ->where('subject_type', Proforma::class)
            ->where('subject_id', $proforma->id)
            ->latest()
            ->get();

        $documents = optional($proforma->opportunity)->documents ?? collect();
        $allUsers  = User::whereNotNull('username')->get();

        return view(
            'sales.proformas.show',
            array_merge(
                compact('proforma', 'approval', 'pendingApproverName', 'pending', 'stageKey', 'stageLabel', 'shamsiDate', 'updates', 'documents', 'allUsers'),
                $approvalViewData
            )
        );

    }

    public function storeNote(Request $request, Proforma $proforma)
    {
        $validated = $request->validate([
            'content'  => ['required', 'string', 'max:2000'],
            'mentions' => ['nullable'],
        ]);

        $note = $proforma->notes()->create([
            'body'    => $validated['content'],
            'user_id' => $request->user()->id,
        ]);

        $formTitle = trim((string) ($proforma->getNotificationTitle() ?? $proforma->subject ?? ''));
        if ($formTitle === '') {
            $formTitle = $proforma->id ? ('پیش‌فاکتور #' . $proforma->id) : 'پیش‌فاکتور';
        }

        $usernames = $this->extractMentions($validated['mentions'] ?? null, $note->body);

        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('username', $usernames)->get();
            foreach ($mentionedUsers as $user) {
                try {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $context = [
                        'note_body'            => $note->body,
                        'mentioned_user'       => $user,
                        'mentioned_user_name'  => $user->name,
                        'context_label'        => 'پیش‌فاکتور',
                        'form_title'           => $formTitle,
                        'actor'                => auth()->user(),
                        'url'                  => route('sales.proformas.show', $proforma->id) . '#note-' . $note->id,
                    ];
                    $router->route('notes', 'note.mentioned', $context, [$user]);
                } catch (\Throwable $e) {
                    // ignore notification failures for mentions
                }
            }
        }

        $url = route('sales.proformas.show', $proforma->id) . '#note-' . $note->id;

        if ($request->ajax()) {
            return response()->json(['success' => true, 'url' => $url, 'note_id' => $note->id]);
        }

        return redirect($url)->with('success', 'یادداشت جدید با موفقیت ثبت شد.');
    }

    public function preview(Proforma $proforma)
    {
        $proforma->load(['organization','contact','items.product']);
        return view('sales.proformas.preview', compact('proforma'));
    }
    
    public function edit(Proforma $proforma)
    {
        // 1) فقط در وضعیت «پیش‌نویس» قابل ویرایش است
        if (! $proforma->canEdit()) {
            return redirect()
                ->route('sales.proformas.show', $proforma)
                ->with('alert_error', 'This proforma is locked (finalized/converted) and cannot be edited.');
        }
    
        // 2) احراز مجوز (ادمین/کاربر ارجاع‌گرفته و ...)
        $this->authorize('update', $proforma);
    
        // 3) لود داده‌های لازم برای فرم
        $proforma->load(['items.product']);
        $users          = User::select('id','name')->get();
        $organizations  = Organization::select('id','name')->get();
        $contacts       = Contact::select('id','first_name','last_name')->get();
        // Opportunities table uses 'name' as the title field; alias to keep downstream usage intact
        $opportunities  = Opportunity::select(['id', DB::raw("name as title")])->get();
        $products       = Product::where('is_active', true)->orderBy('name')->get();
        $proformaStages = config('proforma.stages');
    
        return view('sales.proformas.edit', compact(
            'proforma','users','organizations','contacts','opportunities','products','proformaStages'
        ));
    }
    
    public function update(Request $request, Proforma $proforma)
    {
        Log::debug('✏️ باردادهٔ به‌روزرسانی:', $request->all());
    
        $this->authorize('update', $proforma);
    
        if (! $proforma->canEdit()) {
            return back()->with('error', 'This proforma is locked (finalized/converted) and cannot be edited.');
        }

        $this->normalizeProductPayload($request);
    
        $wasInApprovalFlow = $proforma->hasStartedApprovalFlow();

        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'proforma_date' => 'nullable|string',
                'contact_name' => 'nullable|string|max:255',
                'submit_mode' => ['required', Rule::in(['draft','send_for_approval'])],
                'edit_reason' => 'required|string|max:2000',
                'organization_name' => 'nullable|string|max:255',
                'address_type' => 'required|in:invoice,product',
                'customer_address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'assigned_to' => 'required|exists:users,id',
                'opportunity_id' => 'nullable|exists:opportunities,id',
    
                // محصولات الزامی نیستند
                'products' => 'nullable|array',
                'products.*.name' => 'nullable|string|max:255',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
                'products.*.unit' => 'nullable|string|max:50',
                'products.*.discount_type' => 'nullable|in:percentage,fixed',
                'products.*.discount_value' => 'nullable|numeric|min:0',
                'products.*.tax_type' => 'nullable|in:percentage,fixed',
                'products.*.tax_value' => 'nullable|numeric|min:0',
            ]);
            Log::debug('✅ اعتبارسنجی به‌روزرسانی با موفقیت انجام شد:', $validated);
            $submitMode = $validated['submit_mode'];
            $targetStage = $submitMode === 'send_for_approval' ? 'send_for_approval' : 'draft';
    
            // تبدیل تاریخ ورودی در ویرایش → میلادی (پشتیبانی هر دو فرمت + حفظ مقدار قبلی اگر ورودی خالی باشد)
            $miladiDate = $proforma->proforma_date; // پیش‌فرض: مقدار قبلی را نگه دار
            $rawDateUpd = trim((string)($validated['proforma_date'] ?? ''));
            if ($rawDateUpd !== '') {
                try {
                    // پاک‌سازی نویسه‌های نامرئی و نرمال‌سازی ارقام فارسی/عربی به انگلیسی
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
                    Log::error('❌ تاریخ نامعتبر (جلالی/میلادی) در به‌روزرسانی:', ['exception' => $e->getMessage(), 'raw' => $validated['proforma_date']]);
                    return back()->withInput()->with('error', 'تاریخ وارد شده معتبر نیست.');
                }
            }
    
            DB::beginTransaction();
            $editReason = trim((string) ($validated['edit_reason'] ?? ''));
            $noteCreatedAt = now();
            $reasonBody = "دلیل ویرایش توسط " . (auth()->user()->name ?? 'کاربر') . ' در ' . DateHelper::toJalali($noteCreatedAt, 'H:i Y/m/d') . ":\n" . $editReason;
            $proforma->notes()->create([
                'body'    => $reasonBody,
                'user_id' => $request->user()->id,
            ]);
    
            $totalAmount   = 0;
            $proformaItems = [];
    
            foreach ($validated['products'] ?? [] as $item) {
                $quantity      = floatval($item['quantity']);
                $unitPrice     = floatval($item['price']);
                $discountValue = floatval($item['discount_value'] ?? 0);
                $taxValue      = floatval($item['tax_value'] ?? 0);
    
                // محاسبه تخفیف
                $discountAmount = ($item['discount_type'] === 'percentage')
                    ? ($unitPrice * $discountValue / 100)
                    : $discountValue;
    
                $priceAfterDiscount = $unitPrice - $discountAmount;
    
                // محاسبه مالیات
                $taxAmount = ($item['tax_type'] === 'percentage')
                    ? ($priceAfterDiscount * $taxValue / 100)
                    : $taxValue;
    
                $totalPrice    = $unitPrice * $quantity;
                $totalAfterTax = ($priceAfterDiscount + $taxAmount) * $quantity;
    
                $totalAmount += $totalAfterTax;
    
                $proformaItems[] = [
                    'name'            => $item['name'],
                    'quantity'        => $quantity,
                    'unit_price'      => $unitPrice,
                    'unit_of_use'     => $item['unit'],
                    'total_price'     => $totalPrice,
                    'discount_type'   => $item['discount_type'] ?? null,
                    'discount_value'  => $discountValue,
                    'discount_amount' => $discountAmount,
                    'tax_type'        => $item['tax_type'] ?? null,
                    'tax_value'       => $taxValue,
                    'tax_amount'      => $taxAmount,
                    'total_after_tax' => $totalAfterTax,
                ];
            }
    
            $oldAssignedTo = $proforma->assigned_to;

            $proforma->update([
                'subject'          => $validated['subject'],
                'proforma_date'    => $miladiDate,
                'contact_name'     => $validated['contact_name'],
                'proforma_stage'   => $targetStage,
                'approval_stage'   => $targetStage,
                'organization_name'=> $validated['organization_name'],
                'address_type'     => $validated['address_type'],
                'customer_address' => $validated['customer_address'],
                'city'             => $validated['city'],
                'state'            => $validated['state'],
                'assigned_to'      => $validated['assigned_to'],
                'opportunity_id'   => $validated['opportunity_id'] ?? null,
                'total_amount'     => $totalAmount,
            ]);
            Log::info('Proforma updated:', ['id' => $proforma->id]);

            $proforma->items()->delete();
            if (!empty($proformaItems)) {
                $proforma->items()->createMany($proformaItems);
            }

            $proforma->notifyIfAssigneeChanged($oldAssignedTo);

            if ($targetStage === 'send_for_approval' || $wasInApprovalFlow) {
                $resetTimestamp = now();

                $proforma->fill([
                    'first_approved_by' => null,
                    'first_approved_at' => null,
                    'approved_by'       => null,
                ])->save();

                $proforma->approvals()
                    ->get()
                    ->each(function ($approval) use ($resetTimestamp) {
                        $note = trim(($approval->note ? $approval->note . ' | ' : '') . 'Reset after edit on ' . $resetTimestamp->toDateTimeString());
                        $approval->fill([
                            'status'      => \App\Models\Approval::STATUS_SUPERSEDED,
                            'approved_at' => $approval->approved_at ?? $resetTimestamp,
                            'note'        => $note,
                        ])->save();
                    });

                $proforma->approvals()->where('status', 'pending')->delete();
            }

            // Fire legacy notifications whenever we send for approval
            if ($targetStage === 'send_for_approval') {
                $condition = \App\Models\AutomationCondition::where('model_type', 'Proforma')
                    ->where('field', 'proforma_stage')
                    ->where('operator', '=')
                    ->where('value', 'send_for_approval')
                    ->first();

                if ($condition) {
                    Log::info('Automation condition matched for send_for_approval');
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

            $shouldTriggerAutomation = ($targetStage === 'send_for_approval');

            DB::commit();

            $updatedAt = now();
            $updaterName = auth()->user()->name ?? 'سیستم';
            $updatedDescription = $updaterName . ' این پیش‌فاکتور را در تاریخ ' . DateHelper::toJalali($updatedAt, 'H:i Y/m/d') . ' ویرایش کرد.';

            activity('proforma')
                ->performedOn($proforma)
                ->causedBy(auth()->user())
                ->event('updated')
                ->withProperties(['message' => $updatedDescription])
                ->log($updatedDescription);

            if ($shouldTriggerAutomation) {
                $proforma->refresh();
                $this->runAutomationRulesIfNeeded($proforma);
            }

            $successMessage = 'Proforma updated successfully.';
            if ($targetStage === 'send_for_approval') {
                $successMessage = $wasInApprovalFlow
                    ? 'پیش فاکتور به‌روزرسانی و گردش کار تأیید، مجدداً راه‌اندازی شد.'
                    : 'پیش فاکتور اصلاح و برای تأیید ارسال شد.';
            }

            return redirect()->route('sales.proformas.show', $proforma)->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ خطا در به‌روزرسانی پروفرما:', ['exception' => $e->getMessage()]);
            return back()->withInput()->with('error', 'خطا در به‌روزرسانی پیش‌فاکتور.');
        }
    }
    
    public function destroy(Proforma $proforma)
    {
        \Log::info('🧨 فراخوانی حذف', [
            'route_parameters' => request()->route()->parameters(),
            'proforma_id'      => $proforma->id ?? null,
            'proforma_number'  => $proforma->number ?? null,
        ]);
    
        // تصمیم نهایی با Policy
        try {
            $this->authorize('delete', $proforma);
            \Log::info('✅ مجوز حذف تأیید شد', ['proforma_id' => $proforma->id]);
    
            \DB::transaction(function () use ($proforma) {
                \Log::info('🛠 حذف روابط مرتبط', ['proforma_id' => $proforma->id]);
    
                if (method_exists($proforma, 'items')) {
                    $deleted = $proforma->items()->delete();
                    \Log::info('🗑 آیتم‌ها حذف شدند', ['count' => $deleted]);
                }
                if (method_exists($proforma, 'approvals')) {
                    $deleted = $proforma->approvals()->delete();
                    \Log::info('🗑 approvals حذف شدند', ['count' => $deleted]);
                }
    
                $proforma->delete();
                \Log::info('🧾 پیش‌فاکتور حذف شد (Soft Delete)', ['proforma_id' => $proforma->id]);
            });
    
            return redirect()
                ->route('sales.proformas.index')
                ->with('success', 'پیش‌فاکتور با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            \Log::error('❌ خطا در حذف پیش‌فاکتور', [
                'proforma_id' => $proforma->id ?? null,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
    
            return back()->with('error', 'خطا در حذف پیش‌فاکتور. لطفاً دوباره تلاش کنید.');
        }
    }
    
    /**
     * Prepare approval/approval-history data for the proforma tabs using only Proforma approvals.
     */
    private function buildProformaApprovalViewData(Proforma $proforma): array
    {
        $formatDate = static function ($date) {
            return $date ? \App\Helpers\DateHelper::toJalali($date, 'H:i Y/m/d') : '—';
        };

        $approvals = $proforma->relationLoaded('approvals')
            ? $proforma->approvals->loadMissing('approver', 'approvedBy')
            : $proforma->approvals()->with(['approver', 'approvedBy'])->get();

        $approvals = $approvals->sortBy(function ($approval) {
            return sprintf('%02d-%010d', (int)($approval->step ?? 99), (int)($approval->id ?? 0));
        });

        $buildStep = static function (int $step) use ($approvals, $formatDate) {
            $byStep   = $approvals->where('step', $step);
            $approved = $byStep->firstWhere('status', 'approved');
            $rejected = $byStep->firstWhere('status', 'rejected');
            $pending  = $byStep->firstWhere('status', 'pending');

            $statusClass = 'bg-amber-50 text-amber-800';
            $statusLabel = 'در انتظار تأیید';
            $dateDisplay = '—';
            $approvedAt  = null;
            $actor       = $approved ?? $rejected ?? $pending;
            $mainName    = optional($actor?->approver)->name;
            $subName     = null;
            $mainApproved = false;
            $subApproved  = false;

            if ($actor) {
                if ($actor->approved_by && (int) $actor->approved_by !== (int) $actor->user_id) {
                    $subName = optional($actor->approvedBy)->name;
                }

                if ($actor->status === 'approved') {
                    if (empty($actor->approved_by) || (int) $actor->approved_by === (int) $actor->user_id) {
                        $mainApproved = true;
                    } elseif (!empty($actor->approved_by) && (int) $actor->approved_by !== (int) $actor->user_id) {
                        $subApproved = true;
                    }
                }
            }

           if ($rejected) {
                $statusClass = 'bg-red-50 text-red-800';
                $statusLabel = 'رد شده';
                $approvedAt  = $rejected->approved_at ?? $rejected->created_at;
            } elseif ($approved) {
                $statusClass = 'bg-green-50 text-green-800';
                $statusLabel = 'تأیید شده';
                $approvedAt  = $approved->approved_at ?? $approved->created_at;
            }


            if ($approvedAt) {
                $dateDisplay = $formatDate($approvedAt);
            }

            return [
                'status_class'      => $statusClass,
                'status_label'      => $statusLabel,
                'date_display'      => $dateDisplay,
                'main_cell_class'   => $mainApproved ? 'bg-green-100' : ($rejected ? 'bg-red-100 text-red-800' : ''),
                'sub_cell_class'    => $subApproved ? 'bg-green-100' : '',
                'main_name' => $mainName ?: '—',
                'sub_name'  => $subName ?: '—',
                'main_approved'     => $mainApproved,
                'sub_approved'      => $subApproved,
                'approved_at'       => $approvedAt,
                'approved_at_fa'    => $approvedAt ? $formatDate($approvedAt) : null,
                'pending_approver'  => optional($pending?->approver)->name,
            ];
        };;

        $step1 = $buildStep(1);
        $step2 = $buildStep(2);
        $step3 = $buildStep(3);

        $lastApprovedAt = collect([$step3['approved_at'], $step2['approved_at'], $step1['approved_at']])
            ->filter()
            ->sortDesc()
            ->first();

        $durationText = null;
        try {
            if ($proforma->created_at && $lastApprovedAt) {
                $minutes = $proforma->created_at->diffInMinutes($lastApprovedAt);
                $days    = intdiv($minutes, 60 * 24);
                $hours   = intdiv($minutes % (60 * 24), 60);
                $mins    = $minutes % 60;

                $parts = [];
                if ($days) {
                    $parts[] = $days . ' روز';
                }
                if ($hours) {
                    $parts[] = $hours . ' ساعت';
                }
                if ($mins && $days === 0) {
                    $parts[] = $mins . ' دقیقه';
                }

                $durationText = $parts ? implode(' و ', $parts) : null;
            }
        } catch (\Throwable $e) {
            $durationText = null;
        }

        $currentUserId       = (int) auth()->id();
        $activePending       = $approvals->where('status', 'pending')->first();
        $emergencyApproverId = (int) optional($proforma->automationRule()->select('id', 'emergency_approver_id')->first())->emergency_approver_id;
        $showDecisionButtons = $activePending
            && (
                (int) $activePending->user_id === $currentUserId
                || ($emergencyApproverId && $emergencyApproverId === $currentUserId)
            );
        $createdAtFa         = $formatDate($proforma->created_at);
        $pendingApproverName = $activePending?->approver?->name
            ?? $step1['pending_approver']
            ?? $step2['pending_approver']
            ?? $step3['pending_approver']
            ?? null;

        return [
            'createdAtFa'                      => $createdAtFa,
            'durationText'                     => $durationText,
            'firstApprovedAtFa'                => $step1['approved_at_fa'],
            'secondApprovedAtFa'               => $step2['approved_at_fa'],
            'a1StatusClass'                    => $step1['status_class'],
            'a1StatusLabel'                    => $step1['status_label'],
            'a1DateDisplay'                    => $step1['date_display'],
            'firstApproverName'                => $step1['main_name'],
            'firstApproverSubstituteName'      => $step1['sub_name'],
            'firstApproverMainApproved'        => $step1['main_approved'],
            'firstApproverSubstituteApproved'  => $step1['sub_approved'],
            'firstMainCellClass'               => $step1['main_cell_class'],
            'firstSubCellClass'                => $step1['sub_cell_class'],
            'a2StatusClass'                    => $step2['status_class'],
            'a2StatusLabel'                    => $step2['status_label'],
            'a2DateDisplay'                    => $step2['date_display'],
            'secondApproverName'               => $step2['main_name'],
            'secondApproverSubstituteName'     => $step2['sub_name'],
            'secondApproverMainApproved'       => $step2['main_approved'],
            'secondApproverSubstituteApproved' => $step2['sub_approved'],
            'secondMainCellClass'              => $step2['main_cell_class'],
            'secondSubCellClass'               => $step2['sub_cell_class'],
            'a3StatusClass'                    => $step3['status_class'],
            'a3StatusLabel'                    => $step3['status_label'],
            'a3DateDisplay'                    => $step3['date_display'],
            'accountingApproverName'           => $step3['main_name'],
            'accountingApproverSubstituteName' => $step3['sub_name'],
            'accountingApproverMainApproved'   => $step3['main_approved'],
            'accountingApproverSubstituteApproved' => $step3['sub_approved'],
            'accountingMainCellClass'          => $step3['main_cell_class'],
            'accountingSubCellClass'           => $step3['sub_cell_class'],
            'showDecisionButtons'              => $showDecisionButtons,
            'pendingApproverName'              => $pendingApproverName,
        ];
    }

    private function runAutomationRulesIfNeeded(\App\Models\Proforma $proforma): void
    {
        try {
            $stage = strtolower(trim($proforma->approval_stage ?? $proforma->proforma_stage));
    
            Log::debug('🚀 اجرای قوانین اتوماسیون در صورت نیاز', [
                'proforma_id'       => $proforma->id,
                'stage'             => $stage,
                'first_approved_by' => $proforma->first_approved_by,
                'approved_by'       => $proforma->approved_by,
            ]);
    
            // فقط وقتی مرحله یکی از این دو باشد ادامه بده
            if (! in_array($stage, ['send_for_approval', 'awaiting_second_approval'])) {
                Log::info('⏭️ رد شد: مرحله مرتبط با فرآیند تأیید نیست', ['current_stage' => $stage]);
                return;
            }
    
            $rule = AutomationRule::with(['approvers.user'])
                ->where('proforma_stage', 'send_for_approval')
                ->first();
    
            if (! $rule) {
                Log::warning('⚠️ هیچ قانون اتوماسیونی برای send_for_approval پیدا نشد');
                return;
            }
    
            // ذخیره‌سازی automation_rule_id در پروفرما
            if ($proforma->automation_rule_id !== $rule->id) {
                $proforma->automation_rule_id = $rule->id;
                $proforma->save();
                Log::info('💾 automation_rule_id در پروفرما ذخیره شد', [
                    'proforma_id'        => $proforma->id,
                    'automation_rule_id' => $rule->id
                ]);
            }
    
            $approvers = $rule->approvers ?? collect();
    
            Log::info('👥 تأییدکنندگان یافت شدند', [
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
                Log::info('✅ پیش‌فاکتور قبلاً به‌صورت کامل تأیید شده است');
                return;
            }
    
            if (! $nextApproverId) {
                Log::warning('⚠️ تأییدکننده‌ی مرحله بعد تعیین نشد', ['proforma_id' => $proforma->id]);
                return;
            }
    
            // پاک‌سازی pendingهای قبلی به جز نفر بعدی
            $proforma->approvals()
                ->where('status', 'pending')
                ->where('user_id', '!=', $nextApproverId)
                ->delete();
    
            // ایجاد یا به‌روزرسانی رکورد تأیید
            $approval = $proforma->approvals()->updateOrCreate(
                ['user_id' => $nextApproverId, 'status' => 'pending'],
                ['step'    => $nextStep]
            );
    
            Log::info('📝 مرحله‌ی در انتظار ثبت شد', [
                'approval_id' => $approval->id,
                'user_id'     => $nextApproverId,
                'step'        => $nextStep
            ]);
    
            // ارسال نوتیفیکیشن
            $user = User::find($nextApproverId);
            if ($user && method_exists($user, 'notify')) {
                try {
                    $user->notify(FormApprovalNotification::fromModel($proforma, auth()->id() ?? 0));
                    Log::info('📨 نوتیفیکیشن ارسال شد', [
                        'to_user_id'   => $user->id,
                        'to_user_name' => $user->name,
                        'proforma_id'  => $proforma->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('📭 خطا در ارسال نوتیفیکیشن', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        }
        catch (\Exception $e) {
            Log::error('❌ خطا در runAutomationRulesIfNeeded', [
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
                'proforma_stage' => 'send_for_approval', // همگام‌سازی
            ])->save();
    
            // اجرای اتوماسیون بعد از آپدیت
            $this->runAutomationRulesIfNeeded($proforma);
        });
    
        return redirect()
            ->route('sales.proformas.index')
            ->with('success', 'پیش‌فاکتور با موفقیت برای تأییدیه ارسال شد.');
    }
    
    public function approve(Proforma $proforma)
    {
        $this->authorize('approve', $proforma);
    
        try {
            \DB::transaction(function () use ($proforma) {
                $userId = auth()->id();
    
                $approvals = $proforma->approvals()
                    ->with('approver')
                    ->orderBy('step')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                // رکوردِ مرحله‌ی در انتظار
                $pending = $approvals
                    ->where('status', 'pending')
                    ->sortBy(function ($approval) {
                        return sprintf('%02d-%010d', (int)($approval->step ?? 99), (int)($approval->id ?? 0));
                    })
                    ->first();
                if (! $pending) {
                    throw new \RuntimeException('هیچ مرحله‌ی در انتظاری برای تأیید وجود ندارد.');
                }
    
                $latestForUserThisStep = $approvals
                    ->filter(function ($approval) use ($userId, $pending) {
                        return (int) $approval->user_id === (int) $userId
                            && (int) ($approval->step ?? 0) === (int) ($pending->step ?? 0)
                            && $approval->status !== Approval::STATUS_SUPERSEDED;
                    })
                    ->sortByDesc('id')
                    ->first();

                if ($latestForUserThisStep && $latestForUserThisStep->status !== 'pending') {
                    throw new \RuntimeException('شما قبلاً این پیش‌فاکتور را تأیید کرده‌اید.');
                }

                // حالت 1: خودِ تأییدکننده‌ی اصلی
                $current = $approvals->first(function ($approval) use ($userId, $pending) {
                    return $approval->status === 'pending'
                        && (int) $approval->user_id === (int) $userId
                        && (int) ($approval->step ?? 0) === (int) ($pending->step ?? 0);
                });
    
                // حالت 2: اگر اصلی نبود، بررسی تأییدکننده اضطراری روی همان pending
                $asEmergency = false;
                if (! $current) {
                    $rule = $proforma->automationRule()->first();
                    if ($rule && (int) $rule->emergency_approver_id === (int) $userId) {
                        $current = $pending;   // اجازه بده اضطراری همان مرحله‌ی pending را تأیید کند
                        $asEmergency = true;
                    }
                }
    
                $currentApproval = $current ?? null;
                \Log::info('🔍 Proforma Approval Debug', [
                    'auth_user_id' => auth()->id(),
                    'auth_user_name' => auth()->user()->name ?? null,
                    'current_step' => $proforma->current_step ?? null,
                    'proforma_stage' => $proforma->proforma_stage ?? null,
                    'currentApproval_record' => $currentApproval ?? null,
                ]);

                if (! $current) {
                    throw new \RuntimeException('شما مجاز به تأیید این پیش‌فاکتور نیستید.');
                }
    
                // رعایت ترتیب مراحل: اگر پیش از این رکورد، آیتمی هنوز approved نشده، خطا بده
                $idx           = $approvals->search(fn ($a) => (int) $a->id === (int) $current->id);
                $currentStep   = (int) ($current->step ?? 0);
                $previousSteps = $approvals
                    ->take($idx)
                    ->filter(fn ($a) => (int) ($a->step ?? 0) < $currentStep);

                $blocker = $previousSteps->first(function ($approval) {
                    return in_array($approval->status, ['pending', 'rejected'], true);
                });
                if ($blocker) {
                    \Log::warning('🚫 Proforma Approval Blocker Debug', [
                        'proforma_id'    => $proforma->id,
                        'auth_user_id'   => $userId,
                        'auth_user_name' => auth()->user()->name ?? null,
                        'approvals'      => $approvals->map(function ($approval) {
                            return [
                                'id'            => $approval->id,
                                'user_id'       => $approval->user_id,
                                'step'          => $approval->step,
                                'status'        => $approval->status,
                                'approved_at'   => $approval->approved_at,
                                'approver_name' => optional($approval->approver)->name,
                            ];
                        })->values()->toArray(),
                        'blocker'        => [
                            'id'            => $blocker->id,
                            'user_id'       => $blocker->user_id,
                            'step'          => $blocker->step,
                            'status'        => $blocker->status,
                            'approved_at'   => $blocker->approved_at,
                            'approver_name' => optional($blocker->approver)->name,
                        ],
                    ]);
                    $who = optional($blocker->approver)->name ?: ('کاربر #' . $blocker->user_id);
                    throw new \RuntimeException("پیش‌فاکتور در انتظار تأیید {$who} است.");
                }
    
                // تأیید همین مرحله
                $current->update([
                    'status'      => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $userId,
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
    
                // اگر نفر دوم تعریف نشده و pending دیگری هم وجود ندارد، نهایی کن
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
    
                \Log::info('✅ فرآیند تأیید پیش‌فاکتور پیش رفت', [
                    'proforma_id' => $proforma->id,
                    'by_user'     => $userId,
                    'step'        => $step,
                    'stage'       => $proforma->approval_stage,
                    'as_emergency'=> $asEmergency,
                ]);
            });
    
            return back()->with('success', 'پیش‌فاکتور با موفقیت تأیید شد.');
    
        } catch (\Throwable $e) {
            \Log::error('❌ خطا در تأیید پیش‌فاکتور', [
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
    
                // حالت 1: ردکننده‌ی اصلی همین pending است
                $current = $approvals->firstWhere('user_id', $userId);
    
                // حالت 2: اگر اصلی نبود، بررسی اضطراری برای همان pending
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
    
                // رعایت ترتیب مراحل (اگر قبل از این رکورد، آیتمی هنوز approved نشده، بلاک کن)
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
    
                // ست کردن وضعیت کلی پروفرما به «رد شده»
                $proforma->fill([
                    'approval_stage' => 'rejected',
                    'proforma_stage' => 'rejected',
                ])->save();
    
                // پاک کردن تمام pendingهای دیگر تا فرآیند متوقف شود
                $proforma->approvals()
                    ->where('status', 'pending')
                    ->delete();
    
                \Log::info('🛑 پیش‌فاکتور رد شد', [
                    'proforma_id' => $proforma->id,
                    'by_user'     => $userId,
                    'step'        => (int) ($current->step ?? 1),
                    'as_emergency'=> $asEmergency,
                ]);
            });
    
            return back()->with('success', 'پیش‌فاکتور با موفقیت رد شد.');
    
        } catch (\Throwable $e) {
            \Log::error('❌ خطا در رد پیش‌فاکتور', [
                'proforma_id' => $proforma->id ?? null,
                'error'       => $e->getMessage(),
            ]);
    
            return back()->with('error', $e->getMessage());
        }
    }

    private function extractMentions($rawMentions, string $body): array
    {
        $list = [];

        if (is_array($rawMentions)) {
            foreach ($rawMentions as $item) {
                if (is_string($item)) {
                    $parts = array_map('trim', explode(',', $item));
                    $list = array_merge($list, $parts);
                }
            }
        } elseif (is_string($rawMentions) && $rawMentions !== '') {
            $list = array_map('trim', explode(',', $rawMentions));
        }

        if (preg_match_all('/@([^\\s@]+)/u', $body, $matches)) {
            $list = array_merge($list, $matches[1] ?? []);
        }

        $list = array_filter(array_unique(array_map(function ($value) {
            $value = trim((string) $value);
            return Str::startsWith($value, '@') ? ltrim($value, '@') : $value;
        }, $list)));

        return array_values($list);
    }

    protected function normalizeProductPayload(Request $request): void
    {
        $merged = [];

        $rawProducts = $request->input('products', []);
        if (is_array($rawProducts)) {
            foreach ($rawProducts as $key => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $merged[$key] = $this->normalizeSingleProductRow($row);
            }
        }

        $rawItems = $request->input('items', []);
        if (is_array($rawItems)) {
            foreach ($rawItems as $key => $row) {
                if (!is_array($row)) {
                    continue;
                }
                // Items originate from edit form; map into unified payload (overwriting same key if needed)
                $merged[$key] = $this->normalizeSingleProductRow($row);
            }
        }

        if ($merged !== []) {
            $request->merge(['products' => $merged]);
        }
    }

    protected function normalizeSingleProductRow(array $row): array
    {
        $toAsciiDigits = static function ($value): string {
            $map = [
                '۰' => '0','۱' => '1','۲' => '2','۳' => '3','۴' => '4',
                '۵' => '5','۶' => '6','۷' => '7','۸' => '8','۹' => '9',
                '٠' => '0','١' => '1','٢' => '2','٣' => '3','٤' => '4',
                '٥' => '5','٦' => '6','٧' => '7','٨' => '8','٩' => '9',
            ];
            return strtr((string) $value, $map);
        };

        $sanitizeNumber = static function ($value) use ($toAsciiDigits) {
            if ($value === null) {
                return null;
            }
            $ascii = $toAsciiDigits($value);
            $clean = preg_replace('/[^\d\-]/', '', $ascii);
            return ($clean === '' || $clean === null) ? null : $clean;
        };

        $priceSource = $row['price'] ?? ($row['unit_price'] ?? null);
        $quantitySource = $row['quantity'] ?? ($row['qty'] ?? null);
        $discountValueSource = array_key_exists('discount_value', $row) ? $row['discount_value'] : 0;
        $taxValueSource = array_key_exists('tax_value', $row) ? $row['tax_value'] : 0;

        return [
            'product_id'     => $row['product_id'] ?? ($row['id'] ?? null),
            'name'           => $row['name'] ?? null,
            'unit'           => $row['unit'] ?? ($row['unit_of_use'] ?? null),
            'quantity'       => $sanitizeNumber($quantitySource),
            'price'          => $sanitizeNumber($priceSource),
            'discount_type'  => $row['discount_type'] ?? null,
            'discount_value' => $sanitizeNumber($discountValueSource) ?? 0,
            'tax_type'       => $row['tax_type'] ?? null,
            'tax_value'      => $sanitizeNumber($taxValueSource) ?? 0,
        ];
    }

    protected function isInternalUrl(?string $url, Request $request): bool
    {
        if (!is_string($url)) {
            return false;
        }

        $candidate = trim($url);
        if ($candidate === '' || Str::startsWith($candidate, ['//', 'javascript:'])) {
            return false;
        }

        $host = parse_url($candidate, PHP_URL_HOST);
        $scheme = parse_url($candidate, PHP_URL_SCHEME);

        if ($scheme && !in_array(strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        if ($host && strcasecmp($host, $request->getHost()) !== 0) {
            return false;
        }

        $port = parse_url($candidate, PHP_URL_PORT);
        if ($port && (int) $port !== (int) $request->getPort()) {
            return false;
        }

        if (! $host) {
            return Str::startsWith($candidate, '/');
        }

        return true;
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'          => ['required','array','min:1'],
            'ids.*'        => ['integer','distinct'],
            'force_delete' => ['nullable','boolean'],
        ]);
    
        // جلوگیری از حذف آیتم‌هایی که در وضعیت ارسال برای تأیید هستند
        $ids = Proforma::query()
            ->whereIn('id', $data['ids'])
            ->where('proforma_stage', '!=', 'send_for_approval')
            ->pluck('id');
    
        if ($ids->isEmpty()) {
            return back()->with('error', 'هیچ آیتم قابل حذفی انتخاب نشده است.');
        }
    
        try {
            DB::transaction(function () use ($ids) {
                Proforma::query()->whereIn('id', $ids)->delete(); // کافی است
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در حذف گروهی: '.$e->getMessage());
        }
    
        return back()->with('success', $ids->count().' مورد حذف شد.');
    }
   
}





