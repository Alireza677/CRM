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
    public function __construct()
    {
        $this->middleware('auth');
        // Restrict deletion to users with the 'admin' role
        $this->middleware('role:admin')->only('destroy');
    }
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
        $sortDirection = strtolower((string) $request->get('direction', 'desc'));

        // Normalize sort direction and field to prevent invalid inputs
        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $allowedSortFields = [
            'created_at', 'name', 'unit_price', 'stock_quantity',
            'code', 'serial_number', 'is_active', 'receiver_name', 'percentage'
        ];
        $specialSortFields = ['category', 'supplier'];
        if (! in_array($sortField, array_merge($allowedSortFields, $specialSortFields), true)) {
            $sortField = 'created_at';
        }
        
        if ($sortField === 'category') {
            $query->orderBy('categories.name', $sortDirection);
        } elseif ($sortField === 'supplier') {
            $query->orderBy('suppliers.name', $sortDirection);
        } else {
            $query->orderBy("products.{$sortField}", $sortDirection);
        }

        $allowedPerPage = [10, 25, 50, 100, 200];
        $perPage = (int) $request->get('per_page', 10);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $products = $query->paginate($perPage)->withQueryString();

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
        // Temporary debug
        \Log::info('Product creation request:', $request->all());

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
    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('inventory.products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'supplier']);
        return view('inventory.products.show', compact('product'));
    }

    public function update(Request $request, Product $product)
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

        $validated['has_vat'] = (bool) ($validated['has_vat'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $product->update($validated);

        return redirect()
            ->route('inventory.products.index')
            ->with('success', 'محصول با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('inventory.products.index')
            ->with('success', 'محصول با موفقیت حذف شد.');
    }
} 
