@extends('layouts.guest')

@section('content')
    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif
    

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email">{{ __('نام کاربری') }}</label>
            <input id="email" class="block mt-1 w-full" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password">{{ __('رمز عبور') }}</label>
            <input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password">
            @error('password')
                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('مرا به خاطر بسپار') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('رمزتو فراموش کردی؟') }}
                </a>
            @endif

            <button type="submit" class="ms-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                {{ __('ورود') }}
            </button>
        </div>
    </form>
@endsection
