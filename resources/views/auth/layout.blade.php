<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title') — Jobly</title>
    <link rel="stylesheet" href="{{ asset('jobly/css/style.css') }}" />
    <style>
        .auth-wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .auth-card { width: min(440px, 100%); padding: 28px; }
        .auth-field { display: block; margin-top: 14px; font-weight: 600; font-size: 14px; }
        .auth-field input { width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px; border: 1px solid var(--border); font: inherit; font-weight: 400; }
        .auth-error { color: var(--danger, #dc2626); margin-top: 6px; font-size: 13px; }
        .auth-tabs { display: flex; gap: 8px; margin-top: 16px; }
        .auth-tabs a { flex: 1; text-align: center; padding: 10px; border-radius: 12px; border: 1px solid var(--border); text-decoration: none; color: inherit; font-weight: 600; font-size: 14px; }
        .auth-tabs a.active { background: var(--primary, #7C5CFF); border-color: transparent; color: #fff; }
        .auth-foot { margin-top: 16px; font-size: 14px; text-align: center; }
    </style>
</head>

<body>
    <main class="auth-wrap">
        @yield('content')
    </main>
</body>

</html>
