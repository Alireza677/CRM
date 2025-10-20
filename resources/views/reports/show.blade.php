@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'Reports', 'url' => route('reports.index')],
        ['title' => $report->title],
    ];
@endphp

@section('content')
    <div class="py-6" dir="rtl">
        @include('components.toast')
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">{{ $report->title }}</h1>
            <div class="space-x-2 space-x-reverse">
                @can('update',$report)
                    <a href="{{ route('reports.edit',$report) }}" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600 text-white rounded"><span>Edit</span></a>
                @endcan
                @can('delete',$report)
                <form action="{{ route('reports.destroy',$report) }}" method="post" class="inline" onsubmit="return confirm('Delete?');">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex items-center gap-1 px-3 py-1 bg-red-600 text-white rounded"><span>Delete</span></button>
                </form>
                @endcan
                <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 px-3 py-1 bg-gray-200 rounded">Back</a>
            </div>
        </div>

        <div class="bg-white rounded shadow p-4 mb-4">
            <div class="mb-2"><span class="text-gray-500">Description:</span> {{ $report->description ?: '-' }}</div>
            <div class="mb-2"><span class="text-gray-500">Model:</span> {{ $report->model ?: '-' }}</div>
            <div class="mb-2"><span class="text-gray-500">Visibility:</span>
                @php
                    $label = $report->visibility === 'public' ? 'Public' : ($report->visibility==='shared' ? 'Shared' : 'Private');
                    $color = $report->visibility === 'public' ? 'bg-green-100 text-green-800' : ($report->visibility==='shared' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800');
                @endphp
                <span class="px-2 py-1 text-xs rounded {{ $color }}">{{ $label }}</span>
            </div>
            <div class="mb-2"><span class="text-gray-500">Owner:</span> {{ $report->creator->name ?? '-' }}</div>
            <div class="mb-2"><span class="text-gray-500">Created:</span> {{ jdate($report->created_at)->format('Y/m/d H:i') }}</div>
            <div class="mb-2"><span class="text-gray-500">Status:</span> {{ $report->is_active ? 'Active' : 'Inactive' }}</div>
        </div>

        @can('share', $report)
        <div class="bg-white rounded shadow p-4">
            <h2 class="font-semibold mb-3">Sharing</h2>
            <form action="{{ route('reports.share', $report) }}" method="post" class="space-y-3">
                @csrf
                @method('PUT')

                <div>
                    <label class="block mb-1">Visibility</label>
                    <select name="visibility" class="w-full border rounded p-2">
                        <option value="private" @selected($report->visibility==='private')>Private</option>
                        <option value="public" @selected($report->visibility==='public')>Public</option>
                        <option value="shared" @selected($report->visibility==='shared')>Shared</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1">Shared Users</label>
                    @php $sharedIds = $report->sharedUsers->pluck('id')->all(); @endphp
                    <select name="shared_user_ids[]" multiple class="w-full border rounded p-2">
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(in_array($u->id, old('shared_user_ids', $sharedIds)))>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block mb-1">Users Can Edit</label>
                    @php $canEditIds = $report->sharedUsers->where('pivot.can_edit',true)->pluck('id')->all(); @endphp
                    <select name="shared_can_edit_ids[]" multiple class="w-full border rounded p-2">
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(in_array($u->id, old('shared_can_edit_ids', $canEditIds)))>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-gray-500">Shared users with edit permission can modify the report.</small>
                </div>

                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
        @endcan

        <div class="mt-4">
            <a href="{{ route('reports.run', $report) }}" class="px-4 py-2 bg-green-600 text-white rounded">Run Report</a>
        </div>
    </div>
@endsection
