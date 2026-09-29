@extends('layout.panel')
@section('title', 'Công ty')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Công ty</h1>
            <p class="page-sub">Xác minh công ty để sinh viên thấy dấu đã kiểm duyệt.</p>
        </div>
    </header>

    <nav class="chip-row" aria-label="Lọc xác minh">
        <a @class(['filter-chip', 'is-active' => $verified === null || $verified === '']) href="{{ route('admin.companies.index', array_filter(['q' => $q])) }}">Tất cả</a>
        <a @class(['filter-chip', 'is-active' => $verified === '0']) href="{{ route('admin.companies.index', array_filter(['verified' => '0', 'q' => $q])) }}">Chờ xác minh</a>
        <a @class(['filter-chip', 'is-active' => $verified === '1']) href="{{ route('admin.companies.index', array_filter(['verified' => '1', 'q' => $q])) }}">Đã xác minh</a>
    </nav>

    <form class="inbox-filter" method="GET" action="{{ route('admin.companies.index') }}">
        @if ($verified !== null && $verified !== '')
            <input type="hidden" name="verified" value="{{ $verified }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" placeholder="Tìm tên công ty" maxlength="100">
    </form>

    @if ($companies->isEmpty())
        <div class="card empty-deck" style="margin:24px auto">
            <div class="emoji">🏢</div>
            <h2>Không có công ty</h2>
            <p>Thử bỏ bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>
    @else
        <div class="card panel-table-wrap">
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Công ty</th>
                        <th>Xác minh</th>
                        <th>Tin</th>
                        <th>Nhân sự HR</th>
                        <th class="t-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($companies as $company)
                        <tr>
                            <td>
                                <a href="{{ route('admin.companies.show', $company) }}"><strong>{{ $company->name }}</strong></a>
                                <small>{{ $company->location ?: 'Chưa có địa điểm' }}</small>
                            </td>
                            <td><span class="status-tag" data-status="{{ $company->verified ? 'open' : 'pending' }}">{{ $company->verified ? 'Đã xác minh' : 'Chờ duyệt' }}</span></td>
                            <td>{{ $company->job_posts_count }}</td>
                            <td>{{ $company->employers_count }}</td>
                            <td class="t-right">
                                <form method="POST" action="{{ route('admin.companies.verified', $company) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="verified" value="{{ $company->verified ? '0' : '1' }}">
                                    <button class="btn btn-ghost btn-sm" type="submit">{{ $company->verified ? 'Bỏ xác minh' : 'Xác minh' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $companies->links('pagination.simple') }}
    @endif
@endsection
