<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Jobly</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('jobly/css/style.css') }}?v={{ filemtime(public_path('jobly/css/style.css')) }}" />
</head>

{{-- Khung chung cho khu nhà tuyển dụng và admin. $nav: [['route' => ..., 'label' => ..., 'icon' => ..., 'active' => pattern]] --}}
<body class="panel-body">
    <div class="app panel-app">
        <aside class="sidebar panel-sidebar">
            <a class="logo" href="{{ url(auth()->user()->role->homePath()) }}">
                <span class="logo-mark"><i data-lucide="sparkles"></i></span>
                <span>Jobly<small>{{ $panelLabel }}</small></span>
            </a>
            <nav class="nav-list">
                @foreach ($nav as $item)
                    <a @class(['nav-item', 'is-active' => request()->routeIs($item['active'])]) href="{{ route($item['route']) }}">
                        <i data-lucide="{{ $item['icon'] }}"></i>{{ $item['label'] }}
                        @if (! empty($item['badge']))
                            <span class="nav-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
            <div class="sidebar-user-row">
                <div class="sidebar-user">
                    <span class="avatar panel-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div><strong>{{ auth()->user()->name }}</strong><span>{{ $panelSubtitle }}</span></div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="icon-btn icon-btn--ghost" type="submit" aria-label="Đăng xuất" title="Đăng xuất"><i data-lucide="log-out"></i></button>
                </form>
            </div>
        </aside>

        <main class="main panel-main">
            @if (session('status'))
                <div class="flash flash--ok" role="status"><i data-lucide="circle-check"></i>{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash flash--error" role="alert">
                    <i data-lucide="circle-alert"></i>
                    <div>
                        <strong>Chưa lưu được, kiểm tra lại:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <nav class="bottom-nav panel-bottom-nav">
        @foreach ($nav as $item)
            <a @class(['is-active' => request()->routeIs($item['active'])]) href="{{ route($item['route']) }}">
                <i data-lucide="{{ $item['icon'] }}"></i>{{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('jobly/js/auth.js') }}?v={{ filemtime(public_path('jobly/js/auth.js')) }}"></script>
    <script src="{{ asset('jobly/js/panel.js') }}?v={{ filemtime(public_path('jobly/js/panel.js')) }}"></script>
    @stack('scripts')
</body>

</html>
