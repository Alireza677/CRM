@props(['onClick' => ''])

<button {{ $attributes->merge(['class' => 'fixed top-4 right-4 z-50 p-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200']) }}
        @click="{{ $onClick }}">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
</button> 