<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class MailboxSettingsController extends Controller
{
    public function index()
    {
        return view('mail.index');
    }

    public function edit(Request $request)
    {
        $mailbox = $request->user()?->mailbox;

        return view('settings.mailbox.edit', [
            'mailbox'             => $mailbox,
            'encryptionOptions'   => ['none' => 'بدون رمزنگاری', 'ssl' => 'SSL', 'tls' => 'TLS'],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'email_address'    => ['required', 'email'],
            'imap_host'        => ['required', 'string', 'max:255'],
            'imap_port'        => ['required', 'integer', 'min:1', 'max:65535'],
            'imap_encryption'  => ['required', 'in:none,ssl,tls'],
            'smtp_host'        => ['required', 'string', 'max:255'],
            'smtp_port'        => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption'  => ['required', 'in:none,ssl,tls'],
            'username'         => ['required', 'string', 'max:255'],
            'password'         => ['nullable', 'string', 'min:6'],
            'is_active'        => ['sometimes', 'boolean'],
        ], [
            'email_address.required'   => 'ایمیل الزامی است.',
            'email_address.email'      => 'فرمت ایمیل معتبر نیست.',
            'imap_host.required'       => 'هاست IMAP را وارد کنید.',
            'imap_port.required'       => 'پورت IMAP الزامی است.',
            'imap_port.integer'        => 'پورت IMAP باید عددی باشد.',
            'imap_port.min'            => 'پورت IMAP نامعتبر است.',
            'imap_port.max'            => 'پورت IMAP نامعتبر است.',
            'imap_encryption.required' => 'نوع رمزنگاری IMAP را انتخاب کنید.',
            'imap_encryption.in'       => 'رمزنگاری IMAP فقط none/ssl/tls است.',
            'smtp_host.required'       => 'هاست SMTP را وارد کنید.',
            'smtp_port.required'       => 'پورت SMTP الزامی است.',
            'smtp_port.integer'        => 'پورت SMTP باید عددی باشد.',
            'smtp_port.min'            => 'پورت SMTP نامعتبر است.',
            'smtp_port.max'            => 'پورت SMTP نامعتبر است.',
            'smtp_encryption.required' => 'نوع رمزنگاری SMTP را انتخاب کنید.',
            'smtp_encryption.in'       => 'رمزنگاری SMTP فقط none/ssl/tls است.',
            'username.required'        => 'نام کاربری الزامی است.',
            'password.min'             => 'رمز عبور حداقل ۶ کاراکتر باشد.',
        ]);

        $mailbox = $user->mailbox ?: new Mailbox(['user_id' => $user->id]);

        $mailbox->fill([
            'email_address'   => $data['email_address'],
            'imap_host'       => $data['imap_host'],
            'imap_port'       => $data['imap_port'],
            'imap_encryption' => $data['imap_encryption'],
            'smtp_host'       => $data['smtp_host'],
            'smtp_port'       => $data['smtp_port'],
            'smtp_encryption' => $data['smtp_encryption'],
            'username'        => $data['username'],
            'is_active'       => $request->boolean('is_active', true),
        ]);

        if (!empty($data['password'])) {
            $mailbox->password_encrypted = Crypt::encryptString($data['password']);
        }

        $mailbox->save();

        return back()->with('success', 'تنظیمات ایمیل با موفقیت ذخیره شد.');
    }
}
