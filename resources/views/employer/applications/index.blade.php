@extends('layout.panel')
@section('title', 'Đơn ứng tuyển')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Đơn ứng tuyển</h1>
            <p class="page-sub">Hồ sơ sinh viên đã nộp vào tin của công ty. Bấm một dòng để xem hồ sơ và nhắn tin.</p>
        </div>
    </header>

    <nav class="chip-row" aria-label="Lọc theo trạng thái">
        <a @class(['filter-chip', 'is-active' => ! $status]) href="{{ route('employer.applications.index', array_filter(['job' => $jobId])) }}">Tất cả</a>
        @foreach (\App\Enums\ApplicationStatus::cases() as $case)
            <a @class(['filter-chip', 'is-active' => $status === $case->value]) href="{{ route('employer.applications.index', array_filter(['status' => $case->value, 'job' => $jobId])) }}">{{ $case->label() }}</a>
        @endforeach
    </nav>

    @if ($jobs->isNotEmpty())
        <form class="inbox-filter" method="GET" action="{{ route('employer.applications.index') }}">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <label>
                <span class="muted">Tin tuyển dụng</span>
                <select name="job" data-autosubmit>
                    <option value="">Mọi tin</option>
                    @foreach ($jobs as $job)
                        <option value="{{ $job->id }}" @selected($jobId === $job->id)>{{ $job->title }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    @endif

    @if ($applications->isEmpty())
        <div class="card empty-deck" style="margin:24px auto">
            <div class="emoji">📥</div>
            <h2>Chưa có đơn nào</h2>
            <p>Khi sinh viên bấm Nhận trên một tin đang mở, hồ sơ của họ sẽ hiện ở đây.</p>
            <a class="btn btn-primary" href="{{ route('employer.jobs.create') }}">Đăng tin mới</a>
        </div>
    @else
        <div class="card panel-table-wrap">
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Ứng viên</th>
                        <th>Vị trí</th>
                        <th>Trạng thái</th>
                        <th>Tin nhắn</th>
                        <th>Ngày nộp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($applications as $application)
                        <tr>
                            <td>
                                <a class="inbox-person" href="{{ route('employer.applications.show', $application) }}">
                                    <span class="avatar panel-avatar">{{ mb_strtoupper(mb_substr($application->student->user->name, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $application->student->user->name }}</strong>
                                        <small>{{ $application->student->school ?: $application->student->user->email }}</small>
                                    </span>
                                </a>
                            </td>
                            <td>{{ $application->jobPost->title }}</td>
                            <td><span class="status-tag" data-status="{{ $application->status->value }}">{{ $application->status->label() }}</span></td>
                            <td class="muted">{{ $application->latestMessage?->body ? \Illuminate\Support\Str::limit($application->latestMessage->body, 48) : 'Chưa nhắn' }}</td>
                            <td>{{ $application->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $applications->links('pagination.simple') }}
    @endif
@endsection
