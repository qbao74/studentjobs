@extends('layout.panel')
@section('title', 'Đơn ứng tuyển')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Đơn ứng tuyển</h1>
            <p class="page-sub">Xem hồ sơ và hội thoại. Quản trị viên không gửi tin và không đổi trạng thái đơn.</p>
        </div>
    </header>

    <nav class="chip-row" aria-label="Lọc theo trạng thái">
        <a @class(['filter-chip', 'is-active' => ! $status]) href="{{ route('admin.applications.index', array_filter(['q' => $q])) }}">Tất cả</a>
        @foreach (\App\Enums\ApplicationStatus::cases() as $case)
            <a @class(['filter-chip', 'is-active' => $status === $case->value]) href="{{ route('admin.applications.index', array_filter(['status' => $case->value, 'q' => $q])) }}">{{ $case->label() }}</a>
        @endforeach
    </nav>

    <form class="inbox-filter" method="GET" action="{{ route('admin.applications.index') }}">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" placeholder="Tìm ứng viên hoặc vị trí" maxlength="100">
    </form>

    @if ($applications->isEmpty())
        <div class="card empty-deck" style="margin:24px auto">
            <div class="emoji">📥</div>
            <h2>Không có đơn</h2>
            <p>Thử bỏ bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>
    @else
        <div class="card panel-table-wrap">
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Ứng viên</th>
                        <th>Vị trí</th>
                        <th>Công ty</th>
                        <th>Trạng thái</th>
                        <th>Ngày nộp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($applications as $application)
                        <tr>
                            <td>
                                <a class="inbox-person" href="{{ route('admin.applications.show', $application) }}">
                                    <span class="avatar panel-avatar">{{ mb_strtoupper(mb_substr($application->student->user->name, 0, 1)) }}</span>
                                    <span><strong>{{ $application->student->user->name }}</strong><small>{{ $application->student->user->email }}</small></span>
                                </a>
                            </td>
                            <td>{{ $application->jobPost->title }}</td>
                            <td>{{ $application->jobPost->company->name }}</td>
                            <td><span class="status-tag" data-status="{{ $application->status->value }}">{{ $application->status->label() }}</span></td>
                            <td>{{ $application->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $applications->links('pagination.simple') }}
    @endif
@endsection
