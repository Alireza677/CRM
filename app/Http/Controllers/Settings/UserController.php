<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Helpers\UserTransferHelper;

class UserController extends Controller
{
    /**
     * لیست کاربران
     */
    public function index()
    {
        // برای نمایش نقش‌ها بدون N+1
        $users = User::with('roles')->latest()->paginate(10);
        return view('settings.users.index', compact('users'));
    }

    /**
     * فرم ایجاد کاربر
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id','name']);
        return view('settings.users.create', compact('roles'));
    }

    /**
     * ذخیره کاربر جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','string','email','max:255','unique:users,email'],
            'password' => ['required','confirmed', Password::defaults()],
            'role'     => [
                'required','string',
                Rule::exists('roles','name')->where(fn($q) => $q->where('guard_name','web')),
            ],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // انتساب نقش
        $user->assignRole($validated['role']);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    /**
     * فرم ویرایش کاربر
     */
    public function edit(User $user)
    {
        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id','name']);
        return view('settings.users.edit', compact('user','roles'));
    }

    /**
     * بروزرسانی کاربر
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','string','email','max:255','unique:users,email,'.$user->id],
            'password' => ['nullable','confirmed', Password::defaults()],
            'role'     => [
                'required','string',
                Rule::exists('roles','name')->where(fn($q) => $q->where('guard_name','web')),
            ],
        ]);

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        // همگام‌سازی نقش
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'اطلاعات کاربر با موفقیت بروزرسانی شد.');
    }

    /**
     * حذف کاربر
     */
    public function destroy(User $user)
    {
        // جلوگیری از حذف خودِ کاربر
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('settings.users.index')
                ->with('error', 'شما نمی‌توانید حساب کاربری خود را حذف کنید.');
        }

        // جلوگیری از حذف آخرین ادمین
        if ($user->hasRole('Admin')) {
            $adminCount = User::role('Admin')->count();
            if ($adminCount <= 1) {
                return redirect()
                    ->route('settings.users.index')
                    ->with('error', 'حذف آخرین مدیر سیستم امکان‌پذیر نیست.');
            }
        }

        $user->delete();

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'کاربر با موفقیت حذف شد.');
    }

    /**
     * انتقال داده‌های کاربر و حذف او
     */
    public function reassign(Request $request)
    {
        $validated = $request->validate([
            'user_id_to_delete' => ['required','exists:users,id'],
            'new_user_id'       => ['nullable','exists:users,id','different:user_id_to_delete'],
        ]);

        $fromUser = User::findOrFail($validated['user_id_to_delete']);

        // انتقال همه‌ی داده‌های وابسته (در صورت نبود کاربر جایگزین، شناسه مقصد null می‌شود)
        $toUserId = $validated['new_user_id'] ?? null;
        UserTransferHelper::transferAllData($fromUser->id, $toUserId);

        // حذف کاربر مبدا
        $fromUser->delete();

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'اطلاعات به کاربر دیگر منتقل و کاربر حذف شد.');
    }
}
