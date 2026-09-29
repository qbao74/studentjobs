@extends('layout.panel')
@section('title', 'Tài khoản')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Tài khoản</h1>
            <p class="page-sub">Sinh viên, nhà tuyển dụng và quản trị viên. Khóa tài khoản thì người đó không đăng nhập được.</p>
        </div>
    </header>

    <nav class="chip-row" aria-label="Lọc theo vai trò">
        <a @class(['filter-chip', 'is-active' => ! $role]) href="{{ route('admin.users.index', array_filter(['q' => $q])) }}">Tất cả</a>
        @foreach (\App\Enums\Role::cases() as $case)
            <a @class(['filter-chip', 'is-active' => $role === $case->value]) href="{{ route('admin.users.index', array_filter(['role' => $case->value, 'q' => $q])) }}">{{ $case->label() }}</a>
        @endforeach
    </nav>

    <form class="inbox-filter" method="GET" action="{{ route('admin.users.index') }}">
        @if ($role)
            <input type="hidden" name="role" value="{{ $role }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" placeholder="Tìm tên hoặc email" maxlength="100">
    </form>

    @if ($users->isEmpty())
        <div class="card empty-deck" style="margin:24px auto">
            <div class="emoji">👤</div>
            <h2>Không có tài khoản</h2>
            <p>Thử bỏ bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>
    @else
        <div class="card panel-table-wrap">
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="t-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <a class="inbox-person" href="{{ route('admin.users.show', $user) }}">
                                    <span class="avatar panel-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span>
                                </a>
                            </td>
                            <td>{{ $user->role->label() }}</td>
                            <td><span class="status-tag" data-status="{{ $user->is_active ? 'open' : 'hidden' }}">{{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}</span></td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="t-right">
                                @if ($user->is(auth()->user()))
                                    <small class="muted">Đang đăng nhập</small>
                                @else
                                    <form method="POST" action="{{ route('admin.users.active', $user) }}" data-confirm="{{ $user->is_active ? 'Khóa tài khoản này? Họ sẽ bị đăng xuất.' : '' }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                        <button class="btn btn-ghost btn-sm {{ $user->is_active ? 'is-danger' : '' }}" type="submit">
                                            <i data-lucide="{{ $user->is_active ? 'lock' : 'lock-open' }}"></i> {{ $user->is_active ? 'Khóa' : 'Mở khóa' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links('pagination.simple') }}
    @endif
@endsection
