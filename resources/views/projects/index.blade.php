@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">پروژه‌ها</h1>
        <a href="{{ route('projects.create') }}" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
            + ایجاد پروژه
        </a>
    </div>

    <div class="bg-white rounded shadow">
        <table class="min-w-full text-right">
            <thead class="border-b">
                <tr class="text-gray-600">
                    <th class="py-3 px-4">#</th>
                    <th class="py-3 px-4">نام</th>
                    <th class="py-3 px-4">توضیحات</th>
                    <th class="py-3 px-4">اقدامات</th>
                </tr>
            </thead>
            <tbody>
            @forelse($projects as $project)
                <tr class="border-b">
                    <td class="py-3 px-4">{{ $project->id }}</td>
                    <td class="py-3 px-4 font-semibold">{{ $project->name }}</td>
                    <td class="py-3 px-4 text-gray-700">{{ Str::limit($project->description, 120) }}</td>
                    <td class="py-3 px-4">
                        <a href="{{ route('projects.show', $project) }}" class="text-blue-700 hover:underline">نمایش</a>
                    </td>
                </tr>
            @empty
                <tr><td class="py-6 px-4 text-gray-500" colspan="4">هیچ پروژه‌ای ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>
</div>
@endsection
