@extends('auth.layout')

@section('title', 'Đăng nhập')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="card auth-card">
        @csrf
        <h1 class="page-title">Đăng nhập</h1>
        <p class="page-sub">Sinh viên, nhà tuyển dụng hoặc admin.</p>

        <label class="auth-field">
            Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" />
        </label>
        @error('email') <p class="auth-error">{{ $message }}</p> @enderror

        <label class="auth-field">
            Mật khẩu
            <input type="password" name="password" required autocomplete="current-password" />
        </label>
        @error('password') <p class="auth-error">{{ $message }}</p> @enderror

        <label style="display:flex;gap:8px;align-items:center;margin-top:14px;">
            <input type="checkbox" name="remember" value="1" />
            Ghi nhớ đăng nhập
        </label>

        <button class="btn btn-primary" type="submit" style="margin-top:18px;width:100%;">Vào Jobly</button>
        <p class="auth-foot">
            Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký sinh viên</a>
            · <a href="{{ route('register.employer') }}">Nhà tuyển dụng</a>
        </p>
        <p class="auth-foot"><a href="{{ route('home') }}">← Về trang chủ</a></p>
    </form>
@endsection
