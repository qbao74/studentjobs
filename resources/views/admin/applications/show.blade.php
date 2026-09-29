@extends('layout.panel')
@section('title', $application->student->user->name)

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('admin.applications.index') }}">Đơn ứng tuyển</a></p>
            <h1 class="page-title">{{ $application->student->user->name }}</h1>
            <p class="page-sub">
                <a href="{{ route('admin.jobs.show', $application->jobPost) }}">{{ $application->jobPost->title }}</a>
                · {{ $application->jobPost->company->name }}
                · {{ $application->status->label() }}
            </p>
        </div>
        <a class="btn btn-soft" href="{{ route('admin.users.show', $application->student->user) }}">Hồ sơ tài khoản</a>
    </header>

    <div class="panel-grid">
        <section class="card section">
            <h2>Hồ sơ nộp đơn</h2>
            <div class="info-grid">
                <div class="info-item"><div><small>Trường</small>{{ $application->student->school ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Ngành</small>{{ $application->student->major ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Email</small>{{ $application->student->user->email }}</div></div>
                <div class="info-item"><div><small>Điện thoại</small>{{ $application->student->phone ?: 'Chưa cập nhật' }}</div></div>
            </div>
            <div class="skill-row">
                @forelse ($application->student->skills as $skill)
                    <span class="skill-tag">{{ $skill->name }}</span>
                @empty
                    <span class="muted">Chưa có kỹ năng.</span>
                @endforelse
            </div>
            @if ($application->student->cv)
                <a class="btn btn-soft btn-sm" href="{{ route('cvs.download', $application->student->cv) }}" style="margin-top:14px"><i data-lucide="download"></i> Tải CV</a>
            @endif
        </section>

        <section class="card section">
            <h2>Hội thoại</h2>
            <div class="hr-thread">
                <div class="hr-thread-log">
                    @forelse ($thread as $message)
                        <div class="msg-group msg-group--in">
                            <div class="bubble bubble--in"><strong>{{ $message->sender?->name ?? 'Người dùng' }}.</strong> {{ $message->body }}</div>
                            <span class="msg-time">{{ $message->created_at->format('d/m H:i') }}</span>
                        </div>
                    @empty
                        <p class="muted hr-thread-empty">Chưa có tin nhắn trong đơn này.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
