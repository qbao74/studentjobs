@extends('layout.panel')
@section('title', $job->title)

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('admin.jobs.index') }}">Tin tuyển dụng</a></p>
            <h1 class="page-title">{{ $job->title }}</h1>
            <p class="page-sub"><a href="{{ route('admin.companies.show', $job->company) }}">{{ $job->company->name }}</a> · {{ $job->applications_count }} đơn</p>
        </div>
        @include('admin.jobs.status', ['job' => $job])
    </header>

    <div class="panel-grid">
        <section class="card section">
            <h2>Nội dung</h2>
            <div class="info-grid">
                <div class="info-item"><div><small>Trạng thái</small>{{ $job->status->label() }}</div></div>
                <div class="info-item"><div><small>Hình thức</small>{{ $job->type }}</div></div>
                <div class="info-item"><div><small>Lương</small>{{ $job->salary ?: 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div><small>Địa điểm</small>{{ $job->is_remote ? 'Làm từ xa' : ($job->location ?: 'Chưa cập nhật') }}</div></div>
            </div>
            <p>{{ $job->description }}</p>
            <div class="skill-row">
                @forelse ($job->skills as $skill)
                    <span class="skill-tag">{{ $skill->name }}{{ $skill->pivot->is_required ? '' : ' · điểm cộng' }}</span>
                @empty
                    <span class="muted">Chưa gắn kỹ năng.</span>
                @endforelse
            </div>
        </section>
        <section class="card section">
            <h2>Đơn ứng tuyển</h2>
            <p class="muted">{{ $job->applications_count }} hồ sơ đã nộp vào tin này.</p>
            <a class="btn btn-soft btn-sm" href="{{ route('admin.applications.index', ['q' => $job->title]) }}">Xem đơn</a>
        </section>
    </div>
@endsection
