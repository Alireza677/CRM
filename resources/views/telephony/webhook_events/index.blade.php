@extends('layouts.app')

@section('content')
    <div class="py-10 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Telephony Webhook Events</h1>
                    <p class="text-sm text-gray-500 mt-1">Latest 50 webhook deliveries</p>
                </div>
                <div>
                    <a href="{{ route('telephony.phone-calls.index') }}"
                       class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                        View phone calls
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-700">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Trace ID</th>
                            <th class="px-4 py-3 text-left">Received</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">IP</th>
                            <th class="px-4 py-3 text-left">Payload Preview</th>
                            <th class="px-4 py-3 text-left">Phone Call</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($events as $event)
                            @php
                                $payloadPreview = $event->payload
                                    ? json_encode($event->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                                    : '';
                                $payloadPreview = \Illuminate\Support\Str::limit($payloadPreview, 140);
                            @endphp
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-gray-900">{{ $event->trace_id }}</td>
                                <td class="px-4 py-3">
                                    {{ optional($event->received_at)->format('Y-m-d H:i:s') ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                        {{ $event->processing_status === 'processed' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $event->processing_status === 'failed' ? 'bg-red-100 text-red-700' : '' }}
                                        {{ $event->processing_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                        {{ $event->processing_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $event->ip ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 break-words max-w-md">{{ $payloadPreview ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($event->phone_call_id)
                                        <a href="{{ route('telephony.phone-calls.show', $event->phone_call_id) }}"
                                           class="text-blue-600 hover:text-blue-800">
                                            #{{ $event->phone_call_id }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    No webhook events recorded yet.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
