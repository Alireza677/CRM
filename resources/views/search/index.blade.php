@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto py-8">
        <h2 class="text-xl font-semibold mb-4">نتایج جستجو برای "{{ $query }}"</h2>

        @if($contacts->count() || $opportunities->count() || $organizations->count())
            @if($contacts->count())
                <h3 class="text-lg font-bold mt-6">مخاطبین</h3>
                <ul class="list-disc pl-5 text-sm">
                    @foreach($contacts as $contact)
                        <li>
                            <a href="{{ route('sales.contacts.show', $contact->id) }}" class="text-blue-600 hover:underline">
                                {{ $contact->first_name }} {{ $contact->last_name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($opportunities->count())
                <h3 class="text-lg font-bold mt-6">فرصت‌ها</h3>
                <ul class="list-disc pl-5 text-sm">
                    @foreach($opportunities as $opportunity)
                        <li>
                            <a href="{{ route('sales.opportunities.show', $opportunity->id) }}" class="text-blue-600 hover:underline">
                                {{ $opportunity->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($organizations->count())
                <h3 class="text-lg font-bold mt-6">سازمان‌ها</h3>
                <ul class="list-disc pl-5 text-sm">
                    @foreach($organizations as $organization)
                        <li>
                            <a href="{{ route('sales.organizations.index') }}" class="text-blue-600 hover:underline">
                                {{ $organization->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @else
            <p class="text-gray-500 mt-6">نتیجه‌ای یافت نشد.</p>
        @endif
    </div>
@endsection
