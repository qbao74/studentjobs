@extends('layout.panel')
@section('title', 'Thanh toán')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Gói dịch vụ &amp; thanh toán</h1>
            <p class="page-sub">Thanh toán định kỳ theo tháng để tin tuyển dụng tiếp tục hiện với sinh viên.</p>
        </div>
        <span class="status-tag" data-status="{{ $company->subscriptionState() === 'active' ? 'open' : ($company->subscriptionState() === 'expiring' ? 'interview' : 'hidden') }}">{{ $company->subscriptionStateLabel() }}</span>
    </header>

    @if ($simulation)
        <div class="flash flash--ok sim-banner" role="status">
            <i data-lucide="flask-conical"></i>
            <div><strong>Chế độ mô phỏng (đồ án).</strong> Không thu tiền thật, không kết nối ngân hàng. Gói chỉ được kích hoạt khi giao dịch được xác nhận thành công.</div>
        </div>
    @endif

    <div class="panel-grid">
        <section class="card section">
            <h2>Hạn sử dụng hiện tại</h2>
            <div class="info-grid">
                <div class="info-item"><div><small>Gói</small>{{ $company->servicePlan->name ?? 'Chưa có' }}</div></div>
                <div class="info-item"><div><small>Ngày hết hạn</small>{{ $company->subscription_ends_at?->format('d/m/Y H:i') ?? 'Chưa thanh toán' }}</div></div>
                <div class="info-item"><div><small>Số tin đang mở</small>{{ $jobs->where('status', \App\Enums\JobStatus::Open)->count() }}</div></div>
                <div class="info-item"><div><small>Giới hạn gói</small>{{ $company->servicePlan?->jobLimitLabel() ?? 'Chưa có gói' }}</div></div>
            </div>
        </section>

        <section class="card section">
            <h2>Chọn gói để gia hạn</h2>
            <form method="POST" action="{{ route('employer.billing.store') }}">
                @csrf
                <div class="plan-list">
                    @foreach ($plans as $plan)
                        <label class="plan-card">
                            <input type="radio" name="service_plan_id" value="{{ $plan->id }}" @checked(old('service_plan_id', $company->service_plan_id) == $plan->id) required>
                            <strong>{{ $plan->name }}</strong>
                            <span>{{ $plan->formattedPrice() }} / {{ $plan->duration_days }} ngày</span>
                            <small>{{ $plan->description }}</small>
                        </label>
                    @endforeach
                </div>
                @error('service_plan_id') <p class="form-error">{{ $message }}</p> @enderror
                <button class="btn btn-primary" type="submit" style="margin-top:14px"><i data-lucide="wallet"></i> Tạo yêu cầu thanh toán</button>
            </form>
        </section>
    </div>

    <section class="card section">
        <h2>Tin tuyển dụng theo hạn gói</h2>
        @forelse ($jobs as $job)
            <div class="panel-row">
                <div>
                    <strong>{{ $job->title }}</strong>
                    <small>{{ $job->status->label() }} · đăng {{ $job->created_at->format('d/m/Y') }}</small>
                </div>
                <span class="status-tag" data-status="{{ $job->listing_state === 'active' ? 'open' : ($job->listing_state === 'expiring' ? 'interview' : 'hidden') }}">
                    {{ match ($job->listing_state) { 'active' => 'Đang hoạt động', 'expiring' => 'Sắp hết hạn', 'expired' => 'Đã hết hạn', default => $job->status->label() } }}
                </span>
            </div>
        @empty
            <p class="muted">Chưa có tin nào.</p>
        @endforelse
    </section>

    <section class="card section">
        <h2>Lịch sử giao dịch</h2>
        @if ($payments->isEmpty())
            <p class="muted">Chưa có giao dịch.</p>
        @else
            <div class="panel-table-wrap">
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Gói</th>
                            <th>Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td><strong>{{ $payment->reference }}</strong></td>
                                <td>{{ $payment->servicePlan->name }}</td>
                                <td>{{ $payment->formattedAmount() }}</td>
                                <td><span class="status-tag" data-status="{{ $payment->status === \App\Enums\PaymentStatus::Paid ? 'open' : ($payment->status === \App\Enums\PaymentStatus::Pending ? 'interview' : 'hidden') }}">{{ $payment->status->label() }}</span></td>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="t-right"><a class="btn btn-ghost btn-sm" href="{{ route('employer.billing.show', $payment) }}">Chi tiết</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $payments->links('pagination.simple') }}
        @endif
    </section>
@endsection
