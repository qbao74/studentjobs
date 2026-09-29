@extends('layout.panel')
@section('title', $user->name)

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('admin.users.index') }}">Tài khoản</a></p>
            <h1 class="page-title">{{ $user->name }}</h1>
            <p class="page-sub">{{ $user->role->label() }} · {{ $user->email }}</p>
        </div>
        @if (! $user->is(auth()->user()))
            <form method="POST" action="{{ route('admin.users.active', $user) }}" data-confirm="{{ $user->is_active ? 'Khóa tài khoản này? Họ sẽ bị đăng xuất.' : '' }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                <button class="btn {{ $user->is_active ? 'btn-ghost is-danger' : 'btn-primary' }}" type="submit">
                    <i data-lucide="{{ $user->is_active ? 'lock' : 'lock-open' }}"></i> {{ $user->is_active ? 'Khóa tài khoản' : 'Mở khóa' }}
                </button>
            </form>
        @endif
    </header>

    <div class="panel-grid">
        <section class="card section">
            <h2>Tài khoản</h2>
            <div class="info-grid">
                <div class="info-item"><div><small>Vai trò</small>{{ $user->role->label() }}</div></div>
                <div class="info-item"><div><small>Trạng thái</small>{{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}</div></div>
                <div class="info-item"><div><small>Ngày tạo</small>{{ $user->created_at->format('d/m/Y H:i') }}</div></div>
            </div>
        </section>

        @if ($user->student)
            <section class="card section">
                <h2>Hồ sơ sinh viên</h2>
                <div class="info-grid">
                    <div class="info-item"><div><small>Trường</small>{{ $user->student->school ?: 'Chưa cập nhật' }}</div></div>
                    <div class="info-item"><div><small>Ngành</small>{{ $user->student->major ?: 'Chưa cập nhật' }}</div></div>
                    <div class="info-item"><div><small>Năm học</small>{{ $user->student->year ?: 'Chưa cập nhật' }}</div></div>
                    <div class="info-item"><div><small>Khu vực</small>{{ $user->student->location ?: 'Chưa cập nhật' }}</div></div>
                </div>
                <p>{{ $user->student->bio ?: 'Chưa viết giới thiệu.' }}</p>
                <div class="skill-row">
                    @forelse ($user->student->skills as $skill)
                        <span class="skill-tag">{{ $skill->name }}</span>
                    @empty
                        <span class="muted">Chưa có kỹ năng.</span>
                    @endforelse
                </div>
                <p class="muted" style="margin-top:12px">{{ $user->student->cv ? 'Đã có CV: '.$user->student->cv->original_name : 'Chưa tải CV.' }}</p>
            </section>
        @endif

        @if ($user->employer)
            <section class="card section">
                <h2>Nhà tuyển dụng</h2>
                <p>Chức danh: {{ $user->employer->position ?: 'Chưa cập nhật' }}</p>
                <a class="btn btn-soft btn-sm" href="{{ route('admin.companies.show', $user->employer->company) }}">{{ $user->employer->company->name }}</a>
            </section>
        @endif
    </div>
@endsection
