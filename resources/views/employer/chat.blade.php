@extends('layout.panel')
@section('title', 'Tin nhắn')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Tin nhắn</h1>
            <p class="page-sub">Mỗi đơn ứng tuyển là một hội thoại với sinh viên.</p>
        </div>
    </header>

    @if ($conversations->isEmpty())
        <div class="card empty-deck" style="margin:24px auto">
            <div class="emoji">💬</div>
            <h2>Chưa có hội thoại</h2>
            <p>Hội thoại chỉ xuất hiện khi có sinh viên ứng tuyển vào tin của công ty.</p>
            <a class="btn btn-primary" href="{{ route('employer.jobs.index') }}">Xem tin tuyển dụng</a>
        </div>
    @else
        <div class="card hr-inbox">
            <aside class="hr-inbox-list">
                @foreach ($conversations as $conversation)
                    <a @class(['hr-inbox-item', 'is-active' => $active?->id === $conversation->id]) href="{{ route('employer.chat', ['c' => $conversation->id]) }}">
                        <span class="avatar panel-avatar">{{ mb_strtoupper(mb_substr($conversation->student->user->name, 0, 1)) }}</span>
                        <span>
                            <strong>{{ $conversation->student->user->name }}</strong>
                            <small>{{ $conversation->latestMessage?->body ? \Illuminate\Support\Str::limit($conversation->latestMessage->body, 42) : 'Chưa có tin nhắn' }}</small>
                            <small>{{ $conversation->jobPost->title }}</small>
                        </span>
                        @if ($conversation->unread_count)
                            <span class="unread">{{ $conversation->unread_count }}</span>
                        @endif
                    </a>
                @endforeach
            </aside>
            <section class="hr-inbox-pane">
                @if ($active)
                    <div class="hr-inbox-head">
                        <div>
                            <strong>{{ $active->student->user->name }}</strong>
                            <small>{{ $active->jobPost->title }}</small>
                        </div>
                        <a class="btn btn-soft btn-sm" href="{{ route('employer.applications.show', $active) }}">Xem hồ sơ</a>
                    </div>
                    @include('employer.partials.thread', ['application' => $active, 'thread' => $thread])
                @else
                    <div class="hr-thread-empty">
                        <p>Chọn một hội thoại bên trái để đọc và trả lời.</p>
                    </div>
                @endif
            </section>
        </div>
    @endif
@endsection
