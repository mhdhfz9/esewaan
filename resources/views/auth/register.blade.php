@extends('layouts.app')

@section('title', 'Pendaftaran Pengguna')
@section('header_title', 'Pendaftaran Pengguna')
@section('header_subtitle', 'Pengurusan Pengguna — cipta akaun pengguna baharu')

@section('content')
@php
    $defaultRole = old('role', auth()->user()->isAdminNegeri() ? 'admin_negeri' : 'admin_hq');
    $showNegeriField = ($lockNegeri ?? false) || $defaultRole === 'admin_negeri';
@endphp
<div class="flex min-h-[calc(100vh-7rem)] items-center justify-center lg:min-h-[calc(100vh-8rem)]">
    <div class="glass-card w-full max-w-md p-8">
        <h1 class="mb-6 text-2xl font-bold text-slate-800">Pendaftaran Pengguna</h1>
        <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
            @csrf
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Emel</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
            </div>
            <x-auth.password-field
                label="Kata laluan"
                name="password"
                input-id="register-password"
                autocomplete="new-password"
                required
            />
            <x-auth.password-field
                label="Sahkan kata laluan"
                name="password_confirmation"
                input-id="register-password-confirm"
                autocomplete="new-password"
                required
            />
            <div>
                <label for="role" class="mb-1 block text-sm font-medium text-slate-700">Peranan</label>
                <select name="role" id="role" required class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                    @if(! auth()->user()->isAdminNegeri())
                    <option value="admin_hq" @selected($defaultRole === 'admin_hq')>Admin</option>
                    @endif
                    <option value="admin_negeri" @selected($defaultRole === 'admin_negeri')>Negeri</option>
                </select>
                @error('role')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div id="negeri-field" @class(['hidden' => ! $showNegeriField])>
                <label for="negeri" class="mb-1 block text-sm font-medium text-slate-700">Negeri</label>
                @if($lockNegeri ?? false)
                <input type="hidden" name="negeri" value="{{ $lockedNegeri }}">
                <p class="glass-field-static px-3 py-2 text-sm text-slate-700">{{ $lockedNegeri }}</p>
                <p class="mt-1 text-xs text-slate-500">Negeri dikunci mengikut negeri pentadbir anda.</p>
                @else
                <select name="negeri" id="negeri"
                    @required($showNegeriField)
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                    <option value="">- Pilih Negeri -</option>
                    @foreach($negeriList as $value => $label)
                    <option value="{{ $value }}" @selected(old('negeri') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @endif
                @error('negeri')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="glass-btn-primary w-full rounded-xl px-4 py-2.5 font-medium">
                Daftar pengguna
            </button>
        </form>
    </div>
</div>
@include('partials.auth-password-toggle-script')
@if(! ($lockNegeri ?? false))
@push('scripts')
<script>
(function () {
    const roleSelect = document.getElementById('role');
    const negeriField = document.getElementById('negeri-field');
    const negeriSelect = document.getElementById('negeri');

    if (!roleSelect || !negeriField || !negeriSelect) {
        return;
    }

    function syncNegeriVisibility() {
        const needsNegeri = roleSelect.value === 'admin_negeri';
        negeriField.classList.toggle('hidden', !needsNegeri);
        negeriSelect.required = needsNegeri;

        if (!needsNegeri) {
            negeriSelect.value = '';
        }
    }

    roleSelect.addEventListener('change', syncNegeriVisibility);
    syncNegeriVisibility();
})();
</script>
@endpush
@endif
@endsection
