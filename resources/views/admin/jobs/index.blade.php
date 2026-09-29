@extends('layout.panel')
@section('title', 'Tin tuyển dụng')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Tin tuyển dụng</h1>
            <p class="page-sub">Ẩn tin vi phạm. Nhà tuyển dụng không tự mở lại tin đã bị ẩn.</p>
        </div>
    </header>

    <nav class="chip-row" aria-label="Lọc theo trạng thái">
        <a @class(['filter-chip', 'is-active' => ! $status]) href="{{ route('admin.jobs.index', array_filter(['q' => $q])) }}">Tất cả</a>
        @foreach (\App\Enums\JobStatus::cases() as $case)
            <a @class(['filter-chip', 'is-active' => $status === $case->value]) href="{{ route('admin.jobs.index', array_filter(['status' => $case->value, 'q' => $q])) }}">{{ $case->label() }}</a>
        @endforeach
    </nav>

    <form class="inbox-filter" method="GET" action="{{ route('admin.jobs.index') }}">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" placeholder="Tìm vị trí hoặc công ty" maxlength="100">
    </form>

    @if ($jobs->isEmpty())
        <div class="card empty-deck" style="margin:24px auto">
            <div class="emoji">📝</div>
            <h2>Không có tin</h2>
            <p>Thử bỏ bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>
    @else
        <div class="card panel-table-wrap">
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Vị trí</th>
                        <th>Công ty</th>
                        <th>Trạng thái</th>
                        <th>Đơn</th>
                        <th class="t-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr>
                            <td>
                                <a href="{{ route('admin.jobs.show', $job) }}"><strong>{{ $job->title }}</strong></a>
                                <small>{{ $job->type }} · {{ $job->is_remote ? 'Làm từ xa' : $job->location }}</small>
                            </td>
                            <td><a href="{{ route('admin.companies.show', $job->company) }}">{{ $job->company->name }}</a></td>
                            <td><span class="status-tag" data-status="{{ $job->status->value }}">{{ $job->status->label() }}</span></td>
                            <td><a href="{{ route('admin.applications.index', ['q' => $job->title]) }}">{{ $job->applications_count }}</a></td>
                            <td class="t-right">
                                @include('admin.jobs.status', ['job' => $job])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $jobs->links('pagination.simple') }}
    @endif
@endsection
