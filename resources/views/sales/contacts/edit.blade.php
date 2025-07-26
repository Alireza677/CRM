@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight mb-6">
            {{ __('ویرایش مخاطب') }}
        </h2>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form method="POST" action="{{ route('sales.contacts.update', $contact->id) }}" class="space-y-4">
                    @include('sales.contacts._form', ['contact' => $contact])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    document.getElementById('open-org-modal').addEventListener('click', function () {
        document.getElementById('org-modal').classList.remove('hidden');
    });

    document.getElementById('close-org-modal').addEventListener('click', function () {
        document.getElementById('org-modal').classList.add('hidden');
    });

    document.querySelectorAll('.org-select-item').forEach(function (item) {
        item.addEventListener('click', function () {
            const name = this.dataset.name;
            document.getElementById('company_input').value = name;
            document.getElementById('org-modal').classList.add('hidden');
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#company_select').select2({
            tags: true,
            placeholder: 'انتخاب یا نوشتن نام سازمان',
            dir: 'rtl',
            language: {
                noResults: () => "هیچ سازمانی یافت نشد",
                inputTooShort: () => "برای جستجو تایپ کنید...",
            },
        });
    });
</script>
@endpush