@extends('layout.panel')
@section('title', 'Thanh toán')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Thanh toán nhà tuyển dụng</h1>
            <p class="page-sub">Xem và xác nhận giao dịch. Chỉ xác nhận khi có căn cứ hợp lệ.</p>
        </div>
    </header>

    <nav class="chip-row" aria-label="Lọc theo trạng thái">
        <a @class(['filter-chip', 'is-active' => ! $status]) href="{{ route('admin.payments.index') }}">Tất cả</a>
        @foreach (\App\Enums\PaymentStatus::cases() as $case)
            <a @class(['filter-chip', 'is-active' => $status === $case->value]) href="{{ route('admin.payments.index', ['status' => $case->value]) }}">{{ $case->label() }}</a>
        @endforeach
    </nav>

    @if ($payments->isEmpty())
        <div class="card empty-deck" style="margin:24px auto">
            <div class="emoji">💳</div>
            <h2>Chưa có giao dịch</h2>
            <p>Khi nhà tuyển dụng tạo yêu cầu thanh toán, giao dịch sẽ hiện ở đây.</p>
        </div>
    @else
        <div class="card panel-table-wrap">
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Công ty</th>
                        <th>Gói</th>
                        <th>Số tiền</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td><strong>{{ $payment->reference }}</strong><small>{{ $payment->created_at->format('d/m/Y H:i') }}</small></td>
                            <td>{{ $payment->company->name }}</td>
                            <td>{{ $payment->servicePlan->name }}</td>
                            <td>{{ $payment->formattedAmount() }}</td>
                            <td><span class="status-tag" data-status="{{ $payment->status === \App\Enums\PaymentStatus::Paid ? 'open' : 'hidden' }}">{{ $payment->status->label() }}</span></td>
                            <td class="t-right"><a class="btn btn-ghost btn-sm" href="{{ route('admin.payments.show', $payment) }}">Chi tiết</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $payments->links('pagination.simple') }}
    @endif
@endsection
