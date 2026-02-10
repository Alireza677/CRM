<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Crud\Crud;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        return Crud::index('suppliers', $request);
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
