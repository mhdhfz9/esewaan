@extends('layouts.app')

@section('title', 'Lupa Kata Laluan')

@section('content')
<div class="glass-card overflow-hidden p-7 sm:p-9">
    <div class="mb-7">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pemulihan akaun</p>
        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Lupa Kata Laluan</h2>
        <p class="mt-1.5 text-sm text-slate-500">
            Masukkan emel berdaftar anda. Kami akan hantar pautan set semula kata laluan ke peti masuk emel.
        </p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" data-no-global-loader>
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

        <button type="submit" class="glass-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-medium">
            Hantar Pautan Set Semula
        </button>
    </form>

    <p class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">
            Kembali ke Log Masuk
        </a>
    </p>
</div>
@endsection
