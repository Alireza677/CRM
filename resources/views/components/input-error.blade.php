@props(['messages', 'message' => null])

@if ($message)
    <div {{ $attributes->merge(['class' => 'text-sm text-red-600 mt-1']) }}>
        {{ $message }}
    </div>
@elseif ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1 mt-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
