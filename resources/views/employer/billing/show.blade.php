@extends('layout.panel')
@section('title', 'Giao dịch '.$payment->reference)

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('employer.billing.index') }}">Thanh toán</a></p>
            <h1 class="page-title">{{ $payment->reference }}</h1>
            <p class="page-sub">{{ $payment->servicePlan->name }} · {{ $payment->formattedAmount() }}</p>
        </div>
        <span class="status-tag" data-status="{{ $payment->status === \App\Enums\PaymentStatus::Paid ? 'open' : ($payment->status === \App\Enums\PaymentStatus::Pending ? 'interview' : 'hidden') }}">{{ $payment->status->label() }}</span>
    </header>

    @if ($simulation)
        <div class="flash flash--ok sim-banner" role="status">
            <i data-lucide="flask-conical"></i>
            <div><strong>Chế độ mô phỏng.</strong> Các nút bên dưới giả lập kết quả cổng thanh toán, không phải chuyển khoản thật.</div>
        </div>
    @endif

    <section class="card section">
        <h2>Chi tiết giao dịch</h2>
        <div class="info-grid">
            <div class="info-item"><div><small>Mã giao dịch</small>{{ $payment->reference }}</div></div>
            <div class="info-item"><div><small>Số tiền (từ gói trên hệ thống)</small>{{ $payment->formattedAmount() }}</div></div>
            <div class="info-item"><div><small>Nhà cung cấp</small>{{ $payment->provider === 'simulation' ? 'Mô phỏng đồ án' : $payment->provider }}</div></div>
            <div class="info-item"><div><small>Mã tham chiếu cổng</small>{{ $payment->provider_txn_id ?: 'Chưa có' }}</div></div>
            <div class="info-item"><div><small>Tạo lúc</small>{{ $payment->created_at->format('d/m/Y H:i') }}</div></div>
            <div class="info-item"><div><small>Hết hạn yêu cầu</small>{{ $payment->expires_at?->format('d/m/Y H:i') ?? '—' }}</div></div>
            <div class="info-item"><div><small>Thanh toán lúc</small>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</div></div>
            <div class="info-item"><div><small>Hạn gói sau khi trả</small>{{ $payment->ends_on?->format('d/m/Y H:i') ?? 'Chưa kích hoạt' }}</div></div>
        </div>
        <p class="muted">Số tiền không lấy từ trình duyệt. Nếu form bị sửa, hệ thống vẫn ghi đúng giá gói {{ $payment->servicePlan->formattedPrice() }}.</p>

        @if ($payment->isPending() && $simulation)
            <div class="panel-actions" style="margin-top:16px">
                <form method="POST" action="{{ route('employer.billing.simulate', $payment) }}">
                    @csrf
                    <input type="hidden" name="success" value="1">
                    <button class="btn btn-primary" type="submit"><i data-lucide="circle-check"></i> Mô phỏng thanh toán thành công</button>
                </form>
                <form method="POST" action="{{ route('employer.billing.simulate', $payment) }}">
                    @csrf
                    <input type="hidden" name="success" value="0">
                    <button class="btn btn-ghost" type="submit"><i data-lucide="circle-x"></i> Mô phỏng thanh toán thất bại</button>
                </form>
            </div>
        @elseif ($payment->status === \App\Enums\PaymentStatus::Paid)
            <p class="muted" style="margin-top:12px">Gói đã kích hoạt. Gửi lại cùng mã giao dịch sẽ không gia hạn lần hai.</p>
        @endif
    </section>
@endsection
