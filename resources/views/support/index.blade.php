@extends(auth()->user()->hasRole(\App\Enums\Role::Student) ? 'layout.app' : 'layout.panel')
@section('title', 'Hỗ trợ')
@section('page', 'support')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Hỗ trợ khách hàng</h1>
            <p class="page-sub">Gửi câu hỏi về sản phẩm, thanh toán hoặc tài khoản.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('support.create') }}"><i data-lucide="plus"></i> Tạo yêu cầu</a>
    </header>

    @forelse ($tickets as $ticket)
        <a class="card section panel-row" href="{{ route('support.show', $ticket) }}">
            <div>
                <strong>{{ $ticket->subject }}</strong>
                <small>{{ $ticket->category->label() }} · {{ $ticket->updated_at->diffForHumans() }}</small>
            </div>
            <span class="status-tag" data-status="{{ $ticket->status === \App\Enums\SupportTicketStatus::Closed ? 'hidden' : 'open' }}">{{ $ticket->status->label() }}</span>
        </a>
    @empty
        <div class="card empty-deck">
            <div class="emoji">💬</div>
            <h2>Chưa có yêu cầu nào</h2>
            <p>Tạo yêu cầu khi bạn cần hỗ trợ. Quản trị viên sẽ phản hồi trong trang này.</p>
        </div>
    @endforelse

    {{ $tickets->links('pagination.simple') }}
@endsection
