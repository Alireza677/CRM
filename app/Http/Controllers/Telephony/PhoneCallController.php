<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\PhoneCall;
use Illuminate\Http\Request;

class PhoneCallController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $direction = $request->input('direction');

        $phoneCalls = PhoneCall::query()
            ->with(['handledBy', 'customer'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('customer_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('source_identifier', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $filters = [
            'search' => $search,
            'status' => $status,
            'direction' => $direction,
        ];

        return view('telephony.phone_calls.index', compact('phoneCalls', 'filters'));
    }

    public function show(PhoneCall $phoneCall)
    {
        $phoneCall->load(['handledBy', 'customer']);

        return view('telephony.phone_calls.show', [
            'phoneCall' => $phoneCall,
        ]);
    }
}
