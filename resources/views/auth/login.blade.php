<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Đăng nhập — Jobly</title>
    <link rel="stylesheet" href="{{ asset('jobly/css/style.css') }}" />
</head>

<body>
    <main style="min-height:100vh;display:grid;place-items:center;padding:24px;">
        <form method="POST" action="{{ route('login') }}" class="card" style="width:min(420px,100%);padding:28px;">
            @csrf
            <h1 class="page-title">Đăng nhập</h1>
            <p class="page-sub">Sinh viên, nhà tuyển dụng hoặc admin.</p>

            <label style="display:block;margin-top:18px;">
                Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
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

            <label style="display:flex;gap:8px;align-items:center;margin-top:14px;">
                <input type="checkbox" name="remember" value="1" />
                Ghi nhớ đăng nhập
            </label>

            <button class="btn btn-primary" type="submit" style="margin-top:18px;width:100%;">Vào Jobly</button>
        </form>
    </main>
</body>

</html>
