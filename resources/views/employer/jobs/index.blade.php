@extends('layout.panel')
@section('title', 'Tin tuyển dụng')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Tin tuyển dụng</h1>
            <p class="page-sub">Tin đang mở hiện với sinh viên và được AI so khớp với hồ sơ của họ.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('employer.jobs.create') }}"><i data-lucide="plus"></i> Đăng tin mới</a>
    </header>

    <nav class="chip-row" aria-label="Lọc theo trạng thái">
        <a @class(['filter-chip', 'is-active' => ! $status]) href="{{ route('employer.jobs.index') }}">Tất cả</a>
        @foreach (\App\Enums\JobStatus::cases() as $case)
            <a @class(['filter-chip', 'is-active' => $status === $case->value]) href="{{ route('employer.jobs.index', ['status' => $case->value]) }}">{{ $case->label() }}</a>
        @endforeach
    </nav>

    @if ($jobs->isEmpty())
        <div class="card empty-deck" style="margin:24px auto">
            <div class="emoji">📝</div>
            <h2>Chưa có tin nào</h2>
            <p>Đăng tin đầu tiên để sinh viên phù hợp tìm thấy bạn.</p>
            <a class="btn btn-primary" href="{{ route('employer.jobs.create') }}">Đăng tin mới</a>
        </div>
    @else
        <div class="card panel-table-wrap">
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Vị trí</th>
                        <th>Trạng thái</th>
                        <th>Đơn</th>
                        <th>Ngày đăng</th>
                        <th class="t-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr>
                            <td>
                                <strong>{{ $job->title }}</strong>
                                <small>{{ $job->type }} · {{ $job->is_remote ? 'Làm từ xa' : $job->location }}</small>
                            </td>
                            <td><span class="status-tag" data-status="{{ $job->status->value }}">{{ $job->status->label() }}</span></td>
                            <td>
                                {{ $job->applications_count }}
                                @if ($job->pending_count)
                                    <span class="nav-badge">{{ $job->pending_count }} mới</span>
                                @endif
                            </td>
                            <td>{{ $job->created_at->format('d/m/Y') }}</td>
                            <td class="t-right">
                                <div class="panel-actions">
                                    @if ($job->status !== \App\Enums\JobStatus::Hidden)
                                        <a class="btn btn-ghost btn-sm" href="{{ route('employer.jobs.edit', $job) }}"><i data-lucide="pencil"></i> Sửa</a>
                                        <form method="POST" action="{{ route('employer.jobs.status', $job) }}"
                                            data-confirm="{{ $job->status === \App\Enums\JobStatus::Open ? 'Đóng tin này? Sinh viên sẽ không thấy và không nộp đơn được nữa.' : '' }}">
                                            @csrf
                                            @method('PATCH')
                                            @if ($job->status === \App\Enums\JobStatus::Open)
                                                <input type="hidden" name="status" value="closed">
                                                <button class="btn btn-ghost btn-sm" type="submit"><i data-lucide="lock"></i> Đóng</button>
                                            @else
                                                <input type="hidden" name="status" value="open">
                                                <button class="btn btn-ghost btn-sm" type="submit"><i data-lucide="lock-open"></i> Mở lại</button>
                                            @endif
                                        </form>
                                    @else
                                        <small class="muted">Quản trị viên đã ẩn tin này</small>
                                    @endif
                                    @if ($job->applications_count === 0)
                                        <form method="POST" action="{{ route('employer.jobs.destroy', $job) }}" data-confirm="Xoá hẳn tin &quot;{{ $job->title }}&quot;?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost btn-sm is-danger" type="submit"><i data-lucide="trash-2"></i> Xoá</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $jobs->links('pagination.simple') }}
    @endif
@endsection
