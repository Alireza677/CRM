<?php

namespace App\Http\Controllers\Sales;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $documents = Document::latest()->paginate(10);
    $documents = Document::with('opportunity')->latest()->paginate(10);


    $breadcrumb = [
        ['title' => 'داشبورد', 'url' => route('dashboard')],
        ['title' => 'اسناد'],
    ];

    return view('sales.documents.index', compact('documents', 'breadcrumb'));
    }


    // سایر متدها (create, store...) بعداً اضافه می‌شن

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
{
    $opportunityId = $request->query('opportunity_id');

    $breadcrumb = [
        ['title' => 'داشبورد', 'url' => route('dashboard')],
        ['title' => 'اسناد', 'url' => route('sales.documents.index')],
        ['title' => 'ثبت سند جدید'],
    ];

    return view('sales.documents.create', compact('opportunityId', 'breadcrumb'));
}



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'file' => 'required|file|max:5120', // حداکثر 5MB
        'opportunity_id' => 'nullable|exists:opportunities,id',
    ]);

    $path = $request->file('file')->store('documents', 'public');

    \App\Models\Document::create([
        'title' => $validated['title'],
        
        'file_path' => $path,
        'opportunity_id' => $validated['opportunity_id'] ?? null,
    ]);

    return redirect()->route('sales.documents.index')->with('success', 'سند با موفقیت ثبت شد.');
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
