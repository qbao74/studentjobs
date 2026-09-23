@extends('auth.layout')

@section('title', 'Đăng ký')

@section('content')
    @php($isEmployer = $type === 'employer')

    <form method="POST" action="{{ $isEmployer ? route('register.employer.store') : route('register.store') }}" class="card auth-card">
        @csrf
        <h1 class="page-title">Tạo tài khoản</h1>
        <p class="page-sub">
            {{ $isEmployer ? 'Đăng tin tuyển dụng và xem ứng viên phù hợp.' : 'Dùng để ứng tuyển và giữ hồ sơ.' }}
        </p>

        <nav class="auth-tabs" aria-label="Loại tài khoản">
            <a href="{{ route('register') }}" @class(['active' => ! $isEmployer])>Sinh viên</a>
            <a href="{{ route('register.employer') }}" @class(['active' => $isEmployer])>Nhà tuyển dụng</a>
        </nav>

        <label class="auth-field">
            Họ tên
            <input type="text" name="name" value="{{ old('name') }}" required autofocus maxlength="100" autocomplete="name" />
        </label>
        @error('name') <p class="auth-error">{{ $message }}</p> @enderror

        <label class="auth-field">
            Email
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email" />
        </label>
        @error('email') <p class="auth-error">{{ $message }}</p> @enderror

        @if ($isEmployer)
            <label class="auth-field">
                Tên công ty
                <input type="text" name="company_name" value="{{ old('company_name') }}" required maxlength="150" />
            </label>
            @error('company_name') <p class="auth-error">{{ $message }}</p> @enderror

            <label class="auth-field">
                Địa chỉ công ty
                <input type="text" name="company_location" value="{{ old('company_location') }}" maxlength="150" placeholder="VD: Quận 1, TP.HCM" />
            </label>
            @error('company_location') <p class="auth-error">{{ $message }}</p> @enderror

            <label class="auth-field">
                Chức vụ của bạn
                <input type="text" name="position" value="{{ old('position') }}" maxlength="100" placeholder="VD: HR" />
            </label>
            @error('position') <p class="auth-error">{{ $message }}</p> @enderror
        @endif

        <label class="auth-field">
            Mật khẩu
            <input type="password" name="password" required minlength="8" autocomplete="new-password" />
        </label>
        @error('password') <p class="auth-error">{{ $message }}</p> @enderror

        <label class="auth-field">
            Nhập lại mật khẩu
            <input type="password" name="password_confirmation" required autocomplete="new-password" />
        </label>

        <button class="btn btn-primary" type="submit" style="margin-top:18px;width:100%;">Đăng ký</button>
        <p class="auth-foot">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
    </form>
@endsection
