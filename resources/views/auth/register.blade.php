<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đăng ký — Jobly</title>
  <link rel="stylesheet" href="{{ asset('jobly/css/style.css') }}" />
</head>
<body>
  <main style="min-height:100vh;display:grid;place-items:center;padding:24px;">
    <form method="POST" action="{{ route('register') }}" class="card" style="width:min(420px,100%);padding:28px;">
      @csrf
      <h1 class="page-title">Tạo tài khoản</h1>
      <p class="page-sub">Dùng để ứng tuyển và giữ hồ sơ.</p>

      <label style="display:block;margin-top:18px;">
        Tên
        <input type="text" name="name" value="{{ old('name') }}" required autofocus
               style="width:100%;margin-top:6px;padding:12px;border-radius:12px;border:1px solid var(--border);" />
      </label>
      @error('name')
        <p style="color:var(--danger);margin-top:8px;">{{ $message }}</p>
      @enderror

      <label style="display:block;margin-top:14px;">
        Email
        <input type="email" name="email" value="{{ old('email') }}" required
               style="width:100%;margin-top:6px;padding:12px;border-radius:12px;border:1px solid var(--border);" />
      </label>
      @error('email')
        <p style="color:var(--danger);margin-top:8px;">{{ $message }}</p>
      @enderror

      <label style="display:block;margin-top:14px;">
        Mật khẩu
        <input type="password" name="password" required
               style="width:100%;margin-top:6px;padding:12px;border-radius:12px;border:1px solid var(--border);" />
      </label>
      @error('password')
        <p style="color:var(--danger);margin-top:8px;">{{ $message }}</p>
      @enderror

      <label style="display:block;margin-top:14px;">
        Nhập lại mật khẩu
        <input type="password" name="password_confirmation" required
               style="width:100%;margin-top:6px;padding:12px;border-radius:12px;border:1px solid var(--border);" />
      </label>

      <button class="btn btn-primary" type="submit" style="margin-top:18px;width:100%;">Đăng ký</button>
      <p style="margin-top:14px;"><a href="{{ route('login') }}">Đã có tài khoản</a></p>
    </form>
  </main>
</body>
</html>