@if(session('success'))
    <div class="mb-3 p-3 bg-green-50 border border-green-200 text-green-800 rounded flex items-start justify-between gap-2"
         data-toast
         data-timeout="4000">
        <div>{{ session('success') }}</div>
        <button type="button"
                class="text-green-700/70 hover:text-green-900 text-lg leading-none"
                data-toast-close
                aria-label="Close">&times;</button>
    </div>
@endif
@if($errors->any())
    <div class="mb-3 p-3 bg-red-50 border border-red-200 text-red-800 rounded flex items-start justify-between gap-2"
         data-toast
         data-timeout="6000">
        <div>
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
        <button type="button"
                class="text-red-700/70 hover:text-red-900 text-lg leading-none"
                data-toast-close
                aria-label="Close">&times;</button>
    </div>
@endif
