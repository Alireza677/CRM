<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Arr;


class OrganizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin')->only('destroy');
    }
    public function index(Request $request)
    {
        $query = Organization::query()
            ->select('organizations.*', 'users.name as assigned_to_name')
            ->leftJoin('users', 'organizations.assigned_to', '=', 'users.id')
            ->with('contacts');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('organizations.name', 'like', "%{$search}%")
                  ->orWhere('organizations.phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Handle special cases for sorting
        if ($sortField === 'assigned_to_name') {
            $query->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy("organizations.{$sortField}", $sortDirection);
        }

        // Per-page selection (with allowed values and persistence)
        $allowedPerPage = [10, 25, 50, 100, 250];
        $perPage = (int) $request->get('per_page', session('orgs_per_page', 10));
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        // Remember user's choice in session when explicitly provided
        if ($request->has('per_page')) {
            session(['orgs_per_page' => $perPage]);
        }

        $organizations = $query->paginate($perPage)->withQueryString();

        return view('sales.organizations.index', compact('organizations', 'perPage'));
    }

    public function create()
    {
        $users = User::all(); // یا فیلترشده در صورت نیاز
        $contacts = Contact::all(); // این خط مهم است
    
        return view('sales.organizations.create', compact('users', 'contacts'));
    }
    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'website'     => 'nullable|url|max:255',
            'address'     => 'nullable|string',
            'state' => 'nullable|string|max:255',
            'city'  => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
    
            // می‌تونیم صحت مخاطب را چک کنیم، اما در create استفاده‌اش نمی‌کنیم
            'contact_id'  => 'nullable|exists:contacts,id',
        ], [
            'name.required'      => 'نام سازمان الزامی است.',
            'website.url'        => 'فرمت آدرس وب‌سایت نامعتبر است.',
            'assigned_to.exists' => 'کاربر انتخاب شده معتبر نیست.',
            'contact_id.exists'  => 'مخاطب انتخاب شده معتبر نیست.',
        ]);
    
        // مهم: contact_id را از داده‌های ساخت سازمان حذف کن
        $org = Organization::create(Arr::except($validated, ['contact_id']));
    
        // اگر مخاطب انتخاب شده بود، به این سازمان وصلش کن
        if (!empty($validated['contact_id'])) {
            Contact::whereKey($validated['contact_id'])
                ->update(['organization_id' => $org->id]);
        }
    
        return redirect()->route('sales.organizations.index')
            ->with('success', 'سازمان با موفقیت ایجاد شد.');
    }
    public function edit($id)
    {
        $organization = Organization::findOrFail($id);
        $users = User::all();
        $contacts = Contact::all();

        return view('sales.organizations.edit', compact('organization', 'users', 'contacts'));
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'state' => 'nullable|string|max:255',
            'city'  => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ], [
            'name.required' => 'نام سازمان الزامی است.',
            'website.url' => 'فرمت آدرس وب‌سایت نامعتبر است.',
            'assigned_to.exists' => 'کاربر انتخاب شده معتبر نیست.',
        ]);

        $organization = Organization::findOrFail($id);
        $organization->update($validated);

        return redirect()->route('sales.organizations.index')
            ->with('success', 'سازمان با موفقیت بروزرسانی شد.');
    }

    public function show($id)
    {
        $organization = Organization::with('contacts')->findOrFail($id);
        return view('sales.organizations.show', compact('organization'));
    }

    public function loadTab(Organization $organization, $tab)
    {
        $view = "sales.organizations.tabs.$tab";
        if (!view()->exists($view)) {
            abort(404);
        }

        $data = ['organization' => $organization];

        if ($tab === 'opportunities') {
            $organization->load('opportunities');
        }
        if ($tab === 'contacts') {
            $organization->load('contacts');
        }
        if ($tab === 'updates') {
            $data['activities'] = \Spatie\Activitylog\Models\Activity::where('subject_type', \App\Models\Organization::class)
                ->where('subject_id', $organization->id)
                ->latest()
                ->get();
        }

        return view($view, $data);
    }
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('selected', []);
        if (!empty($ids)) {
            Organization::whereIn('id', $ids)->delete();
        }

        return redirect()->route('sales.organizations.index')->with('success', 'سازمان‌های انتخاب‌شده با موفقیت حذف شدند.');
    }


} 
