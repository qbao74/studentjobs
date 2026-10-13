@extends('layout.panel')
@section('title', $payment->reference)

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('admin.payments.index') }}">Thanh toán</a></p>
            <h1 class="page-title">{{ $payment->reference }}</h1>
            <p class="page-sub">{{ $payment->company->name }} · {{ $payment->user->name }}</p>
        </div>
        <span class="status-tag" data-status="{{ $payment->status === \App\Enums\PaymentStatus::Paid ? 'open' : 'hidden' }}">{{ $payment->status->label() }}</span>
    </header>

    <section class="card section">
        <div class="info-grid">
            <div class="info-item"><div><small>Gói</small>{{ $payment->servicePlan->name }}</div></div>
            <div class="info-item"><div><small>Số tiền</small>{{ $payment->formattedAmount() }}</div></div>
            <div class="info-item"><div><small>Mã cổng</small>{{ $payment->provider_txn_id ?: 'Chưa có' }}</div></div>
            <div class="info-item"><div><small>Hạn yêu cầu</small>{{ $payment->expires_at?->format('d/m/Y H:i') ?? '—' }}</div></div>
        </div>

        @if ($payment->isPending())
            <div class="panel-actions" style="margin-top:16px">
                <form method="POST" action="{{ route('admin.payments.update', $payment) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decision" value="paid">
                    <button class="btn btn-primary" type="submit">Xác nhận đã thanh toán</button>
                </form>
                <form method="POST" action="{{ route('admin.payments.update', $payment) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decision" value="failed">
                    <button class="btn btn-ghost" type="submit">Đánh dấu thất bại</button>
                </form>
            </div>
        @endif
    </section>
@endsection
