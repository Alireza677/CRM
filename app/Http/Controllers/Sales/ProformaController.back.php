﻿<?php
}

    public function preview(Proforma \)
    {
        \->load(['organization','contact','items.product']);
        return view('sales.proformas.preview', compact('proforma'));
    }
 
}
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
    // ÙˆØ±ÙˆØ¯ÛŒâ€ŒÙ‡Ø§
    $search          = trim((string) $request->get('search', ''));
    $organizationId  = $request->get('organization_id');
    $stage           = $request->get('stage');
    $assignedTo      = $request->get('assigned_to');

    // Ø¯ÛŒØªØ§ÛŒ Ú©Ù…â€ŒØ­Ø¬Ù… Ø¨Ø±Ø§ÛŒ ÙˆÛŒÙˆ (ÙÙ‚Ø· ÙÛŒÙ„Ø¯Ù‡Ø§ÛŒ Ù„Ø§Ø²Ù…)
    $organizations = Organization::select('id', 'name')->orderBy('name')->get();
    $users         = User::select('id', 'name')->orderBy('name')->get();

    // Ú©ÙˆØ¦Ø±ÛŒ Ø§ØµÙ„ÛŒ
    $query = Proforma::query()
        ->with(['organization', 'contact', 'opportunity', 'assignedTo'])
        ->orderByDesc('proforma_date')
        ->orderByDesc('created_at');

    // Ø¬Ø³ØªØ¬Ùˆ
    $query->when($search !== '', function ($q) use ($search) {
        $q->where(function ($qq) use ($search) {
            $qq->where('subject', 'like', "%{$search}%")
               ->orWhereHas('organization', function ($q2) use ($search) {
                   $q2->where('name', 'like', "%{$search}%");
               })
               ->orWhereHas('contact', function ($q3) use ($search) {
                   $q3->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name',  'like', "%{$search}%");
                   // Ø§Ú¯Ø± Ù…Ø¯Ù„ contact Ø³ØªÙˆÙ† full_name Ø¯Ø§Ø±Ø¯ØŒ Ù…ÛŒâ€ŒØªÙˆØ§Ù†ÛŒ Ø§ÛŒÙ† Ø±Ø§ Ù‡Ù… Ø§Ø¶Ø§ÙÙ‡ Ú©Ù†ÛŒ:
                   // ->orWhere('full_name', 'like', "%{$search}%");
               });
        });
    });

    // ÙÛŒÙ„ØªØ± Ø³Ø§Ø²Ù…Ø§Ù† (Ù‡Ù…Ø§Ù‡Ù†Ú¯ Ø¨Ø§ input hidden[name=organization_id])
    $query->when(!empty($organizationId), function ($q) use ($organizationId) {
        $q->where('organization_id', (int) $organizationId);
    });

    // ÙÛŒÙ„ØªØ± Ù…Ø±Ø­Ù„Ù‡
    $query->when(!empty($stage), function ($q) use ($stage) {
        $q->where('proforma_stage', $stage);
    });

    // ÙÛŒÙ„ØªØ± Ø§Ø±Ø¬Ø§Ø¹â€ŒØ¨Ù‡ (Ú©Ø§Ø±Ø¨Ø±)
    $query->when(!empty($assignedTo), function ($q) use ($assignedTo) {
        $q->where('assigned_to', (int) $assignedTo);
    });

    // ØµÙØ­Ù‡â€ŒØ¨Ù†Ø¯ÛŒ + Ø­ÙØ¸ Ú©ÙˆØ¦Ø±ÛŒâ€ŒØ§Ø³ØªØ±ÛŒÙ†Ú¯
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
                    'opportunity_name'   => $opportunity->name ?? $opportunity->subject ?? '', // â† Ø§Ø¶Ø§ÙÙ‡ Ø´Ø¯
                    'sales_opportunity'  => $opportunity->name ?? $opportunity->subject ?? '', // â† Ø§Ú¯Ø± Ø³ØªÙˆÙ†â€ŒØªØ§Ù† Ø§ÛŒÙ† Ù†Ø§Ù… Ø±Ø§ Ù…ÛŒâ€ŒØ®ÙˆØ§Ù‡Ø¯
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
        // -------------------- 1) HARD PRE-CLEAN: Ø§Ø¹Ø¯Ø§Ø¯ ÙØ§Ø±Ø³ÛŒ/Ø¬Ø¯Ø§Ú©Ù†Ù†Ø¯Ù‡â€ŒÙ‡Ø§ Ù‚Ø¨Ù„ Ø§Ø² validate --------------------
        $in = $request->all();

        $removeJunk = static function ($v) {
            if ($v === null || $v === '') return $v;
            $v = (string) $v;

            // Ø­Ø°Ù ÙØ§ØµÙ„Ù‡â€ŒÙ‡Ø§ÛŒ Ù†Ø§Ù…Ø±Ø¦ÛŒ/ØºÛŒØ±Ø§Ø³ØªØ§Ù†Ø¯Ø§Ø±Ø¯
            $v = str_replace(
                ["\u{200C}", "\u{200B}", "\u{00A0}", "\u{FEFF}", " "],
                '',
                $v
            );

            // ØªØ¨Ø¯ÛŒÙ„ Ø§Ø±Ù‚Ø§Ù… ÙØ§Ø±Ø³ÛŒ/Ø¹Ø±Ø¨ÛŒ Ùˆ Ø¬Ø¯Ø§Ú©Ù†Ù†Ø¯Ù‡â€ŒÙ‡Ø§
            $mapFrom = ['Û°','Û±','Û²','Û³','Û´','Ûµ','Û¶','Û·','Û¸','Û¹','Ù ','Ù¡','Ù¢','Ù£','Ù¤','Ù¥','Ù¦','Ù§','Ù¨','Ù©','Ù¬','Ù«','ØŒ',','];
            $mapTo   = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','','.','',''];
            $v = str_replace($mapFrom, $mapTo, $v);

            // Ù†Ú¯Ù‡â€ŒØ¯Ø§Ø´ØªÙ† ÙÙ‚Ø· Ø¹Ø¯Ø¯/Ù†Ù‚Ø·Ù‡/Ù…Ù†ÙÛŒ
            $v = preg_replace('/[^0-9.\-]/', '', $v) ?? '';

            // Ø§Ú¯Ø± Ú†Ù†Ø¯ Ù†Ù‚Ø·Ù‡ Ø¨ÙˆØ¯ØŒ Ø¨Ù‡ ÛŒÚ© Ù†Ù‚Ø·Ù‡ ØªÙ‚Ù„ÛŒÙ„ ÛŒØ§Ø¨Ø¯
            if (substr_count($v, '.') > 1) {
                $first = strpos($v, '.');
                $v = substr($v, 0, $first + 1) . str_replace('.', '', substr($v, $first + 1));
            }

            return ($v === '' || $v === '-') ? null : $v;
        };

        // ÙÛŒÙ„Ø¯Ù‡Ø§ÛŒ Ø¹Ø¯Ø¯ÛŒ Ø³Ø±Ø§Ø³Ø±ÛŒ
        foreach (['global_discount_value','global_tax_value','total_subtotal','total_discount','total_tax','total_amount'] as $f) {
            if (array_key_exists($f, $in)) {
                $in[$f] = $removeJunk($in[$f]);
            }
        }

        // ÙÛŒÙ„Ø¯Ù‡Ø§ÛŒ Ø¹Ø¯Ø¯ÛŒ Ù…Ø­ØµÙˆÙ„Ø§Øª
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

            // Ù…Ø­ØµÙˆÙ„Ø§Øª
            'products'                 => 'nullable|array',
            'products.*.name'          => 'nullable|string|max:255',
            'products.*.quantity'      => 'nullable|numeric|min:0.01',
            'products.*.price'         => 'nullable|numeric|min:0',
            'products.*.unit'          => 'nullable|string|max:50',
            // (Ú†ÙˆÙ† Ù‚Ø±Ø§Ø± Ø§Ø³Øª ØªØ®ÙÛŒÙ/Ù…Ø§Ù„ÛŒØ§Øª Ø³Ø±Ø§Ø³Ø±ÛŒ Ø¨Ø§Ø´Ø¯ØŒ ÙÛŒÙ„Ø¯Ù‡Ø§ÛŒ Ø³Ø·Ø±ÛŒ Ø§Ø¬Ø¨Ø§Ø±ÛŒ Ù†ÛŒØ³ØªÙ†Ø¯)
            'products.*.discount_type' => 'nullable|in:percentage,fixed',
            'products.*.discount_value'=> 'nullable|numeric|min:0',
            'products.*.tax_type'      => 'nullable|in:percentage,fixed',
            'products.*.tax_value'     => 'nullable|numeric|min:0',

            // Ú©Ù†ØªØ±Ù„â€ŒÙ‡Ø§ÛŒ Ø³Ø±Ø§Ø³Ø±ÛŒ (Ø§Ø®ØªÛŒØ§Ø±ÛŒ)
            'global_discount_type' => 'nullable|in:none,percentage,fixed',
            'global_discount_value'=> 'nullable|numeric|min:0',
            'global_tax_type'      => 'nullable|in:none,percentage,fixed',
            'global_tax_value'     => 'nullable|numeric|min:0',
        ]);
        \Log::debug('âœ… Passed validation (store)', $validated);

        // -------------------- 3) ØªØ§Ø±ÛŒØ® ÙˆØ±ÙˆØ¯ÛŒ â†’ Ù…ÛŒÙ„Ø§Ø¯ÛŒ (Ù¾Ø´ØªÛŒØ¨Ø§Ù†ÛŒ Ù‡Ø± Ø¯Ùˆ ÙØ±Ù…Øª) --------------------
        // Ø³Ù†Ø§Ø±ÛŒÙˆÙ‡Ø§:
        // - Ø§Ú¯Ø± Ø®Ø§Ù„ÛŒ Ø¨ÙˆØ¯: Ø§Ù…Ø±ÙˆØ² Ø°Ø®ÛŒØ±Ù‡ Ù…ÛŒâ€ŒØ´ÙˆØ¯.
        // - Ø§Ú¯Ø± "YYYY-MM-DD" (Ù…ÛŒÙ„Ø§Ø¯ÛŒ) Ø¨ÙˆØ¯: Ù…Ø³ØªÙ‚ÛŒÙ… Carbon Ù…ÛŒâ€ŒØ´ÙˆØ¯.
        // - Ø§Ú¯Ø± "YYYY/MM/DD" ÛŒØ§ Â«YYYY-MM-DDÂ» (Ø¬Ù„Ø§Ù„ÛŒ) Ø¨ÙˆØ¯: Ø¨Ù‡ Ù…ÛŒÙ„Ø§Ø¯ÛŒ ØªØ¨Ø¯ÛŒÙ„ Ù…ÛŒâ€ŒØ´ÙˆØ¯.
        $miladiDate = null;
        try {
            $rawDate = trim((string)($validated['proforma_date'] ?? ''));
            // Normalize unicode digits (Persian/Arabic) to ASCII and strip ZW chars
            $rawDate = preg_replace('/\x{200C}|\x{200B}|\x{00A0}|\x{FEFF}/u', '', $rawDate);
            $rawDate = str_replace(
                ['Û°','Û±','Û²','Û³','Û´','Ûµ','Û¶','Û·','Û¸','Û¹','Ù ','Ù¡','Ù¢','Ù£','Ù¤','Ù¥','Ù¦','Ù§','Ù¨','Ù©'],
                ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'],
                $rawDate
            );
            if ($rawDate === '') {
                // Ù¾ÛŒØ´â€ŒÙØ±Ø¶: Ø§Ù…Ø±ÙˆØ²
                $miladiDate = \Carbon\Carbon::today();
            } else {
                $normalized = preg_replace('/\s+/', '', $rawDate) ?? '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
                    $year = (int) substr($normalized, 0, 4);
                    if ($year >= 1300 && $year <= 1599) {
                        // Ø¬Ù„Ø§Ù„ÛŒ Ø¨Ø§ Ø®Ø·â€ŒØªÛŒØ±Ù‡
                        $miladiDate = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', str_replace('-', '/', $normalized))->toCarbon();
                    } else {
                        // Ù…ÛŒÙ„Ø§Ø¯ÛŒ: YYYY-MM-DD
                        $miladiDate = \Carbon\Carbon::createFromFormat('Y-m-d', $normalized)->startOfDay();
                    }
                } else {
                    // ØªÙ„Ø§Ø´ Ø¨Ø±Ø§ÛŒ Ø¬Ù„Ø§Ù„ÛŒ: YYYY/MM/DD (ÛŒØ§ Ø¨Ø§ - Ú©Ù‡ Ø¨Ù‡ / ØªØ¨Ø¯ÛŒÙ„ Ù…ÛŒâ€ŒÚ©Ù†ÛŒÙ…)
                    $jalaliDate = str_replace('-', '/', $normalized);
                    if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $jalaliDate)) {
                        $miladiDate = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon();
                    } else {
                        return back()->withInput()->with('error', 'ØªØ§Ø±ÛŒØ® ÙˆØ§Ø±Ø¯ Ø´Ø¯Ù‡ Ù…Ø¹ØªØ¨Ø± Ù†ÛŒØ³Øª.');
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('âŒ Invalid Date (store)', ['exception' => $e->getMessage(), 'raw' => $validated['proforma_date'] ?? null]);
            return back()->withInput()->with('error', 'ØªØ§Ø±ÛŒØ® ÙˆØ§Ø±Ø¯ Ø´Ø¯Ù‡ Ù…Ø¹ØªØ¨Ø± Ù†ÛŒØ³Øª.');
        }

        // -------------------- 4) DB & Ù…Ø­Ø§Ø³Ø¨Ø§Øª --------------------
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
            'total_amount'      => 0, // Ø¨Ø¹Ø¯Ø§Ù‹ Ø¢Ù¾Ø¯ÛŒØª Ù…ÛŒâ€ŒÚ©Ù†ÛŒÙ…
        ]);
        \Log::info('ðŸ“„ Proforma Created', ['id' => $proforma->id]);

        // Ø§Ø³ØªØ±Ø§ØªÚ˜ÛŒ: ØªØ®ÙÛŒÙ/Ù…Ø§Ù„ÛŒØ§Øª Ø³Ø±Ø§Ø³Ø±ÛŒ Ø±ÙˆÛŒ Ù…Ø¬Ù…ÙˆØ¹ Ø§Ù‚Ù„Ø§Ù… Ø§Ø¹Ù…Ø§Ù„ Ù…ÛŒâ€ŒØ´ÙˆØ¯
        $subtotal = 0.0;

        if (!empty($validated['products'])) {
            foreach ($validated['products'] as $item) {
                $unitPrice = (float) ($item['price']    ?? 0);
                $quantity  = (float) ($item['quantity'] ?? 0);
                $lineBase  = $unitPrice * $quantity;

                // Ø¬Ù…Ø¹ Ù¾Ø§ÛŒÙ‡
                $subtotal += $lineBase;

                // Ø°Ø®ÛŒØ±Ù‡ Ø¢ÛŒØªÙ…Ø› ØªØ®ÙÛŒÙ/Ù…Ø§Ù„ÛŒØ§Øª Ø³Ø·Ø±ÛŒ Ø±Ø§ ØµÙØ± Ù…ÛŒâ€ŒÚ¯Ø°Ø§Ø±ÛŒÙ… ØªØ§ Ø¯ÙˆØ¨Ø§Ø±Ù‡ Ø§Ø¹Ù…Ø§Ù„ Ù†Ø´ÙˆØ¯
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
                    'total_after_tax' => $lineBase, // ÙØ¹Ù„Ø§Ù‹ Ø¨Ø±Ø§Ø¨Ø± Ø¨Ø§ Ø®Ø· Ù¾Ø§ÛŒÙ‡
                ]);
            }
        }

        // ØªØ®ÙÛŒÙ/Ù…Ø§Ù„ÛŒØ§Øª Ø³Ø±Ø§Ø³Ø±ÛŒ
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
        // Ø¬Ù„ÙˆÚ¯ÛŒØ±ÛŒ Ø§Ø² Ù…Ù†ÙÛŒ Ø´Ø¯Ù†
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

       // ØªØ¨Ø¯ÛŒÙ„ safe Ø¨Ù‡ Ø¹Ø¯Ø¯ ØµØ­ÛŒØ­ (Ø±ÛŒØ§Ù„)
        $toInt = fn($x) => (int) round((float) $x, 0);

        // Ø§Ú¯Ø± enum Ø¯ÛŒØªØ§Ø¨ÛŒØ³ 'none' Ù†Ø¯Ø§Ø±Ù‡ØŒ none => null
        $dbDiscType = ($gDiscType === 'none') ? null : $gDiscType;
        $dbTaxType  = ($gTaxType  === 'none') ? null : $gTaxType;

        $proforma->update([
            'items_subtotal'        => $toInt($subtotal),

            'global_discount_type'  => $dbDiscType,
            'global_discount_value' => $toInt($gDiscVal),        // Ø§Ú¯Ø± Ø¯Ø±ØµØ¯ Ø¨ÙˆØ¯ØŒ Ù‡Ù…ÙˆÙ† Ø¹Ø¯Ø¯ Ø¯Ø±ØµØ¯ Ø°Ø®ÛŒØ±Ù‡ Ù…ÛŒâ€ŒØ´ÙˆØ¯
            'global_discount_amount'=> $toInt($globalDiscount),  // Ù…Ø¨Ù„Øº ÙˆØ§Ù‚Ø¹ÛŒ ØªØ®ÙÛŒÙ Ø§Ø¹Ù…Ø§Ù„â€ŒØ´Ø¯Ù‡

            'global_tax_type'       => $dbTaxType,
            'global_tax_value'      => $toInt($gTaxVal),         // Ø§Ú¯Ø± Ø¯Ø±ØµØ¯ Ø¨ÙˆØ¯ØŒ Ù‡Ù…ÙˆÙ† Ø¹Ø¯Ø¯ Ø¯Ø±ØµØ¯ Ø°Ø®ÛŒØ±Ù‡ Ù…ÛŒâ€ŒØ´ÙˆØ¯
            'global_tax_amount'     => $toInt($globalTax),       // Ù…Ø¨Ù„Øº ÙˆØ§Ù‚Ø¹ÛŒ Ù…Ø§Ù„ÛŒØ§Øª Ø§Ø¹Ù…Ø§Ù„â€ŒØ´Ø¯Ù‡

            'total_amount'          => $toInt($grandTotal),
        ]);

        \Log::debug('ðŸ§® Totals (global mode)', [
            'subtotal'        => $subtotal,
            'global_discount' => $globalDiscount,
            'after_discount'  => $afterDiscount,
            'global_tax'      => $globalTax,
            'grand_total'     => $grandTotal,
        ]);

        // Ù†ÙˆØªÛŒÙÛŒÚ©ÛŒØ´Ù† Â«Ø§Ø±Ø¬Ø§Ø¹ Ø¨Ù‡Â»
        $proforma->notifyIfAssigneeChanged(null);

        // Ø§ØªÙˆÙ…Ø§Ø³ÛŒÙˆÙ† "Ø§Ø±Ø³Ø§Ù„ Ø¨Ø±Ø§ÛŒ ØªØ§ÛŒÛŒØ¯ÛŒÙ‡"
        if ($proforma->proforma_stage === 'send_for_approval') {
            $condition = AutomationCondition::where('model_type', 'Proforma')
                ->where('field', 'proforma_stage')
                ->where('operator', '=')
                ->where('value', 'send_for_approval')
                ->first();

            if ($condition) {
                \Log::info('ðŸ”” Automation condition matched for send_for_approval');
                $sender = \Auth::user();
                foreach ([$condition->approver1_id, $condition->approver2_id] as $approverId) {
                    if ($approverId && ($user = User::find($approverId))) {
                        $user->notify(new \App\Notifications\FormApprovalNotification($proforma, $sender));
                    }
                }
            }
        }

        DB::commit();

        // Ø§Ø¬Ø±Ø§ÛŒ Ù‡Ø± Rule Ø¯ÛŒÚ¯Ø±ÛŒ Ú©Ù‡ Ø¨Ù‡ state Ù¾Ø§ÛŒØ¯Ø§Ø± Ù†ÛŒØ§Ø² Ø¯Ø§Ø±Ø¯
        $proforma->refresh();
        $this->runAutomationRulesIfNeeded($proforma);

        return redirect()->route('sales.proformas.index')->with('success', 'Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø§ÛŒØ¬Ø§Ø¯ Ø´Ø¯.');
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('âŒ Error Creating Proforma:', ['exception' => $e->getMessage()]);
        return back()->withInput()->with('error', 'Ø®Ø·Ø§ Ø¯Ø± Ø§ÛŒØ¬Ø§Ø¯ Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ±. Ù„Ø·ÙØ§ Ø¯ÙˆØ¨Ø§Ø±Ù‡ ØªÙ„Ø§Ø´ Ú©Ù†ÛŒØ¯.');
    }
}






    public function show(Proforma $proforma)
    {
        $proforma->load([
            'organization', 'contact', 'opportunity', 'assignedTo',
            'items',
            'approvals.approver',   // Ø¨Ø±Ø§ÛŒ Ø³ÛŒØ³ØªÙ… Ù‚Ø¯ÛŒÙ…ÛŒ approvals
        ]);
    
        // 1) Ø§Ú¯Ø± Ø³ÛŒØ³ØªÙ… approvals Ø±Ú©ÙˆØ±Ø¯ pending Ø¯Ø§Ø±Ø¯ØŒ Ù‡Ù…Ø§Ù† Ø±Ø§ Ø§Ø³ØªÙØ§Ø¯Ù‡ Ú©Ù†
        $approval = $proforma->approvals()
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();
    
        $pendingApproval = $proforma->approvals
            ->where('status', 'pending')
            ->first();
    
        $pendingApproverName = $pendingApproval?->approver?->name;
    
        // 2) Ø¯Ø± ØºÛŒØ± Ø§ÛŒÙ† ØµÙˆØ±ØªØŒ Ø§Ø² Ù‚ÙˆØ§Ù†ÛŒÙ† Ø§ØªÙˆÙ…Ø§Ø³ÛŒÙˆÙ† Ù…Ø­Ø§Ø³Ø¨Ù‡ Ú©Ù†
        if (empty($pendingApproverName)) {
            $stage = $proforma->approval_stage ?? $proforma->proforma_stage;
    
            if ($stage === 'send_for_approval') {
                $rule = AutomationRule::with(['approvers.user'])
                    ->where('proforma_stage', 'send_for_approval')
                    ->first();
    
                if ($rule) {
                    $pendingApproverId = null;
    
                    if (empty($proforma->first_approved_by)) {
                        // Ù‡Ù†ÙˆØ² Ù…Ø±Ø­Ù„Ù‡ Ø§ÙˆÙ„ ØªØ§ÛŒÛŒØ¯ Ù†Ø´Ø¯Ù‡
                        $pendingApproverId = optional($rule->approvers->firstWhere('priority', 1))->user_id;
                    } elseif (empty($proforma->approved_by)) {
                        // Ù…Ø±Ø­Ù„Ù‡ Ø§ÙˆÙ„ ØªØ§ÛŒÛŒØ¯ Ø´Ø¯Ù‡ ÙˆÙ„ÛŒ Ù†Ù‡Ø§ÛŒÛŒ Ù†Ø´Ø¯Ù‡
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
        // 1) Ù‚Ø§Ù†ÙˆÙ† Ø§ØµÙ„ÛŒ: ÙÙ‚Ø· Ø¯Ø± Ù¾ÛŒØ´â€ŒÙ†ÙˆÛŒØ³
        if (! $proforma->canEdit()) {
            return redirect()
                ->route('sales.proformas.show', $proforma)
                ->with('alert_error', 'ÙÙ‚Ø· Ø¯Ø± ÙˆØ¶Ø¹ÛŒØª Â«Ù¾ÛŒØ´â€ŒÙ†ÙˆÛŒØ³Â» Ù‚Ø§Ø¨Ù„ ÙˆÛŒØ±Ø§ÛŒØ´ Ø§Ø³Øª.');
        }
    
        // 2) Ø§Ø­Ø±Ø§Ø² Ù…Ø¬ÙˆØ² (Ø§Ø¯Ù…ÛŒÙ†/Ú©Ø§Ø±Ø¨Ø±Ù assignâ€ŒØ´Ø¯Ù‡ Ùˆ ...)
        $this->authorize('update', $proforma);
    
        // 3) Ù„ÙˆØ¯ Ø¯Ø§Ø¯Ù‡â€ŒÙ‡Ø§ÛŒ Ù„Ø§Ø²Ù… Ø¨Ø±Ø§ÛŒ ÙØ±Ù…
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
        Log::debug('âœï¸ Update Request Payload:', $request->all());
        $this->authorize('update', $proforma);
        if (! $proforma->canEdit()) {
            return back()->with('error', 'ÙÙ‚Ø· Ø¯Ø± ÙˆØ¶Ø¹ÛŒØª Ù¾ÛŒØ´â€ŒÙ†ÙˆÛŒØ³ Ù‚Ø§Ø¨Ù„ ÙˆÛŒØ±Ø§ÛŒØ´ Ø§Ø³Øª.');
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
    
                // Ù…Ø­ØµÙˆÙ„Ø§Øª Ø¯ÛŒÚ¯Ø± Ø§Ø¬Ø¨Ø§Ø±ÛŒ Ù†ÛŒØ³ØªÙ†Ø¯
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
            Log::debug('âœ… Passed Update Validation:', $validated);
    
            // ØªØ§Ø±ÛŒØ® ÙˆØ±ÙˆØ¯ÛŒ Ø¯Ø± ÙˆÛŒØ±Ø§ÛŒØ´ â†’ Ù…ÛŒÙ„Ø§Ø¯ÛŒ (Ù¾Ø´ØªÛŒØ¨Ø§Ù†ÛŒ Ù‡Ø± Ø¯Ùˆ ÙØ±Ù…Øª + Ù†Ú¯Ù‡â€ŒØ¯Ø§Ø´ØªÙ† Ù…Ù‚Ø¯Ø§Ø± Ù‚Ø¨Ù„ÛŒ Ø¯Ø± ØµÙˆØ±Øª Ø®Ø§Ù„ÛŒ)
            $miladiDate = $proforma->proforma_date; // Ù¾ÛŒØ´â€ŒÙØ±Ø¶: Ù…Ù‚Ø¯Ø§Ø± Ù‚Ø¨Ù„ÛŒ Ø±Ø§ Ù†Ú¯Ù‡ Ø¯Ø§Ø±
            $rawDateUpd = trim((string)($validated['proforma_date'] ?? ''));
            if ($rawDateUpd !== '') {
                try {
                    // Ù†Ø±Ù…Ø§Ù„â€ŒØ³Ø§Ø²ÛŒ Ø§Ø±Ù‚Ø§Ù… Ùˆ Ú©Ø§Ø±Ø§Ú©ØªØ±Ù‡Ø§ÛŒ Ù†Ø§Ù…Ø±Ø¦ÛŒ
                    $rawDateUpd = preg_replace('/\x{200C}|\x{200B}|\x{00A0}|\x{FEFF}/u', '', $rawDateUpd);
                    $rawDateUpd = str_replace(
                        ['Û°','Û±','Û²','Û³','Û´','Ûµ','Û¶','Û·','Û¸','Û¹','Ù ','Ù¡','Ù¢','Ù£','Ù¤','Ù¥','Ù¦','Ù§','Ù¨','Ù©'],
                        ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'],
                        $rawDateUpd
                    );
                    $normalizedUpd = preg_replace('/\s+/', '', $rawDateUpd) ?? '';
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalizedUpd)) {
                        $year = (int) substr($normalizedUpd, 0, 4);
                        if ($year >= 1300 && $year <= 1599) {
                            // Ø¬Ù„Ø§Ù„ÛŒ Ø¨Ø§ Ø®Ø· ØªÛŒØ±Ù‡
                            $miladiDate = Jalalian::fromFormat('Y/m/d', str_replace('-', '/', $normalizedUpd))->toCarbon();
                        } else {
                            // Ù…ÛŒÙ„Ø§Ø¯ÛŒ Ø¨Ø§ Ø®Ø· ØªÛŒØ±Ù‡
                            $miladiDate = \Carbon\Carbon::createFromFormat('Y-m-d', $normalizedUpd)->startOfDay();
                        }
                    } else {
                        // ØªÙ„Ø§Ø´ Ø¨Ø±Ø§ÛŒ Ø¬Ù„Ø§Ù„ÛŒ Ø¨Ø§ Ø§Ø³Ù„Ø´
                        $jalaliDateString = str_replace('-', '/', $normalizedUpd);
                        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $jalaliDateString)) {
                            $miladiDate = Jalalian::fromFormat('Y/m/d', $jalaliDateString)->toCarbon();
                        } else {
                            return back()->withInput()->with('error', 'ØªØ§Ø±ÛŒØ® ÙˆØ§Ø±Ø¯ Ø´Ø¯Ù‡ Ù…Ø¹ØªØ¨Ø± Ù†ÛŒØ³Øª.');
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('âŒ Invalid Jalali/Gregorian Date on Update:', ['exception' => $e->getMessage(), 'raw' => $validated['proforma_date']]);
                    return back()->withInput()->with('error', 'ØªØ§Ø±ÛŒØ® ÙˆØ§Ø±Ø¯ Ø´Ø¯Ù‡ Ù…Ø¹ØªØ¨Ø± Ù†ÛŒØ³Øª.');
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
    
                // Ù…Ø­Ø§Ø³Ø¨Ù‡ ØªØ®ÙÛŒÙ
                $discountAmount = ($item['discount_type'] === 'percentage') 
                    ? ($unitPrice * $discountValue / 100)
                    : $discountValue;
    
                $priceAfterDiscount = $unitPrice - $discountAmount;
    
                // Ù…Ø­Ø§Ø³Ø¨Ù‡ Ù…Ø§Ù„ÛŒØ§Øª
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
            Log::info('âœ… Proforma Updated:', ['id' => $proforma->id]);
    
            $proforma->items()->delete();
            $proforma->items()->createMany($proformaItems);
    
            $proforma->notifyIfAssigneeChanged($oldAssignedTo);
    
            // Ø§Ø¹Ù„Ø§Ù† ØªØ§ÛŒÛŒØ¯ Ø¯Ø± ØµÙˆØ±Øª ØªØºÛŒÛŒØ± Ø¨Ù‡ Ù…Ø±Ø­Ù„Ù‡ Ù…Ø±Ø¨ÙˆØ·Ù‡
            if ($validated['proforma_stage'] === 'send_for_approval' && $oldStage !== 'send_for_approval') {
                $condition = \App\Models\AutomationCondition::where('model_type', 'Proforma')
                    ->where('field', 'proforma_stage')
                    ->where('operator', '=')
                    ->where('value', 'send_for_approval')
                    ->first();
    
                if ($condition) {
                    Log::info('ðŸ”” Automation condition matched for send_for_approval');
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
            return redirect()->route('sales.proformas.show', $proforma)->with('success', 'Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø¨Ø±ÙˆØ²Ø±Ø³Ø§Ù†ÛŒ Ø´Ø¯.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('âŒ Error Updating Proforma:', ['exception' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Ø®Ø·Ø§ Ø¯Ø± Ø¨Ø±ÙˆØ²Ø±Ø³Ø§Ù†ÛŒ Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ±.');
        }
    }
    


    public function destroy(Proforma $proforma)
{
    \Log::info('ðŸŸ¢ Destroy called', [
        'route_parameters' => request()->route()->parameters(),
        'proforma_id'      => $proforma->id ?? null,
        'proforma_number'  => $proforma->number ?? null,
    ]);

    // ØªØµÙ…ÛŒÙ… Ù†Ù‡Ø§ÛŒÛŒ Ø¨Ø§ Policy
    try {
        $this->authorize('delete', $proforma);
        \Log::info('âœ… Authorization passed', ['proforma_id' => $proforma->id]);

        \DB::transaction(function () use ($proforma) {
            \Log::info('ðŸ›  Deleting relations', ['proforma_id' => $proforma->id]);

            if (method_exists($proforma, 'items')) {
                $deleted = $proforma->items()->delete();
                \Log::info('ðŸ”¸ Items deleted', ['count' => $deleted]);
            }
            if (method_exists($proforma, 'approvals')) {
                $deleted = $proforma->approvals()->delete();
                \Log::info('ðŸ”¸ Approvals deleted', ['count' => $deleted]);
            }

            $proforma->delete();
            \Log::info('ðŸ—‘ Proforma deleted (soft)', ['proforma_id' => $proforma->id]);
        });

        return redirect()
            ->route('sales.proformas.index')
            ->with('success', 'Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø­Ø°Ù Ø´Ø¯.');
    } catch (\Throwable $e) {
        \Log::error('âŒ Proforma delete failed', [
            'proforma_id' => $proforma->id ?? null,
            'error'       => $e->getMessage(),
            'trace'       => $e->getTraceAsString(),
        ]);

        return back()->with('error', 'Ø®Ø·Ø§ Ø¯Ø± Ø­Ø°Ù Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ±. Ù„Ø·ÙØ§Ù‹ Ø¯ÙˆØ¨Ø§Ø±Ù‡ ØªÙ„Ø§Ø´ Ú©Ù†ÛŒØ¯.');
    }
}

    

    
    private function runAutomationRulesIfNeeded(\App\Models\Proforma $proforma): void
    {
        try {
            $stage = strtolower(trim($proforma->approval_stage ?? $proforma->proforma_stage));
    
            Log::debug('ðŸš€ runAutomationRulesIfNeeded', [
                'proforma_id'      => $proforma->id,
                'stage'            => $stage,
                'first_approved_by'=> $proforma->first_approved_by,
                'approved_by'      => $proforma->approved_by,
            ]);
    
            // ÙÙ‚Ø· ÙˆÙ‚ØªÛŒ Ù…Ø±Ø­Ù„Ù‡ ÛŒÚ©ÛŒ Ø§Ø² Ø§ÛŒÙ† Ø¯Ùˆ Ø¨Ø§Ø´Ù‡ Ø§Ø¯Ø§Ù…Ù‡ Ø¨Ø¯Ù‡
            if (! in_array($stage, ['send_for_approval', 'awaiting_second_approval'])) {
                Log::info('â­ï¸ Skipped: Stage not relevant for approvals', ['current_stage' => $stage]);
                return;
            }
    
            $rule = AutomationRule::with(['approvers.user'])
                ->where('proforma_stage', 'send_for_approval')
                ->first();
    
            if (! $rule) {
                Log::warning('âš ï¸ No automation rule found for send_for_approval');
                return;
            }
    
            // ðŸ“Œ Ø°Ø®ÛŒØ±Ù‡â€ŒØ³Ø§Ø²ÛŒ automation_rule_id Ø¯Ø± Ù¾Ø±ÙˆÙØ±Ù…Ø§
            if ($proforma->automation_rule_id !== $rule->id) {
                $proforma->automation_rule_id = $rule->id;
                $proforma->save();
                Log::info('ðŸ’¾ automation_rule_id saved to proforma', [
                    'proforma_id'       => $proforma->id,
                    'automation_rule_id'=> $rule->id
                ]);
            }
    
            $approvers = $rule->approvers ?? collect();
    
            Log::info('ðŸ‘¥ Approvers found', [
                'count' => $approvers->count(),
                'list'  => $approvers->map(fn($a) => [
                    'priority' => $a->priority,
                    'user_id'  => $a->user_id,
                    'name'     => optional($a->user)->name,
                ])->toArray(),
                'emergency_approver_id' => $rule->emergency_approver_id,
            ]);
    
            // ØªØ¹ÛŒÛŒÙ† Ù†ÙØ± Ø¨Ø¹Ø¯ÛŒ
            if (empty($proforma->first_approved_by)) {
                $nextApproverId = optional($approvers->firstWhere('priority', 1))->user_id;
                $nextStep = 1;
            } elseif (empty($proforma->approved_by)) {
                $nextApproverId = optional($approvers->firstWhere('priority', 2))->user_id
                    ?? $rule->emergency_approver_id;
                $nextStep = 2;
            } else {
                Log::info('âœ… Proforma already fully approved');
                return;
            }
    
            if (! $nextApproverId) {
                Log::warning('âš ï¸ No next approver determined', ['proforma_id' => $proforma->id]);
                return;
            }
    
            // Ù¾Ø§Ú©â€ŒØ³Ø§Ø²ÛŒ pendingâ€ŒÙ‡Ø§ÛŒ Ù‚Ø¨Ù„ÛŒ Ø¨Ù‡ Ø¬Ø² Ù†ÙØ± Ø¨Ø¹Ø¯ÛŒ
            $proforma->approvals()
                ->where('status', 'pending')
                ->where('user_id', '!=', $nextApproverId)
                ->delete();
    
            // Ø§ÛŒØ¬Ø§Ø¯ ÛŒØ§ Ø¨Ø±ÙˆØ²Ø±Ø³Ø§Ù†ÛŒ Ø±Ú©ÙˆØ±Ø¯ ØªØ§ÛŒÛŒØ¯
            $approval = $proforma->approvals()->updateOrCreate(
                ['user_id' => $nextApproverId, 'status' => 'pending'], 
                ['step' => $nextStep]
            );
    
            Log::info('ðŸ“ Pending approval set', [
                'approval_id' => $approval->id,
                'user_id'     => $nextApproverId,
                'step'        => $nextStep
            ]);
    
            // Ø§Ø±Ø³Ø§Ù„ Ù†ÙˆØªÛŒÙÛŒÚ©ÛŒØ´Ù†
            $user = User::find($nextApproverId);
            if ($user && method_exists($user, 'notify')) {
                try {
                    $user->notify(FormApprovalNotification::fromModel($proforma, auth()->id() ?? 0));
                    Log::info('ðŸ“¨ Notification sent', [
                        'to_user_id'   => $user->id,
                        'to_user_name' => $user->name,
                        'proforma_id'  => $proforma->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('ðŸ“­ Notification failed', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        }
        catch (\Exception $e) {
            Log::error('âŒ Error in runAutomationRulesIfNeeded', [
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
                'proforma_stage' => 'send_for_approval', // ðŸ”¹ Ù‡Ù…Ú¯Ø§Ù…â€ŒØ³Ø§Ø²ÛŒ
            ])->save();
    
            // Ø§Ø¬Ø±Ø§ÛŒ Ø§ØªÙˆÙ…Ø§Ø³ÛŒÙˆÙ† Ø¨Ø¹Ø¯ Ø§Ø² Ø¢Ù¾Ø¯ÛŒØª
            $this->runAutomationRulesIfNeeded($proforma);
        });
    
        return redirect()
            ->route('sales.proformas.index')
            ->with('success', 'Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø¨Ø±Ø§ÛŒ ØªØ§ÛŒÛŒØ¯ÛŒÙ‡ Ø§Ø±Ø³Ø§Ù„ Ø´Ø¯.');
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
    
                // Ø±Ú©ÙˆØ±Ø¯Ù Ù…Ø±Ø­Ù„Ù‡â€ŒÛŒ Ø¯Ø± Ø§Ù†ØªØ¸Ø§Ø±
                $pending = $approvals->firstWhere('status', 'pending');
                if (! $pending) {
                    throw new \RuntimeException('Ù‡ÛŒÚ† Ù…Ø±Ø­Ù„Ù‡â€ŒÛŒ Ø¯Ø± Ø§Ù†ØªØ¸Ø§Ø±ÛŒ Ø¨Ø±Ø§ÛŒ ØªØ§ÛŒÛŒØ¯ ÙˆØ¬ÙˆØ¯ Ù†Ø¯Ø§Ø±Ø¯.');
                }
    
                // Ø­Ø§Ù„Øª 1: Ø®ÙˆØ¯Ù ØªØ§ÛŒÛŒØ¯Ú©Ù†Ù†Ø¯Ù‡â€ŒÛŒ Ø§ØµÙ„ÛŒ
                $current = $approvals->firstWhere('user_id', $userId);
    
                // Ø­Ø§Ù„Øª 2: Ø§Ú¯Ø± Ø§ØµÙ„ÛŒ Ù†Ø¨ÙˆØ¯ØŒ Ø¨Ø±Ø±Ø³ÛŒ emergency approver Ø±ÙˆÛŒ Ù‡Ù…Ø§Ù† pending
                $asEmergency = false;
                if (! $current) {
                    $rule = $proforma->automationRule()->first();
                    if ($rule && (int) $rule->emergency_approver_id === (int) $userId) {
                        $current = $pending;   // Ø§Ø¬Ø§Ø²Ù‡ Ø¨Ø¯Ù‡ emergency Ù‡Ù…Ø§Ù† Ù…Ø±Ø­Ù„Ù‡â€ŒÛŒ pending Ø±Ø§ ØªØ§ÛŒÛŒØ¯ Ú©Ù†Ø¯
                        $asEmergency = true;
                    }
                }
    
                if (! $current) {
                    throw new \RuntimeException('Ø´Ù…Ø§ Ù…Ø¬Ø§Ø² Ø¨Ù‡ ØªØ§ÛŒÛŒØ¯ Ø§ÛŒÙ† Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ù†ÛŒØ³ØªÛŒØ¯.');
                }
                if ($current->status !== 'pending') {
                    throw new \RuntimeException('Ø´Ù…Ø§ Ù‚Ø¨Ù„Ø§Ù‹ Ø§ÛŒÙ† Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø±Ø§ ØªØ§ÛŒÛŒØ¯ Ú©Ø±Ø¯Ù‡â€ŒØ§ÛŒØ¯.');
                }
    
                // Ø±Ø¹Ø§ÛŒØª ØªØ±ØªÛŒØ¨ Ù…Ø±Ø§Ø­Ù„: Ø§Ú¯Ø± Ù‚Ø¨Ù„ Ø§Ø² Ø§ÛŒÙ† Ø±Ú©ÙˆØ±Ø¯ØŒ Ø¢ÛŒØªÙ…ÛŒ Ù‡Ù†ÙˆØ² approved Ù†Ø´Ø¯Ù‡ØŒ Ø®Ø·Ø§ Ø¨Ø¯Ù‡
                $idx     = $approvals->search(fn ($a) => (int) $a->id === (int) $current->id);
                $blocker = $approvals->take($idx)->first(fn ($a) => $a->status !== 'approved');
                if ($blocker) {
                    $who = optional($blocker->approver)->name ?: ('Ú©Ø§Ø±Ø¨Ø± #' . $blocker->user_id);
                    throw new \RuntimeException("Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø¯Ø± Ø§Ù†ØªØ¸Ø§Ø± ØªØ§ÛŒÛŒØ¯ {$who} Ø§Ø³Øª.");
                }
    
                // ØªØ§ÛŒÛŒØ¯ Ø§ÛŒÙ† Ù…Ø±Ø­Ù„Ù‡
                $current->update([
                    'status'      => 'approved',
                    'approved_at' => now(),
                ]);
    
                $step = (int) ($current->step ?? 1);
    
                if ($step === 1) {
                    if (empty($proforma->first_approved_by)) {
                        // Ú†Ù‡ Ø§ØµÙ„ÛŒ Ú†Ù‡ Ø§Ø¶Ø·Ø±Ø§Ø±ÛŒØŒ Ù‡Ù…Ø§Ù† Ú©Ø§Ø±Ø¨Ø± ÙØ¹Ù„ÛŒ Ø±Ø§ Ø«Ø¨Øª Ú©Ù†
                        $proforma->first_approved_by = $userId;
                    }
    
                    $proforma->fill([
                        'approval_stage' => 'awaiting_second_approval',
                        'proforma_stage' => 'awaiting_second_approval', // Ù‡Ù…Ú¯Ø§Ù…â€ŒØ³Ø§Ø²ÛŒ
                    ])->save();
    
                    $this->runAutomationRulesIfNeeded($proforma);
    
                } elseif ($step === 2) {
                    $proforma->fill([
                        'approved_by'    => $userId,
                        'approval_stage' => 'approved',
                        'proforma_stage' => 'approved', // Ù‡Ù…Ú¯Ø§Ù…â€ŒØ³Ø§Ø²ÛŒ
                    ])->save();
                }
    
                // Ø¨Ø±Ø±Ø³ÛŒ Ø§ÛŒÙ†Ú©Ù‡ Ù†ÙØ± Ø¯ÙˆÙ… ØªØ¹Ø±ÛŒÙ Ù†Ø´Ø¯Ù‡ Ùˆ pending Ø¯ÛŒÚ¯Ø±ÛŒ ÙˆØ¬ÙˆØ¯ Ù†Ø¯Ø§Ø±Ø¯
                $rule = $proforma->automationRule()->with('approvers')->first();
                $hasSecondApprover = $rule && $rule->approvers()->where('priority', 2)->exists();
    
                $hasPending = $proforma->approvals()
                    ->where('status', 'pending')
                    ->exists();
    
                if (! $hasPending && $step === 1 && ! $hasSecondApprover) {
                    $proforma->fill([
                        'approved_by'    => $userId,
                        'approval_stage' => 'approved',
                        'proforma_stage' => 'approved', // Ù‡Ù…Ú¯Ø§Ù…â€ŒØ³Ø§Ø²ÛŒ
                    ])->save();
                }
    
                \Log::info('âœ… Proforma approval progressed', [
                    'proforma_id' => $proforma->id,
                    'by_user'     => $userId,
                    'step'        => $step,
                    'stage'       => $proforma->approval_stage,
                    'as_emergency'=> $asEmergency,
                ]);
            });
    
            return back()->with('success', 'Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª ØªØ§ÛŒÛŒØ¯ Ø´Ø¯.');
    
        } catch (\Throwable $e) {
            \Log::error('âŒ Proforma approve failed', [
                'proforma_id' => $proforma->id ?? null,
                'error'       => $e->getMessage(),
            ]);
    
            return back()->with('error', $e->getMessage());
        }
        
    }

        public function reject(Proforma $proforma)
    {
        $this->authorize('approve', $proforma); // Ù‡Ù…Ø§Ù† policy Ú©Ù‡ Ø¨Ø±Ø§ÛŒ approve Ø§Ø³ØªÙØ§Ø¯Ù‡ Ù…ÛŒâ€ŒÚ©Ù†ÛŒ

        try {
            \DB::transaction(function () use ($proforma) {
                $userId = auth()->id();

                // Ø§Ú¯Ø± Ù‚Ø¨Ù„Ø§Ù‹ Ù†Ù‡Ø§ÛŒÛŒ Ø´Ø¯Ù‡ (approved/rejected) Ø§Ø¯Ø§Ù…Ù‡ Ù†Ø¯Ù‡
                if (in_array($proforma->approval_stage, ['approved','rejected'], true)) {
                    throw new \RuntimeException('Ø§ÛŒÙ† Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ù‚Ø¨Ù„Ø§Ù‹ Ù†Ù‡Ø§ÛŒÛŒ Ø´Ø¯Ù‡ Ø§Ø³Øª.');
                }

                // approvals Ø±Ø§ Ø¨Ø§ Ù„Ø§Ú© Ø¨Ø®ÙˆØ§Ù†
                $approvals = $proforma->approvals()
                    ->with('approver')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                // Ù…Ø±Ø­Ù„Ù‡â€ŒÛŒ Ø¯Ø± Ø§Ù†ØªØ¸Ø§Ø±
                $pending = $approvals->firstWhere('status', 'pending');
                if (! $pending) {
                    throw new \RuntimeException('Ù‡ÛŒÚ† Ù…Ø±Ø­Ù„Ù‡â€ŒÛŒ Ø¯Ø± Ø§Ù†ØªØ¸Ø§Ø±ÛŒ Ø¨Ø±Ø§ÛŒ Ø±Ø¯ ÙˆØ¬ÙˆØ¯ Ù†Ø¯Ø§Ø±Ø¯.');
                }

                // Ø­Ø§Ù„Øª 1: ØªØ§ÛŒÛŒØ¯Ú©Ù†Ù†Ø¯Ù‡/Ø±Ø¯Ú©Ù†Ù†Ø¯Ù‡ Ø§ØµÙ„ÛŒ Ù‡Ù…ÛŒÙ† pending Ø§Ø³Øª
                $current = $approvals->firstWhere('user_id', $userId);

                // Ø­Ø§Ù„Øª 2: Ø§Ú¯Ø± Ø§ØµÙ„ÛŒ Ù†Ø¨ÙˆØ¯ØŒ Ø¨Ø±Ø±Ø³ÛŒ emergency approver Ø¨Ø±Ø§ÛŒ Ù‡Ù…Ø§Ù† pending
                $asEmergency = false;
                if (! $current) {
                    $rule = $proforma->automationRule()->first();
                    if ($rule && (int) $rule->emergency_approver_id === (int) $userId) {
                        $current = $pending;   // Ø§Ø¬Ø§Ø²Ù‡ Ø¨Ø¯Ù‡ Ø§Ø¶Ø·Ø±Ø§Ø±ÛŒ Ù‡Ù…Ø§Ù† pending Ø±Ø§ Ø±Ø¯ Ú©Ù†Ø¯
                        $asEmergency = true;
                    }
                }

                if (! $current) {
                    throw new \RuntimeException('Ø´Ù…Ø§ Ù…Ø¬Ø§Ø² Ø¨Ù‡ Ø±Ø¯ Ø§ÛŒÙ† Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ù†ÛŒØ³ØªÛŒØ¯.');
                }

                // ÙÙ‚Ø· Ø±ÙˆÛŒ pending Ù…ÛŒâ€ŒØªÙˆØ§Ù† ØªØµÙ…ÛŒÙ… Ú¯Ø±ÙØª
                if ($current->status !== 'pending') {
                    throw new \RuntimeException('Ø¨Ø±Ø§ÛŒ Ø§ÛŒÙ† Ù…Ø±Ø­Ù„Ù‡ Ù‚Ø¨Ù„Ø§Ù‹ ØªØµÙ…ÛŒÙ…â€ŒÚ¯ÛŒØ±ÛŒ Ø´Ø¯Ù‡ Ø§Ø³Øª.');
                }

                // Ø±Ø¹Ø§ÛŒØª ØªØ±ØªÛŒØ¨ Ù…Ø±Ø§Ø­Ù„ (Ø§Ú¯Ø± Ù‚Ø¨Ù„ Ø§Ø² Ø§ÛŒÙ† Ø±Ú©ÙˆØ±Ø¯ØŒ Ø¢ÛŒØªÙ…ÛŒ Ù‡Ù†ÙˆØ² approved Ù†Ø´Ø¯Ù‡ØŒ Ø¨Ù„ÙˆÚ©Ù‡)
                $idx     = $approvals->search(fn ($a) => (int) $a->id === (int) $current->id);
                $blocker = $approvals->take($idx)->first(fn ($a) => $a->status !== 'approved');
                if ($blocker) {
                    $who = optional($blocker->approver)->name ?: ('Ú©Ø§Ø±Ø¨Ø± #' . $blocker->user_id);
                    throw new \RuntimeException("Ø±Ø¯ Ø§Ù…Ú©Ø§Ù†â€ŒÙ¾Ø°ÛŒØ± Ù†ÛŒØ³ØªØ› Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø¯Ø± Ø§Ù†ØªØ¸Ø§Ø± ØªØµÙ…ÛŒÙ… {$who} Ø§Ø³Øª.");
                }

                // Ø±Ø¯ Ù‡Ù…ÛŒÙ† Ù…Ø±Ø­Ù„Ù‡
                $current->update([
                    'status'      => 'rejected',
                    'approved_at' => now(),
                    'approved_by' => $userId,
                ]);

                // Ø³Øª Ú©Ø±Ø¯Ù† ÙˆØ¶Ø¹ÛŒØª Ú©Ù„ÛŒ Ù¾Ø±ÙˆÙÙˆØ±Ù…Ø§ Ø¨Ù‡ Ø±Ø¯â€ŒØ´Ø¯Ù‡
                $proforma->fill([
                    'approval_stage' => 'rejected',
                    'proforma_stage' => 'rejected',
                ])->save();

                // Ù¾Ø§Ú© Ú©Ø±Ø¯Ù† ØªÙ…Ø§Ù… pendingÙ‡Ø§ÛŒ Ø¯ÛŒÚ¯Ø± ØªØ§ ÙØ±Ø§ÛŒÙ†Ø¯ Ù…ØªÙˆÙ‚Ù Ø´ÙˆØ¯
                $proforma->approvals()
                    ->where('status', 'pending')
                    ->delete();

                \Log::info('â›” Proforma rejected', [
                    'proforma_id' => $proforma->id,
                    'by_user'     => $userId,
                    'step'        => (int) ($current->step ?? 1),
                    'as_emergency'=> $asEmergency,
                ]);
            });

            return back()->with('success', 'Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø±Ø¯ Ø´Ø¯.');

        } catch (\Throwable $e) {
            \Log::error('âŒ Proforma reject failed', [
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

        // Ø¬Ù„ÙˆÚ¯ÛŒØ±ÛŒ Ø§Ø² Ø­Ø°Ù Ø¢ÛŒØªÙ…â€ŒÙ‡Ø§ÛŒ Ø¯Ø± ÙˆØ¶Ø¹ÛŒØª ØªØ§ÛŒÛŒØ¯
        $ids = Proforma::query()
            ->whereIn('id', $data['ids'])
            ->where('proforma_stage', '!=', 'send_for_approval')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return back()->with('error', 'Ù‡ÛŒÚ† Ø¢ÛŒØªÙ… Ù‚Ø§Ø¨Ù„ Ø­Ø°ÙÛŒ Ø§Ù†ØªØ®Ø§Ø¨ Ù†Ø´Ø¯Ù‡ Ø§Ø³Øª.');
        }

        try {
            DB::transaction(function () use ($ids) {
                Proforma::query()->whereIn('id', $ids)->delete(); // Ù‡Ù…ÛŒÙ† Ú©Ø§ÙÛŒ Ø§Ø³Øª
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Ø®Ø·Ø§ Ø¯Ø± Ø­Ø°Ù Ú¯Ø±ÙˆÙ‡ÛŒ: '.$e->getMessage());
        }

        return back()->with('success', $ids->count().' Ù…ÙˆØ±Ø¯ Ø­Ø°Ù Ø´Ø¯.');
    }

    

}

