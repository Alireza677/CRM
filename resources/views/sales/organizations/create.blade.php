@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'سازمان‌ها', 'url' => route('sales.organizations.index')],
        ['title' => 'ایجاد سازمان جدید']
    ];
@endphp

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
            {{ __('ایجاد سازمان جدید') }}
        </h2>

        @include('sales.organizations.partials._form', [
            'action' => route('sales.organizations.store'),
            'method' => 'POST',
            'organization' => null,
            'users' => $users,
            'contacts' => $contacts
        ])
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openContactsModal() {
        document.getElementById('contactsModal').classList.remove('hidden');
    }

    function closeContactsModal() {
        document.getElementById('contactsModal').classList.add('hidden');
    }

    function selectContact(id, name) {
        document.getElementById('contact_id').value = id;
        document.getElementById('contact_display').value = name;
        closeContactsModal();
    }
</script>
@endpush