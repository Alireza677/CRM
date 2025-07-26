@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="text-xl font-bold mb-4">مشاهده سازمان</h2>

    <div class="bg-white shadow rounded p-4">
        <p><strong>نام:</strong> {{ $organization->name }}</p>
        <p><strong>ایمیل:</strong> {{ $organization->email }}</p>
        <p><strong>شماره تماس:</strong> {{ $organization->phone }}</p>
        <p><strong>آدرس:</strong> {{ $organization->address }}</p>
        <p><strong>وب‌سایت:</strong> 
            <a href="{{ $organization->website }}" class="text-blue-600" target="_blank">
                {{ $organization->website }}
            </a>
        </p>
        <p><strong>صنعت:</strong> {{ $organization->industry }}</p>
        <p><strong>شهر:</strong> {{ $organization->city }}</p>
        <p><strong>استان:</strong> {{ $organization->state }}</p>
        <p><strong>یادداشت:</strong> {{ $organization->notes }}</p>
    </div>

    @if($organization->contacts->count())
        <h3 class="mt-6 font-bold">مخاطبین مرتبط</h3>
        <ul class="list-disc pl-5 mt-2 space-y-1">
            @foreach($organization->contacts as $contact)
                <li>
                    <a href="{{ route('sales.contacts.show', $contact->id) }}" class="text-indigo-600 hover:underline">
                        {{ $contact->first_name }} {{ $contact->last_name }} - {{ $contact->mobile }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif


    <div class="mt-6">
        <a href="{{ route('sales.organizations.edit', $organization->id) }}" class="btn btn-primary">ویرایش</a>
        <a href="{{ route('sales.organizations.index') }}" class="btn btn-secondary">بازگشت</a>
    </div>
</div>
@endsection
