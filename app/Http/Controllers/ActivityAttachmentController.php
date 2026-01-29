<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivityAttachmentController extends Controller
{
    public function store(Request $request, Activity $activity)
    {
        $this->authorizeVisibility($activity, $request);

        $validated = $request->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,zip,rar,doc,docx,xls,xlsx,ppt,pptx,txt'],
        ]);

        foreach ((array) $request->file('attachments', []) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $path = $file->store('activity-attachments', 'public');
            $activity->attachments()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime' => $file->getClientMimeType(),
                'created_by_id' => $request->user()?->id,
            ]);
        }

        return redirect()->route('activities.show', $activity)->with('success', 'فایل‌ها بارگذاری شد.');
    }

    public function destroy(Activity $activity, Attachment $attachment)
    {
        $this->authorizeVisibility($activity, request());

        if (!($attachment->attachable instanceof Activity) || (int) $attachment->attachable->id !== (int) $activity->id) {
            abort(404);
        }

        if ($attachment->file_path) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $attachment->delete();

        return back()->with('success', 'فایل حذف شد.');
    }

    private function authorizeVisibility(Activity $activity, Request $request): void
    {
        $user = $request->user();
        abort_unless(
            !$activity->is_private || $activity->created_by_id === $user?->id || $activity->assigned_to_id === $user?->id,
            403,
            'اجازه دسترسی ندارید.'
        );
    }
}