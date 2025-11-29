<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\OnlineMeeting;
use App\Models\Opportunity;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnlineMeetingController extends Controller
{
    public function index(): View
    {
        $meetings = OnlineMeeting::query()
            ->with(['related'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('sales.online-meetings.index', compact('meetings'));
    }

    public function create(): View
    {
        $opportunities = Opportunity::select('id', 'name')->orderByDesc('id')->limit(80)->get();
        $contacts = Contact::select('id', 'first_name', 'last_name', 'company')->orderByDesc('id')->limit(80)->get();
        $organizations = Organization::select('id', 'name')->orderByDesc('id')->limit(80)->get();

        return view('sales.online-meetings.create', compact('opportunities', 'contacts', 'organizations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'notes' => ['nullable', 'string'],
            'related_type' => ['nullable', 'in:opportunity,contact,organization'],
            'related_id' => ['nullable', 'integer'],
        ], [], [
            'title' => 'عنوان جلسه',
            'scheduled_at' => 'تاریخ و ساعت جلسه',
            'duration_minutes' => 'مدت جلسه (دقیقه)',
            'related_type' => 'نوع ارتباط',
            'related_id' => 'رکورد مرتبط',
        ]);

        $map = [
            'opportunity' => Opportunity::class,
            'contact' => Contact::class,
            'organization' => Organization::class,
        ];

        $relatedType = $validated['related_type'] ?? null;
        $relatedId = $validated['related_id'] ?? null;

        if ($relatedType && isset($map[$relatedType])) {
            $modelClass = $map[$relatedType];
            $exists = $modelClass::where('id', $relatedId)->exists();
            abort_unless($exists, 404, 'رکورد انتخاب‌شده پیدا نشد.');
            $storedRelatedType = $modelClass;
        } else {
            $storedRelatedType = null;
            $relatedId = null;
        }

        $roomName = OnlineMeeting::generateUniqueRoomName(
            $storedRelatedType,
            $relatedId,
            $validated['scheduled_at'] ?? now()
        );

        $meeting = OnlineMeeting::create([
            'title' => $validated['title'],
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'notes' => $validated['notes'],
            'related_type' => $storedRelatedType,
            'related_id' => $relatedId,
            'room_name' => $roomName,
            'jitsi_url' => 'https://meet.jit.si/' . $roomName,
            'created_by_id' => auth()->id(),
            'updated_by_id' => auth()->id(),
        ]);

        return redirect()
            ->route('sales.online-meetings.show', $meeting)
            ->with('status', 'جلسه آنلاین ایجاد شد و لینک Jitsi آماده است.');
    }

    public function show(OnlineMeeting $onlineMeeting): View
    {
        $onlineMeeting->load(['related']);

        return view('sales.online-meetings.show', compact('onlineMeeting'));
    }
}
