<?php

namespace App\Http\Controllers;

use App\Models\OnlineChatGroup;
use App\Models\OnlineChatMembership;
use App\Models\OnlineChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnlineChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $groups = OnlineChatGroup::query()
            ->forUser($user)
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
            ->filter(fn ($id) => $id !== $user->id)
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
            'is_active' => ['nullable', 'boolean'],
        ]);

        $group->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

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

        return response()->json([
            'data' => $messages->map(fn (OnlineChatMessage $m) => $this->transformMessage($m))->values(),
        ]);
    }

    public function sendMessage(Request $request, OnlineChatGroup $group)
    {
        $this->authorize('sendMessage', $group);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $group->messages()->create([
            'body' => $data['body'],
            'sender_id' => $request->user()->id,
        ]);

        $group->touch();
        $message->load('sender:id,name,email');

        return response()->json([
            'data' => $this->transformMessage($message),
        ], 201);
    }

    private function transformMessage(OnlineChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender' => [
                'id' => $message->sender?->id,
                'name' => $message->sender?->name,
                'email' => $message->sender?->email,
            ],
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
