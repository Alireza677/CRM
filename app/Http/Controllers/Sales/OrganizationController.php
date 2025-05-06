<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::query()
            ->select('organizations.*', 'users.name as assigned_to_name')
            ->leftJoin('users', 'organizations.assigned_to', '=', 'users.id');

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

        $organizations = $query->paginate(10)->withQueryString();

        return view('sales.organizations.index', compact('organizations'));
    }

    public function create()
    {
        $users = User::all();
        return view('sales.organizations.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ], [
            'name.required' => 'نام سازمان الزامی است.',
            'website.url' => 'فرمت آدرس وب‌سایت نامعتبر است.',
            'assigned_to.exists' => 'کاربر انتخاب شده معتبر نیست.',
        ]);

        Organization::create($validated);

        return redirect()->route('sales.organizations.index')
            ->with('success', 'سازمان با موفقیت ایجاد شد.');
    }
} 