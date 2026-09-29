@extends('layout.panel')
@section('title', 'Tổng quan')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Quản trị Jobly</h1>
            <p class="page-sub">Tài khoản, công ty, tin tuyển dụng và đơn trên toàn hệ thống.</p>
        </div>
    </header>

    <div class="stats-grid panel-stats">
        <a class="stat-card" href="{{ route('admin.users.index') }}"><span class="stat-icon is-violet"><i data-lucide="users"></i></span><strong>{{ $stats['users'] }}</strong><span>Tài khoản</span></a>
        <a class="stat-card" href="{{ route('admin.companies.index', ['verified' => '0']) }}"><span class="stat-icon is-blue"><i data-lucide="building-2"></i></span><strong>{{ $stats['unverified'] }}</strong><span>Công ty chờ xác minh</span></a>
        <a class="stat-card" href="{{ route('admin.jobs.index', ['status' => 'open']) }}"><span class="stat-icon is-mint"><i data-lucide="briefcase"></i></span><strong>{{ $stats['openJobs'] }}</strong><span>Tin đang tuyển</span></a>
        <a class="stat-card" href="{{ route('admin.applications.index', ['status' => 'pending']) }}"><span class="stat-icon is-pink"><i data-lucide="inbox"></i></span><strong>{{ $stats['pending'] }}</strong><span>Đơn chưa xem</span></a>
    </div>

    <div class="panel-grid">
        <section class="card section">
            <h2>Tài khoản mới</h2>
            @foreach ($recentUsers as $user)
                <a class="panel-row" href="{{ route('admin.users.show', $user) }}">
                    <span class="avatar panel-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $user->name }}</strong>
                        <small>{{ $user->role->label() }} · {{ $user->email }}</small>
                    </div>
                    <span class="status-tag" data-status="{{ $user->is_active ? 'open' : 'hidden' }}">{{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}</span>
                </a>
            @endforeach
        </section>

        <section class="card section">
            <h2>Tin mới đăng</h2>
            @forelse ($recentJobs as $job)
                <a class="panel-row" href="{{ route('admin.jobs.show', $job) }}">
                    <div>
                        <strong>{{ $job->title }}</strong>
                        <small>{{ $job->company->name }} · {{ $job->applications_count }} đơn</small>
                    </div>
                    <span class="status-tag" data-status="{{ $job->status->value }}">{{ $job->status->label() }}</span>
                </a>
            @empty
                <p class="muted">Chưa có tin nào.</p>
            @endforelse
            <p class="muted" style="margin-top:12px">{{ $roles['student'] }} sinh viên · {{ $roles['employer'] }} nhà tuyển dụng · {{ $roles['admin'] }} quản trị</p>
        </section>
    </div>
@endsection
