@extends('layouts.guest')

@section('content')
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <label for="password">{{ __('Password') }}</label>

            <input id="password" class="block mt-1 w-full"
                   type="password"
                   name="password"
                   required autocomplete="current-password" >

            @error('password')
                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
@endsection
