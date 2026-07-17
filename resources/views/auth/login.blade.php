@extends('layouts.app')

@section('title', 'Log Masuk')

@section('content')
@php
    $loginLogoPath = null;
    $custom = config('app.logo');
    if ($custom && file_exists(public_path($custom))) {
        $loginLogoPath = $custom;
    } else {
        foreach (['aadk.png', 'logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp', 'images/logo.png', 'images/logo.jpg', 'images/logo.jpeg', 'images/logo.webp'] as $name) {
            if (file_exists(public_path($name))) {
                $loginLogoPath = $name;
                break;
            }
        }
    }
@endphp

<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Brand panel --}}
    <aside class="relative flex min-h-[42vh] flex-col justify-between overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 px-8 py-10 text-white lg:min-h-screen lg:px-12 lg:py-14">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -left-16 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-24 right-0 h-80 w-80 rounded-full bg-slate-500/20 blur-3xl"></div>
            <div class="absolute left-1/3 top-1/2 h-40 w-40 -translate-y-1/2 rounded-full bg-white/5 blur-2xl"></div>
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.06)_0%,transparent_45%,rgba(255,255,255,0.03)_100%)]"></div>
        </div>

        <div class="relative z-10">
            <div class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-3 py-2 shadow-lg backdrop-blur-xl">
                @if($loginLogoPath)
                    <img src="{{ asset($loginLogoPath) }}" alt="AADK" class="h-11 w-11 rounded-xl bg-white object-contain p-1">
                @else
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-sm font-bold">E</div>
                @endif
                <div>
                    <p class="text-sm font-semibold tracking-wide">E-SEWAAN</p>
                    <p class="text-xs text-white/60">AADK</p>
                </div>
            </div>
        </div>

        <div class="relative z-10 mt-10 max-w-lg lg:mt-0">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/50">Sistem Pengurusan</p>
            <h1 class="mt-3 text-3xl font-semibold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
                Kontrak Sewaan Premis
            </h1>
            <p class="mt-4 text-sm leading-relaxed text-white/70 sm:text-base">
                Pantau permohonan, tindakan dan status kontrak sewaan dalam satu platform.
            </p>
        </div>

        <p class="relative z-10 mt-10 text-xs text-white/40 lg:mt-0">
            © {{ date('Y') }} Agensi Antidadah Kebangsaan
        </p>
    </aside>

    {{-- Form panel --}}
    <section class="relative flex items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute right-[-10%] top-[-8%] h-64 w-64 rounded-full bg-slate-300/30 blur-3xl"></div>
            <div class="absolute bottom-[-12%] left-[-6%] h-72 w-72 rounded-full bg-slate-400/20 blur-3xl"></div>
        </div>

        <div class="relative z-10 w-full max-w-md">
            <div class="glass-card overflow-hidden p-7 sm:p-9">
                <div class="mb-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Selamat kembali</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Log Masuk</h2>
                    <p class="mt-1.5 text-sm text-slate-500">Masukkan maklumat akaun anda untuk meneruskan.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Emel</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            class="glass-input w-full rounded-xl px-3.5 py-2.5 text-sm"
                            placeholder="nama@contoh.com"
                            autocomplete="email"
                        >
                    </div>

                    <x-auth.password-field
                        label="Kata laluan"
                        name="password"
                        input-id="login-password"
                        autocomplete="current-password"
                        required
                    />

                    <div class="flex items-center justify-between gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" id="remember" class="h-4 w-4 rounded border-slate-300 text-slate-800 focus:ring-slate-500">
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="glass-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-medium">
                        Log Masuk
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-slate-500">
                Akses terhad kepada pengguna berdaftar sistem E-Sewaan.
            </p>
        </div>
    </section>
</div>

@include('partials.auth-password-toggle-script')
@endsection
