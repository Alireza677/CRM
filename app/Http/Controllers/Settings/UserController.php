<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Helpers\UserTransferHelper;


class UserController extends Controller
{
    // نمایش لیست کاربران
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('settings.users.index', compact('users'));
    }

    // فرم ایجاد کاربر جدید
    public function create()
    {
        return view('settings.users.create');
    }

    // ذخیره کاربر جدید در دیتابیس
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        $user->syncRoles($request->role);
        

        return redirect()->route('settings.users.index')
            ->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    public function edit(User $user)
    {
        return view('settings.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        return redirect()->route('settings.users.index')
            ->with('success', 'اطلاعات کاربر با موفقیت بروزرسانی شد.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('settings.users.index')
                ->with('error', 'شما نمی‌توانید حساب کاربری خود را حذف کنید.');
        }

        $user->delete();
        return redirect()->route('settings.users.index')
            ->with('success', 'کاربر با موفقیت حذف شد.');
    }

    public function reassign(Request $request)
{
    $request->validate([
        'user_id_to_delete' => 'required|exists:users,id',
        'new_user_id' => 'required|exists:users,id|different:user_id_to_delete',
    ]);

    $fromUser = User::findOrFail($request->user_id_to_delete);

    UserTransferHelper::transferAllData($fromUser->id, $request->new_user_id);

    $fromUser->delete();

    return redirect()->route('settings.users.index')->with('success', 'اطلاعات به کاربر دیگر منتقل و کاربر حذف شد.');
}


}
