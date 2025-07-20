<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query()
            ->select([
                'suppliers.*',
                'categories.name as category_name',
                'users.name as assigned_to_name'
            ])
            ->leftJoin('categories', 'suppliers.category_id', '=', 'categories.id')
            ->leftJoin('users', 'suppliers.assigned_to', '=', 'users.id');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('suppliers.name', 'like', "%{$search}%")
                  ->orWhere('suppliers.phone', 'like', "%{$search}%")
                  ->orWhere('suppliers.email', 'like', "%{$search}%")
                  ->orWhere('categories.name', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        if ($request->has('name')) {
            $query->where('suppliers.name', 'like', "%{$request->name}%");
        }
        if ($request->has('phone')) {
            $query->where('suppliers.phone', 'like', "%{$request->phone}%");
        }
        if ($request->has('email')) {
            $query->where('suppliers.email', 'like', "%{$request->email}%");
        }
        if ($request->has('category')) {
            $query->where('categories.name', 'like', "%{$request->category}%");
        }
        if ($request->has('assigned_to')) {
            $query->where('users.name', 'like', "%{$request->assigned_to}%");
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        if ($sortField === 'category') {
            $query->orderBy('categories.name', $sortDirection);
        } elseif ($sortField === 'assigned_to') {
            $query->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy("suppliers.{$sortField}", $sortDirection);
        }

        $suppliers = $query->paginate(10)->withQueryString();

        return view('inventory.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('inventory.suppliers.create', compact('categories', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'telegram_id' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'assigned_to' => 'required|exists:users,id',
            'province' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Supplier::create($validated);

        return redirect()->route('inventory.suppliers.index')->with('success', 'تأمین‌کننده با موفقیت ایجاد شد.');
    }

    public function show(Supplier $supplier)
    {
        return view('inventory.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('inventory.suppliers.edit', compact('supplier', 'categories', 'users'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'telegram_id' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'assigned_to' => 'required|exists:users,id',
            'province' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return redirect()->route('inventory.suppliers.index')->with('success', 'تأمین‌کننده با موفقیت ویرایش شد.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('inventory.suppliers.index')->with('success', 'تأمین‌کننده حذف شد.');
    }
}
