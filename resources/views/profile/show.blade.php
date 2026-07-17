@extends('layouts.app')

@section('title', 'Profil Saya')
@section('header_title', 'Profil Saya')
@section('header_subtitle', auth()->user()->name)

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="glass-card p-6">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200/80 text-lg font-semibold text-slate-700 ring-1 ring-white/80">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">{{ $user->name }}</h1>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="glass-btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium">
                Kemaskini profil
            </a>
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Peranan</dt>
                <dd class="mt-1 text-sm text-slate-800">{{ $user->roleLabel() }}</dd>
            </div>
            @if(! $user->isAdminHq())
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Negeri</dt>
                <dd class="mt-1 text-sm text-slate-800">{{ $user->negeri ?? '–' }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Didaftar</dt>
                <dd class="mt-1 text-sm text-slate-800">{{ $user->created_at?->format('d/m/Y H:i') ?? '–' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kemaskini terakhir</dt>
                <dd class="mt-1 text-sm text-slate-800">{{ $user->updated_at?->format('d/m/Y H:i') ?? '–' }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
