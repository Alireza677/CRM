<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::query()
            ->select([
                'purchase_orders.*',
                'suppliers.name as supplier_name',
                'users.name as assigned_to_name'
            ])
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users', 'purchase_orders.assigned_to', '=', 'users.id');

        // Global search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('purchase_orders.subject', 'like', "%{$search}%")
                  ->orWhere('suppliers.name', 'like', "%{$search}%");
            });
        }

        // Column-specific filters
        if ($request->has('subject')) {
            $query->where('purchase_orders.subject', 'like', "%{$request->subject}%");
        }
        if ($request->has('supplier')) {
            $query->where('suppliers.name', 'like', "%{$request->supplier}%");
        }
        if ($request->has('purchase_date')) {
            $query->whereDate('purchase_orders.purchase_date', $request->purchase_date);
        }
        if ($request->has('status')) {
            $query->where('purchase_orders.status', $request->status);
        }
        if ($request->has('assigned_to')) {
            $query->where('users.name', 'like', "%{$request->assigned_to}%");
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if ($sortField === 'supplier') {
            $query->orderBy('suppliers.name', $sortDirection);
        } elseif ($sortField === 'assigned_to') {
            $query->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy("purchase_orders.{$sortField}", $sortDirection);
        }

        $purchaseOrders = $query->paginate(10)->withQueryString();

        return view('inventory.purchase-orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('inventory.purchase-orders.create', compact('suppliers', 'users'));
    }
} 