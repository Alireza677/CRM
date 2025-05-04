@props(['value', 'for' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-gray-700']) }}
       @if($for) for="{{ $for }}" @endif>
    {{ $value ?? $slot }}
</label>
