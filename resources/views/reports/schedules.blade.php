@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'Reports', 'url' => route('reports.index')],
        ['title' => 'Schedules'],
    ];
@endphp

@section('content')
<div class="py-6" dir="rtl">
    @include('components.toast')
    <h1 class="text-xl font-semibold mb-4">Schedules for: {{ $report->title }}</h1>

    @if(session('success'))
        <div class="mb-3 p-2 bg-green-50 border border-green-200 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded shadow p-4 mb-6">
        <h2 class="font-semibold mb-3">Add Schedule</h2>
        <form action="{{ route('reports.schedules.store', $report) }}" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="block mb-1">Frequency</label>
                <select name="frequency" class="w-full border rounded p-2" required>
                    <option value="daily">O�U^O�O\u0015U+U�</option>
                    <option value="weekly">U�U?O�U_UO</option>
                    <option value="monthly">U.O\u0015U�O\u0015U+U�</option>
                    <option value="custom">O3U?O\u0015O�O'UO</option>
                </select>
            </div>
            <div>
                <label class="block mb-1">Time of day</label>
                <input type="time" name="time_of_day" class="w-full border rounded p-2" required value="08:00">
            </div>
            <div>
                <label class="block mb-1">Weekday (0..6) when weekly</label>
                <input type="number" name="weekday" class="w-full border rounded p-2" min="0" max="6" placeholder="O\u0015OrO�UOO\u0015O�UO">
            </div>
            <div>
                <label class="block mb-1">Day of month when monthly</label>
                <input type="number" name="day_of_month" class="w-full border rounded p-2" min="1" max="31" placeholder="O\u0015OrO�UOO\u0015O�UO">
            </div>
            <div class="md:col-span-2">
                <label class="block mb-1">Emails (comma separated)</label>
                <input type="text" name="emails[]" class="w-full border rounded p-2" placeholder="example1@site.com, example2@site.com">
            </div>
            <div>
                <label class="block mb-1">Export format</label>
                <select name="export_format" class="w-full border rounded p-2" required>
                    <option value="csv">CSV</option>
                    <option value="xlsx">XLSX</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>
            <div class="flex items-center">
                <label class="inline-flex items-center"><input type="checkbox" name="active" value="1" checked class="mr-2"> Active</label>
            </div>
            <div class="md:col-span-2">
                <button class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                <a href="{{ route('reports.show',$report) }}" class="px-4 py-2 bg-gray-200 rounded">Back</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-3">Existing Schedules</h2>
        <div class="overflow-auto">
            <table class="min-w-full text-right">
                <thead>
                <tr>
                    <th class="px-2 py-1 bg-gray-50 border">Frequency</th>
                    <th class="px-2 py-1 bg-gray-50 border">Time</th>
                    <th class="px-2 py-1 bg-gray-50 border">Weekday</th>
                    <th class="px-2 py-1 bg-gray-50 border">Day</th>
                    <th class="px-2 py-1 bg-gray-50 border">Format</th>
                    <th class="px-2 py-1 bg-gray-50 border">Emails</th>
                    <th class="px-2 py-1 bg-gray-50 border">Active</th>
                    <th class="px-2 py-1 bg-gray-50 border">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($schedules as $s)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $s->frequency }}</td>
                        <td class="px-2 py-1">{{ $s->time_of_day }}</td>
                        <td class="px-2 py-1">{{ $s->weekday ?? '�?"' }}</td>
                        <td class="px-2 py-1">{{ $s->day_of_month ?? '�?"' }}</td>
                        <td class="px-2 py-1">{{ strtoupper($s->export_format) }}</td>
                        <td class="px-2 py-1">{{ implode(', ', (array)$s->emails) }}</td>
                        <td class="px-2 py-1">{{ $s->active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-2 py-1">
                            <form action="{{ route('reports.schedules.destroy', [$report, $s]) }}" method="post" onsubmit="return confirm('Delete?');">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">O-O�U?</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-2 py-4 text-center text-gray-500">�?"</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
