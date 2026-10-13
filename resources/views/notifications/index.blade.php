@extends(auth()->user()->hasRole(\App\Enums\Role::Student) ? 'layout.app' : 'layout.panel')
@section('title', 'Thông báo')
@section('page', 'notifications')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Thông báo</h1>
            <p class="page-sub">{{ $unread }} thông báo chưa đọc.</p>
        </div>
        @if ($unread > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-soft btn-sm" type="submit">Đánh dấu tất cả đã đọc</button>
            </form>
        @endif
    </header>

    @forelse ($notifications as $notification)
        <a class="card section notif-item {{ $notification->isUnread() ? 'is-unread' : '' }}" href="{{ route('notifications.read', $notification) }}">
            <strong>{{ $notification->title }}</strong>
            <p>{{ $notification->body }}</p>
            <small class="muted">{{ $notification->created_at->diffForHumans() }} · {{ $notification->isUnread() ? 'Chưa đọc' : 'Đã đọc' }}</small>
        </a>
    @empty
        <div class="card empty-deck">
            <div class="emoji">🔔</div>
            <h2>Chưa có thông báo</h2>
            <p>Khi đơn ứng tuyển, thanh toán hoặc hỗ trợ có cập nhật, thông báo sẽ hiện ở đây.</p>
        </div>
    @endforelse

    {{ $notifications->links('pagination.simple') }}
@endsection
