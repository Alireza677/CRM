<?php

namespace App\Http\Controllers;

use App\Models\OnlineChatGroup;
use App\Models\OnlineChatMembership;
use App\Models\OnlineChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OnlineChatController extends Controller
{
    private function unreadCountsQuery(User $user)
    {
        return OnlineChatGroup::query()
            ->forUser($user)
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('sender_id', '!=', $user->id)
                    ->whereRaw(
                        'online_chat_messages.id > COALESCE((select last_read_message_id from online_chat_group_user where online_chat_group_user.online_chat_group_id = online_chat_groups.id and user_id = ?), 0)',
                        [$user->id]
                    );
            }]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $groups = $this->unreadCountsQuery($user)
            ->with([
                'memberships' => function ($q) {
                    $q->with('user:id,name,email');
                },
                'lastMessage.sender:id,name,email',
            ])
            ->orderByDesc('updated_at')
            ->get();

        $activeGroupId = $request->integer('group_id') ?: ($groups->first()->id ?? null);
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('chat.index', [
            'groups' => $groups,
            'users' => $users,
            'activeGroupId' => $activeGroupId,
        ]);
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'members' => ['array'],
            'members.*' => ['integer', 'exists:users,id'],
        ]);

        $user = $request->user();

        $group = OnlineChatGroup::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $group->memberships()->create([
            'user_id' => $user->id,
            'role' => OnlineChatMembership::ROLE_OWNER,
        ]);

        $memberIds = collect($data['members'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id !== (int) $user->id)
            ->unique();

        foreach ($memberIds as $memberId) {
            $group->memberships()->create([
                'user_id' => $memberId,
                'role' => OnlineChatMembership::ROLE_MEMBER,
            ]);
        }

        return redirect()
            ->route('chat.index', ['group_id' => $group->id])
            ->with('success', 'گروه چت با موفقیت ایجاد شد.');
    }

    public function updateGroup(Request $request, OnlineChatGroup $group)
    {
        $this->authorize('update', $group);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'call_link' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $group->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'call_link' => $data['call_link'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'call_link' => $group->call_link,
                ],
            ]);
        }

        return back()->with('success', 'تنظیمات گروه به‌روزرسانی شد.');
    }

    public function addMember(Request $request, OnlineChatGroup $group)
    {
        $this->authorize('manageMembers', $group);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::in([
                OnlineChatMembership::ROLE_OWNER,
                OnlineChatMembership::ROLE_ADMIN,
                OnlineChatMembership::ROLE_MEMBER,
            ])],
        ]);

        $group->memberships()->updateOrCreate(
            ['user_id' => $data['user_id']],
            ['role' => $data['role']]
        );

        return back()->with('success', 'عضو جدید به گروه افزوده شد.');
    }

    public function removeMember(Request $request, OnlineChatGroup $group, User $user)
    {
        $this->authorize('manageMembers', $group);

        $membership = $group->memberships()->where('user_id', $user->id)->firstOrFail();

        if (
            $membership->role === OnlineChatMembership::ROLE_OWNER &&
            $group->memberships()->where('role', OnlineChatMembership::ROLE_OWNER)->count() <= 1
        ) {
            return back()->with('error', 'حذف آخرین مالک گروه مجاز نیست.');
        }

        $membership->delete();

        return back()->with('success', 'عضو از گروه حذف شد.');
    }

    public function destroyGroup(Request $request, OnlineChatGroup $group)
    {
        $this->authorize('delete', $group);

        $group->delete();

        return redirect()
            ->route('chat.index')
            ->with('success', 'گروه حذف شد.');
    }

    public function messages(Request $request, OnlineChatGroup $group)
    {
        $this->authorize('view', $group);

        $afterId = $request->integer('after_id');

        $messages = $group->messages()
            ->with('sender:id,name,email')
            ->when($afterId, function ($q) use ($afterId) {
                $q->where('id', '>', $afterId);
            })
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        if ($messages->isNotEmpty()) {
            $lastId = $messages->max('id');
            $membership = $group->memberships()->where('user_id', $request->user()->id)->first();
            if ($membership && (int) $membership->last_read_message_id < $lastId) {
                $membership->update(['last_read_message_id' => $lastId]);
            }
        }

        return response()->json([
            'data' => $messages->map(fn (OnlineChatMessage $m) => $this->transformMessage($m))->values(),
        ]);
    }

    public function unreadCounts(Request $request)
    {
        $user = $request->user();
        $groups = $this->unreadCountsQuery($user)->get(['id']);

        return response()->json([
            'data' => $groups->mapWithKeys(fn (OnlineChatGroup $g) => [$g->id => (int) $g->unread_count]),
        ]);
    }

    public function sendMessage(Request $request, OnlineChatGroup $group)
    {
        $this->authorize('sendMessage', $group);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,zip,rar'],
            'image_title' => ['nullable', 'string', 'max:255'],
        ]);

        if (
            !filled($data['body'] ?? null)
            && ! $request->hasFile('images')
            && ! $request->hasFile('files')
        ) {
            throw ValidationException::withMessages([
                'body' => 'متن پیام یا تصویر یا فایل الزامی است.',
            ]);
        }

        $messages = [];
        $body = $data['body'] ?? '';
        $imageTitle = $data['image_title'] ?? null;

        if (filled($body)) {
            $messages[] = $group->messages()->create([
                'body' => $body,
                'sender_id' => $request->user()->id,
            ]);
        }

        foreach ($request->file('images', []) as $image) {
            $imagePath = $image->store('chat-attachments', 'public');
            $messages[] = $group->messages()->create([
                'body' => '',
                'sender_id' => $request->user()->id,
                'image_path' => $imagePath,
                'image_title' => $imageTitle,
            ]);
        }

        foreach ($request->file('files', []) as $file) {
            $filePath = $file->store('chat-files', 'public');
            $messages[] = $group->messages()->create([
                'body' => '',
                'sender_id' => $request->user()->id,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime' => $file->getMimeType(),
            ]);
        }

        $group->touch();
        if (!empty($messages)) {
            $lastId = collect($messages)->max('id');
            $membership = $group->memberships()->where('user_id', $request->user()->id)->first();
            if ($membership && (int) $membership->last_read_message_id < $lastId) {
                $membership->update(['last_read_message_id' => $lastId]);
            }
        }
        foreach ($messages as $message) {
            $message->load('sender:id,name,email');
        }

        return response()->json([
            'data' => collect($messages)->map(fn (OnlineChatMessage $m) => $this->transformMessage($m))->values(),
        ], 201);
    }

    public function messageImage(Request $request, OnlineChatGroup $group, OnlineChatMessage $message)
    {
        $this->authorize('view', $group);

        if ($message->online_chat_group_id !== $group->id || ! $message->image_path) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($message->image_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($message->image_path));
    }

    public function messageFile(Request $request, OnlineChatGroup $group, OnlineChatMessage $message)
    {
        $this->authorize('view', $group);

        if ($message->online_chat_group_id !== $group->id || ! $message->file_path) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($message->file_path)) {
            abort(404);
        }

        return response()->download(
            Storage::disk('public')->path($message->file_path),
            $message->file_name ?? basename($message->file_path)
        );
    }

    private function transformMessage(OnlineChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'image_url' => $message->image_path
                ? route('chat.groups.messages.image', [
                    'group' => $message->online_chat_group_id,
                    'message' => $message->id,
                ])
                : null,
            'image_title' => $message->image_title,
            'file_url' => $message->file_path
                ? route('chat.groups.messages.file', [
                    'group' => $message->online_chat_group_id,
                    'message' => $message->id,
                ])
                : null,
            'file_name' => $message->file_name,
            'file_size' => $message->file_size,
            'file_mime' => $message->file_mime,
            'sender' => [
                'id' => $message->sender?->id,
                'name' => $message->sender?->name,
                'email' => $message->sender?->email,
            ],
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
