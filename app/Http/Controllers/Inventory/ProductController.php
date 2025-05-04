<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->select([
                'products.*',
                'categories.name as category_name',
                'suppliers.name as supplier_name'
            ])
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.part_number', 'like', "%{$search}%")
                  ->orWhere('products.manufacturer', 'like', "%{$search}%")
                  ->orWhere('products.series', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if ($sortField === 'category') {
            $query->orderBy('categories.name', $sortDirection);
        } elseif ($sortField === 'supplier') {
            $query->orderBy('suppliers.name', $sortDirection);
        } else {
            $query->orderBy("products.{$sortField}", $sortDirection);
        }

        $products = $query->paginate(10)->withQueryString();

        return view('inventory.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('inventory.products.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sales_start_date' => 'nullable|date',
            'sales_end_date' => 'nullable|date|after_or_equal:sales_start_date',
            'support_start_date' => 'nullable|date',
            'support_end_date' => 'nullable|date|after_or_equal:support_start_date',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'manufacturer' => 'nullable|string|max:255',
            'series' => 'nullable|string|max:255',
            'length' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'has_vat' => 'boolean',
            'is_active' => 'boolean',
            'website' => 'nullable|url|max:255',
            'part_number' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'thermal_power' => 'nullable|numeric|min:0',
            'commission' => 'nullable|numeric|min:0|max:100',
            'purchase_cost' => 'nullable|numeric|min:0',
        ]);

        $product = Product::create($validated);

        return redirect()
            ->route('inventory.products.index')
            ->with('success', 'محصول با موفقیت ایجاد شد.');
    }
} 