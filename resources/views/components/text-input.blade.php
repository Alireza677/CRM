@props(['disabled' => false, 'value' => null])

<input {{ $attributes->merge(['class' => 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}
       @disabled($disabled)
       @if($value) value="{{ $value }}" @endif>
