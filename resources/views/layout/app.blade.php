<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Jobly')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('jobly/css/style.css') }}?v={{ filemtime(public_path('jobly/css/style.css')) }}" />
</head>

<body data-page="@yield('page')">
    <div class="app-bg" aria-hidden="true">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="grid-overlay"></div>
    </div>
    <div class="app @hasSection('hide-rail')
@else
has-rail
@endif">
        <aside class="sidebar" id="sidebar"></aside>
        <main class="main">
            @yield('content')
        </main>
        @hasSection('hide-rail')
        @else
            <aside class="rail" id="rail"></aside>
        @endif
    </div>
    <nav class="bottom-nav" id="bottom-nav"></nav>
    <script>
        window.JOBLY = @json($jobly);
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('jobly/js/auth.js') }}?v={{ filemtime(public_path('jobly/js/auth.js')) }}"></script>
    <script src="{{ asset('jobly/js/data.js') }}?v={{ filemtime(public_path('jobly/js/data.js')) }}"></script>
    <script src="{{ asset('jobly/js/swipe.js') }}?v={{ filemtime(public_path('jobly/js/swipe.js')) }}"></script>
    <script src="{{ asset('jobly/js/app.js') }}?v={{ filemtime(public_path('jobly/js/app.js')) }}"></script>
</body>

</html>
