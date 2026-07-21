<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'E-Sewaan AADK')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @include('partials.appearance-boot')
    @include('partials.sidebar-scroll-styles')
    @include('partials.glass-theme')
    @include('partials.appearance-theme')
    @stack('styles')
</head>
<body class="liquid-bg min-h-screen font-sans antialiased">
    <div class="liquid-bg-mesh" aria-hidden="true">
        <div class="liquid-orb liquid-orb-1"></div>
        <div class="liquid-orb liquid-orb-2"></div>
        <div class="liquid-orb liquid-orb-3"></div>
    </div>
    @auth
    <div class="flex h-screen overflow-hidden">
        {{-- Desktop Sidebar --}}
        <aside id="sidebar" class="glass-sidebar hidden lg:flex h-full min-h-0 w-60 flex-shrink-0 flex-col overflow-hidden transition-all duration-300">
            <div class="flex h-full min-h-0 flex-col">
                <div class="flex h-20 flex-shrink-0 items-center gap-3 border-b border-white/10 px-4">
                    @php
                        $logoPath = null;
                        $custom = config('app.logo');
                        if ($custom && file_exists(public_path($custom))) {
                            $logoPath = $custom;
                        } else {
                            foreach (['aadk.png', 'logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp', 'images/logo.png', 'images/logo.jpg', 'images/logo.jpeg', 'images/logo.webp'] as $name) {
                                if (file_exists(public_path($name))) {
                                    $logoPath = $name;
                                    break;
                                }
                            }
                        }
                    @endphp
                    @if($logoPath)
                        <img src="{{ asset($logoPath) }}" alt="E-SEWAAN" class="h-9 w-9 min-w-[2.25rem] max-h-9 max-w-9 rounded-xl object-contain flex-shrink-0 shadow-lg bg-white p-0.5">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-slate-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                    @endif
                    <div>
                        <p class="text-white font-semibold text-sm leading-tight">E-SEWAAN</p>
                        <p class="text-white/50 text-xs">Sistem Kontrak Sewaan</p>
                    </div>
                </div>
                <nav class="sidebar-scroll min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-y-contain px-3 py-4 pr-2 scroll-smooth">
                    @if(auth()->user()->isAdminHq())
                    <a href="{{ route('dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                        <span>Dashboard</span>
                    </a>
                    @endif
                    @if(auth()->user()->isAdmin())
                    @include('partials.sidebar-pengurusan-permohonan')
                    @include('partials.sidebar-kontrak-sewaan')
                    @include('partials.sidebar-pengurusan-pengguna')
                    @else
                    @include('partials.sidebar-kontrak-sewaan')
                    @endif
                    @include('partials.sidebar-penampilan')
                </nav>
                @include('partials.sidebar-profile-footer')
            </div>
        </aside>

        {{-- Mobile sidebar overlay --}}
        <div id="mobile-sidebar-overlay" class="lg:hidden fixed inset-0 z-40 hidden">
            <div class="fixed inset-0 bg-black/50" id="mobile-overlay-backdrop"></div>
            <aside class="glass-sidebar relative z-50 flex h-full min-h-0 w-64 flex-col overflow-hidden shadow-xl">
                <div class="flex h-20 flex-shrink-0 items-center justify-between border-b border-white/10 px-4">
                    <div class="flex items-center gap-3">
                        @if(isset($logoPath) && $logoPath)
                            <img src="{{ asset($logoPath) }}" alt="E-SEWAAN" class="h-9 w-9 min-w-[2.25rem] max-h-9 max-w-9 rounded-xl object-contain flex-shrink-0 bg-white p-0.5">
                        @else
                            @php
                                if (!isset($logoPath) || !$logoPath) {
                                    $logoPath = null;
                                    $custom = config('app.logo');
                                    if ($custom && file_exists(public_path($custom))) {
                                        $logoPath = $custom;
                                    } else {
                                        foreach (['aadk.png', 'logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp', 'images/logo.png', 'images/logo.jpg', 'images/logo.jpeg', 'images/logo.webp'] as $name) {
                                            if (file_exists(public_path($name))) {
                                                $logoPath = $name;
                                                break;
                                            }
                                        }
                                    }
                                }
                            @endphp
                            @if($logoPath)
                                <img src="{{ asset($logoPath) }}" alt="E-SEWAAN" class="h-9 w-9 min-w-[2.25rem] max-h-9 max-w-9 rounded-xl object-contain flex-shrink-0 bg-white p-0.5">
                            @else
                                <div class="w-9 h-9 rounded-xl bg-slate-600 flex items-center justify-center"><span class="text-white font-bold text-sm">E</span></div>
                            @endif
                        @endif
                        <div><p class="text-white font-semibold text-sm">E-SEWAAN</p><p class="text-white/50 text-xs">Sistem Kontrak Sewaan</p></div>
                    </div>
                    <button type="button" id="mobile-close-sidebar" class="p-2 text-white/60 hover:text-white">&times;</button>
                </div>
                <nav class="sidebar-scroll min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-y-contain px-3 py-4 pr-2 scroll-smooth">
                    @if(auth()->user()->isAdminHq())
                    <a href="{{ route('dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                        <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    @endif
                    @if(auth()->user()->isAdmin())
                    @include('partials.sidebar-pengurusan-permohonan')
                    @include('partials.sidebar-kontrak-sewaan')
                    @include('partials.sidebar-pengurusan-pengguna')
                    @else
                    @include('partials.sidebar-kontrak-sewaan')
                    @endif
                    @include('partials.sidebar-penampilan')
                </nav>
                @include('partials.sidebar-profile-footer')
            </aside>
        </div>

        {{-- Main content --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="glass-header flex h-20 flex-shrink-0 items-center justify-between px-4 lg:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" id="mobile-open-sidebar" class="lg:hidden p-2 rounded-xl glass-subtle hover:bg-white/60 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div>
                        <h1 class="text-base font-semibold text-slate-900">@yield('header_title', 'E-Sewaan')</h1>
                        <p class="text-xs text-slate-500 hidden sm:block">@yield('header_subtitle', 'Sistem Pengurusan Kontrak Sewaan AADK')</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div class="p-4 lg:p-6">
                    @if(session('success'))
                    <div class="mb-4 rounded-xl glass-alert-success p-4 text-green-800 text-sm" role="alert">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                    <div class="mb-4 rounded-xl glass-alert-error p-4 text-red-800 text-sm" role="alert">{{ session('error') }}</div>
                    @endif
                    @if(isset($errors) && $errors->any())
                    <div class="mb-4 rounded-xl glass-alert-warning p-4 text-amber-900 text-sm">
                        <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    <script>
        document.getElementById('mobile-open-sidebar')?.addEventListener('click', function() {
            document.getElementById('mobile-sidebar-overlay').classList.remove('hidden');
        });
        function closeMobileSidebar() {
            document.getElementById('mobile-sidebar-overlay').classList.add('hidden');
        }
        document.getElementById('mobile-close-sidebar')?.addEventListener('click', closeMobileSidebar);
        document.getElementById('mobile-overlay-backdrop')?.addEventListener('click', closeMobileSidebar);
    </script>
    @else
    @if(request()->routeIs('login'))
    <main class="relative z-10 min-h-screen">
        @if(session('success'))
            <div class="absolute left-1/2 top-4 z-30 w-[min(28rem,calc(100%-2rem))] -translate-x-1/2 rounded-xl glass-alert-success p-4 text-sm text-green-800 shadow-lg lg:left-auto lg:right-6 lg:translate-x-0">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="absolute left-1/2 top-4 z-30 w-[min(28rem,calc(100%-2rem))] -translate-x-1/2 rounded-xl glass-alert-error p-4 text-sm text-red-800 shadow-lg lg:left-auto lg:right-6 lg:translate-x-0">{{ session('error') }}</div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="absolute left-1/2 top-4 z-30 w-[min(28rem,calc(100%-2rem))] -translate-x-1/2 rounded-xl glass-alert-warning p-4 text-sm text-amber-900 shadow-lg lg:left-auto lg:right-6 lg:translate-x-0">
                <ul class="list-inside list-disc">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    @else
    {{-- Guest: simple top bar --}}
    <header class="glass-header sticky top-0 z-20">
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4">
            <a href="{{ route('login') }}" class="flex items-center gap-3">
                @php
                    $guestLogoPath = null;
                    $custom = config('app.logo');
                    if ($custom && file_exists(public_path($custom))) {
                        $guestLogoPath = $custom;
                    } else {
                        foreach (['aadk.png', 'logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp', 'images/logo.png', 'images/logo.jpg', 'images/logo.jpeg', 'images/logo.webp'] as $name) {
                            if (file_exists(public_path($name))) {
                                $guestLogoPath = $name;
                                break;
                            }
                        }
                    }
                @endphp
                @if($guestLogoPath)
                    <img src="{{ asset($guestLogoPath) }}" alt="E-SEWAAN" class="h-9 w-9 rounded-lg object-contain flex-shrink-0">
                @endif
                <span class="text-lg font-semibold text-gray-900">E-SEWAAN</span>
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}" class="glass-btn-primary rounded-xl px-4 py-2 text-sm font-medium">Log masuk</a>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-8">
        @if(session('success'))<div class="mb-4 rounded-xl glass-alert-success p-4 text-green-800">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 rounded-xl glass-alert-error p-4 text-red-800">{{ session('error') }}</div>@endif
        @if(isset($errors) && $errors->any())<div class="mb-4 rounded-xl glass-alert-warning p-4 text-amber-800"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
    @endif
    @endauth

    @stack('scripts')
</body>
</html>
