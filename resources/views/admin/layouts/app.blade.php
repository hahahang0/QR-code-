<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · QR Manager</title>
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}-scanner-shared-layout">
</head>
<body class="font-sans antialiased" data-profile-origin="{{ config('app.url') }}">
    <div class="admin-shell min-h-screen bg-slate-50">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">⌗</span><span>QR Manager<small>ADMIN WORKSPACE</small></span></a>
            <p class="nav-caption">MAIN MENU</p>
            <nav class="side-nav" aria-label="Main navigation">
                <a @class(['active' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}"><span>◫</span>Dashboard</a>
                <a @class(['active' => request()->routeIs('admin.people.*')]) href="{{ route('admin.people.index') }}"><span>♙</span>People Management</a>
                <a @class(['active' => request()->routeIs('admin.qr-codes.*')]) href="{{ route('admin.qr-codes.index') }}"><span>▦</span>QR Code Management</a>
                <a @class(['active' => request()->routeIs('scanner')]) href="{{ route('scanner') }}"><span>⌗</span>QR Scanner</a>
                <a @class(['active' => request()->routeIs('admin.activity-logs.*')]) href="{{ route('admin.activity-logs.index') }}"><span>◷</span>Activity Logs</a>
            </nav>
            <div class="sidebar-bottom"><div class="help-card"><strong>Public portfolios</strong><span>QR codes open each person’s professional profile.</span><a href="{{ route('scanner') }}">Open scanner <b>→</b></a></div></div>
        </aside>
        <main class="main-content">
            <header class="mobile-topbar"><button type="button" class="icon-button" onclick="document.body.classList.toggle('nav-open')" aria-label="Toggle navigation">☰</button><a class="mobile-brand" href="{{ route('admin.dashboard') }}">QR Manager</a></header>
            <div class="content-wrap">
                @if (session('status'))<div class="flash-success" role="status">{{ session('status') }}</div>@endif
                @if ($errors->any())<div class="flash-error" role="alert"><strong>Review the following:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
                <footer class="page-footer">© {{ date('Y') }} QR Manager <span>Profile management · v1.0.0</span></footer>
            </div>
        </main>
    </div>
    <script>document.addEventListener('click', e => { if (e.target.closest('[data-confirm]') && !confirm(e.target.closest('[data-confirm]').dataset.confirm)) e.preventDefault(); });</script>
    @stack('scripts')
    <script src="{{ asset('js/page-loading.js') }}?v={{ filemtime(public_path('js/page-loading.js')) }}" defer></script>
</body>
</html>
