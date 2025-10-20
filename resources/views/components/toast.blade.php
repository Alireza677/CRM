@if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false, 4000)" class="mb-3 p-3 bg-green-50 border border-green-200 text-green-800 rounded">
        {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false, 6000)" class="mb-3 p-3 bg-red-50 border border-red-200 text-red-800 rounded">
        @foreach($errors->all() as $err)
            <div>{{ $err }}</div>
        @endforeach
    </div>
@endif

