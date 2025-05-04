<?php

namespace App\Http\Controllers;

use App\Models\PrintTemplate;
use Illuminate\Http\Request;

class PrintTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = PrintTemplate::query();

        // Global search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Column-specific filters
        if ($request->has('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->has('module')) {
            $query->where('module', $request->module);
        }
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $templates = $query->paginate(10)->withQueryString();

        return view('print-templates.index', compact('templates'));
    }

    public function create()
    {
        $modules = PrintTemplate::getModules();
        return view('print-templates.create', compact('modules'));
    }
} 