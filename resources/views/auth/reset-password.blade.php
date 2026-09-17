@extends('layouts.app')

@section('title', 'Set Semula Kata Laluan')

@section('content')
<div class="glass-card overflow-hidden p-7 sm:p-9">
    <div class="mb-7">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kata laluan baharu</p>
        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Set Semula Kata Laluan</h2>
        <p class="mt-1.5 text-sm text-slate-500">Masukkan kata laluan baharu untuk akaun anda.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" data-no-global-loader>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Emel</label>
            <input
                type="email"
                name="email"
                id="email"
                value="{{ old('email', $email) }}"
                required
                autofocus
                class="glass-input w-full rounded-xl px-3.5 py-2.5 text-sm"
                autocomplete="username"
            >
        </div>

        <x-auth.password-field
            label="Kata laluan baharu"
            name="password"
            input-id="reset-password"
            autocomplete="new-password"
            required
        />

        <x-auth.password-field
            label="Sahkan kata laluan baharu"
            name="password_confirmation"
            input-id="reset-password-confirmation"
            autocomplete="new-password"
            required
        />

        <button type="submit" class="glass-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-medium">
            Kemaskini Kata Laluan
        </button>
    </form>

    <p class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">
            Kembali ke Log Masuk
        </a>
    </p>
</div>

@include('partials.auth-password-toggle-script')
@endsection
