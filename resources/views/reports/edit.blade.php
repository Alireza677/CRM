@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'Reports', 'url' => route('reports.index')],
        ['title' => 'Edit'],
    ];
@endphp

@section('content')
    <div class="py-6" dir="rtl">
        @include('components.toast')
        <h1 class="text-xl font-semibold mb-4">Edit Report: {{ $report->title }}</h1>

        <form action="{{ route('reports.update', $report) }}" method="post" class="bg-white p-4 rounded shadow space-y-4">
            @csrf
            @method('PUT')
            @include('reports._form', ['report' => $report])

            <div class="bg-white p-4 rounded border">
                <h3 class="font-semibold mb-2">Query Builder</h3>
                @include('reports._builder', ['report' => $report])
            </div>

            <div class="flex items-center gap-2">
                <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                <a href="{{ route('reports.show',$report) }}" class="px-4 py-2 bg-gray-200 rounded">Back</a>
            </div>
        </form>
    </div>
@endsection
