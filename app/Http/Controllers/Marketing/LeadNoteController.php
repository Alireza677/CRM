<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesLead;
use App\Models\Note;

class LeadNoteController extends Controller
{
    public function store(Request $request, SalesLead $lead)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $lead->notes()->create([
            'body' => $request->body,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'یادداشت با موفقیت ثبت شد.');
    }
}
