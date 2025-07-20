@extends('layouts.guest')

@section('content')
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <label for="name">{{ __('Name') }}</label>
            <input id="name" class="block mt-1 w-full" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')
                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <label for="email">{{ __('Email') }}</label>
            <input id="email" class="block mt-1 w-full" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            @error('email')
                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password">{{ __('Password') }}</label>
            <input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password">
            @error('password')
                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <label for="password_confirmation">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password">
            @error('password_confirmation')
                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="submit" class="ms-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                {{ __('Register') }}
            </button>
        </div>
    </form>
@endsection
