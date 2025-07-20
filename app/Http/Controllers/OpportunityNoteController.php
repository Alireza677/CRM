<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\OpportunityNote;
use Illuminate\Http\Request;

class OpportunityNoteController extends Controller
{
    /**
     * ذخیره یادداشت جدید برای فرصت فروش
     */
    public function store(Request $request, Opportunity $opportunity)
    {
        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $opportunity->notes()->create([
            'content' => $request->input('content'),
            'user_id' => auth()->id(),
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'یادداشت با موفقیت ذخیره شد.');
    }
}
