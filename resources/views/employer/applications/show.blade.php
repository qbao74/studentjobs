@extends('layout.panel')
@section('title', $application->student->user->name)

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('employer.applications.index') }}">Đơn ứng tuyển</a></p>
            <h1 class="page-title">{{ $application->student->user->name }}</h1>
            <p class="page-sub">{{ $application->jobPost->title }} · nộp {{ $application->created_at->diffForHumans() }}</p>
        </div>
        <form method="POST" action="{{ route('employer.applications.status', $application) }}">
            @csrf
            @method('PATCH')
            <label class="inbox-filter">
                <span class="muted">Trạng thái</span>
                <select name="status" data-autosubmit @disabled($application->status->isFinal())>
                    @foreach (\App\Enums\ApplicationStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($application->status === $case)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </header>

    <div class="panel-grid">
        <section class="card section">
            <h2>Hồ sơ</h2>
            <div class="info-grid">
                <div class="info-item"><div><small>Trường</small>{{ $application->student->school ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Ngành</small>{{ $application->student->major ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Năm học</small>{{ $application->student->year ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Khu vực</small>{{ $application->student->location ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Email</small>{{ $application->student->user->email }}</div></div>
                <div class="info-item"><div><small>Điện thoại</small>{{ $application->student->phone ?: 'Chưa cập nhật' }}</div></div>
            </div>
            <p>{{ $application->student->bio ?: 'Ứng viên chưa viết giới thiệu.' }}</p>
            <div class="skill-row">
                @forelse ($application->student->skills as $skill)
                    <span class="skill-tag">{{ $skill->name }}</span>
                @empty
                    <span class="muted">Chưa có kỹ năng.</span>
                @endforelse
            </div>
            @if ($application->student->cv)
                <a class="btn btn-soft btn-sm" href="{{ route('cvs.download', $application->student->cv) }}" style="margin-top:14px"><i data-lucide="download"></i> Tải CV</a>
            @else
                <p class="muted" style="margin-top:14px">Ứng viên chưa tải CV.</p>
            @endif
        </section>

        <section class="card section">
            <h2>Tin nhắn</h2>
            @include('employer.partials.thread', ['application' => $application, 'thread' => $thread])
        </section>
    </div>
@endsection
