@extends('layouts.app')
@section('content')
    <h2>تأییدیه‌های در انتظار من</h2>

    @forelse($pendingApprovals as $approval)
        <div class="card mb-3">
            <div class="card-body">
                <strong>موضوع:</strong> {{ $approval->approvable->subject ?? '---' }} <br>
                <a href="{{ route('sales.proformas.show', $approval->approvable_id) }}" class="btn btn-primary">
                    مشاهده پیش‌فاکتور
                </a>
            </div>
            <form method="POST" action="{{ route('approvals.action', $approval->id) }}" class="mt-2 flex gap-2">
                @csrf
                <button name="decision" value="approved" class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600">
                    تایید
                </button>
                <button name="decision" value="rejected" class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                    رد
                </button>
            </form>

        </div>
    @empty
        <p>در حال حاضر تأییدیه‌ای برای شما ثبت نشده است.</p>
    @endforelse
@endsection
