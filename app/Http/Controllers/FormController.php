<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function index(Request $request)
    {
        $query = Form::query()
            ->select('forms.*', 'suppliers.name as supplier_name', 'users.name as assigned_user_name')
            ->leftJoin('suppliers', 'forms.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users', 'forms.assigned_to', '=', 'users.id');

        // Global search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('forms.title', 'like', "%{$search}%")
                    ->orWhere('forms.description', 'like', "%{$search}%")
                    ->orWhere('suppliers.name', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        // Column-specific filters
        if ($request->has('type')) {
            $query->where('forms.type', $request->type);
        }
        if ($request->has('title')) {
            $query->where('forms.title', 'like', "%{$request->title}%");
        }
        if ($request->has('supplier_name')) {
            $query->where('suppliers.name', 'like', "%{$request->supplier_name}%");
        }
        if ($request->has('status')) {
            $query->where('forms.status', $request->status);
        }
        if ($request->has('assigned_to')) {
            $query->where('forms.assigned_to', $request->assigned_to);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $forms = $query->paginate(10)->withQueryString();
        $users = User::all();

        return view('forms.index', compact('forms', 'users'));
    }

    public function create()
    {
        $types = Form::getTypes();
        $statuses = Form::getStatuses();
        $suppliers = Supplier::where('is_active', true)->get();
        $users = User::all();

        return view('forms.create', compact('types', 'statuses', 'suppliers', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'status' => 'required|string',
            'total' => 'required|numeric',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        Form::create($validated);

        return redirect()->route('forms.index')
            ->with('success', 'فرم با موفقیت ایجاد شد.');
    }

    public function show(Form $form)
    {
        return view('forms.show', compact('form'));
    }

    public function edit(Form $form)
    {
        $types = Form::getTypes();
        $statuses = Form::getStatuses();
        $suppliers = Supplier::where('is_active', true)->get();
        $users = User::all();

        return view('forms.edit', compact('form', 'types', 'statuses', 'suppliers', 'users'));
    }

    public function update(Request $request, Form $form)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'status' => 'required|string',
            'total' => 'required|numeric',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        $form->update($validated);

        return redirect()->route('forms.index')
            ->with('success', 'فرم با موفقیت بروزرسانی شد.');
    }

    public function destroy(Form $form)
    {
        $form->delete();

        return redirect()->route('forms.index')
            ->with('success', 'فرم با موفقیت حذف شد.');
    }
} 