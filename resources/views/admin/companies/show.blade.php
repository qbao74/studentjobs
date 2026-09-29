@extends('layout.panel')
@section('title', $company->name)

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('admin.companies.index') }}">Công ty</a></p>
            <h1 class="page-title">{{ $company->name }}</h1>
            <p class="page-sub">{{ $company->tagline ?: 'Chưa có slogan' }}</p>
        </div>
        <form method="POST" action="{{ route('admin.companies.verified', $company) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="verified" value="{{ $company->verified ? '0' : '1' }}">
            <button class="btn {{ $company->verified ? 'btn-ghost' : 'btn-primary' }}" type="submit">{{ $company->verified ? 'Bỏ xác minh' : 'Xác minh công ty' }}</button>
        </form>
    </header>

    <div class="panel-grid">
        <section class="card section">
            <h2>Thông tin</h2>
            <div class="info-grid">
                <div class="info-item"><div><small>Địa điểm</small>{{ $company->location ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Quy mô</small>{{ $company->size ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Xác minh</small>{{ $company->verified ? 'Đã xác minh' : 'Chờ duyệt' }}</div></div>
                <div class="info-item"><div><small>Người theo dõi</small>{{ $company->followers()->count() }}</div></div>
            </div>
            <p>{{ $company->about ?: 'Chưa có giới thiệu.' }}</p>
        </section>

        <section class="card section">
            <h2>Tài khoản HR</h2>
            @forelse ($company->employers as $employer)
                <a class="panel-row" href="{{ route('admin.users.show', $employer->user) }}">
                    <span class="avatar panel-avatar">{{ mb_strtoupper(mb_substr($employer->user->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $employer->user->name }}</strong>
                        <small>{{ $employer->position ?: 'HR' }} · {{ $employer->user->email }}</small>
                    </div>
                </a>
            @empty
                <p class="muted">Chưa có tài khoản nhà tuyển dụng.</p>
            @endforelse
        </section>
    </div>

    <section class="card section">
        <h2>Tin tuyển dụng</h2>
        @forelse ($company->jobPosts as $job)
            <a class="panel-row" href="{{ route('admin.jobs.show', $job) }}">
                <div>
                    <strong>{{ $job->title }}</strong>
                    <small>{{ $job->applications_count }} đơn · {{ $job->created_at->format('d/m/Y') }}</small>
                </div>
                <span class="status-tag" data-status="{{ $job->status->value }}">{{ $job->status->label() }}</span>
            </a>
        @empty
            <p class="muted">Công ty chưa đăng tin.</p>
        @endforelse
    </section>
@endsection
