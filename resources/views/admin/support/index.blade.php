@extends('layout.panel')
@section('title', 'Hỗ trợ')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Hỗ trợ khách hàng</h1>
            <p class="page-sub">Phản hồi yêu cầu của sinh viên và nhà tuyển dụng.</p>
        </div>
    </header>

    <nav class="chip-row">
        <a @class(['filter-chip', 'is-active' => ! $status]) href="{{ route('admin.support.index') }}">Tất cả</a>
        @foreach (\App\Enums\SupportTicketStatus::cases() as $case)
            <a @class(['filter-chip', 'is-active' => $status === $case->value]) href="{{ route('admin.support.index', ['status' => $case->value]) }}">{{ $case->label() }}</a>
        @endforeach
    </nav>

    @forelse ($tickets as $ticket)
        <a class="panel-row card section" href="{{ route('admin.support.show', $ticket) }}">
            <div>
                <strong>{{ $ticket->subject }}</strong>
                <small>{{ $ticket->user->name }} · {{ $ticket->user->role->label() }} · {{ $ticket->category->label() }}</small>
            </div>
            <span class="status-tag" data-status="{{ $ticket->isClosed() ? 'hidden' : 'open' }}">{{ $ticket->status->label() }}</span>
        </a>
    @empty
        <div class="card empty-deck">
            <div class="emoji">💬</div>
            <h2>Không có yêu cầu</h2>
            <p>Chưa có ai gửi hỗ trợ, hoặc bộ lọc không khớp.</p>
        </div>
    @endforelse

    {{ $tickets->links('pagination.simple') }}
@endsection
