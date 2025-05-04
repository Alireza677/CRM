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

        // Global search
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

        // Column-specific filters
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

        // Sorting
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
} 