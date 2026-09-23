@extends('layout.panel')
@section('title', 'Tổng quan')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Chào {{ \Illuminate\Support\Str::afterLast(trim(auth()->user()->name), ' ') }}!</h1>
            <p class="page-sub">Tình hình tuyển dụng của {{ $company->name }}.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('employer.jobs.create') }}"><i data-lucide="plus"></i> Đăng tin mới</a>
    </header>

    <div class="stats-grid panel-stats">
        <div class="stat-card"><span class="stat-icon is-violet"><i data-lucide="briefcase"></i></span><strong>{{ $stats['openJobs'] }}</strong><span>Tin đang tuyển</span></div>
        <div class="stat-card"><span class="stat-icon is-blue"><i data-lucide="users"></i></span><strong>{{ $stats['applications'] }}</strong><span>Tổng đơn nhận được</span></div>
        <div class="stat-card"><span class="stat-icon is-pink"><i data-lucide="inbox"></i></span><strong>{{ $stats['pending'] }}</strong><span>Đơn chưa xem</span></div>
        <div class="stat-card"><span class="stat-icon is-mint"><i data-lucide="calendar-check"></i></span><strong>{{ $stats['interview'] }}</strong><span>Đang phỏng vấn</span></div>
    </div>

    <div class="panel-grid">
        <section class="card section">
            <h2>Đơn mới nhất</h2>
            @forelse ($recent as $application)
                <div class="panel-row">
                    <span class="avatar panel-avatar">{{ mb_strtoupper(mb_substr($application->student->user->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $application->student->user->name }}</strong>
                        <small>{{ $application->jobPost->title }} · {{ $application->created_at->diffForHumans() }}</small>
                    </div>
                    <span class="status-tag" data-status="{{ $application->status->value }}">{{ $application->status->label() }}</span>
                </div>
            @empty
                <p class="muted">Chưa có ai ứng tuyển. Đăng tin để bắt đầu nhận hồ sơ.</p>
            @endforelse
        </section>

        <section class="card section">
            <h2>Tin gần đây</h2>
            @forelse ($jobs as $job)
                <div class="panel-row">
                    <div>
                        <strong>{{ $job->title }}</strong>
                        <small>{{ $job->applications_count }} đơn · {{ $job->pending_count }} chưa xem</small>
                    </div>
                    <span class="status-tag" data-status="{{ $job->status->value }}">{{ $job->status->label() }}</span>
                </div>
            @empty
                <p class="muted">Bạn chưa có tin nào.</p>
            @endforelse
            <a class="btn btn-soft btn-sm" href="{{ route('employer.jobs.index') }}" style="margin-top:12px">Quản lý tin</a>
        </section>
    </div>
@endsection
