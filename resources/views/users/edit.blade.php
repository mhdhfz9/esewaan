@extends('layouts.app')

@section('title', 'Urus Profil Pengguna')
@section('header_title', 'Urus Profil Pengguna')
@section('header_subtitle', $user->name)

@section('content')
@php
    $selectedRole = old('role', $user->role);
    $showNegeriField = ($lockNegeri ?? false) || $selectedRole === 'admin_negeri';
@endphp
<div class="mx-auto max-w-2xl space-y-6">
    <div class="glass-card p-6">
        <h1 class="mb-6 text-xl font-bold text-slate-800">Kemaskini profil</h1>
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama penuh</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Emel</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label for="role" class="mb-1 block text-sm font-medium text-slate-700">Peranan</label>
                @if($user->is(auth()->user()))
                <p class="glass-field-static px-3 py-2 text-sm text-slate-700">{{ $user->roleLabel() }}</p>
                <p class="mt-1 text-xs text-slate-500">Anda tidak boleh menukar peranan sendiri.</p>
                <input type="hidden" id="role" value="{{ $user->role }}">
                @else
                <select name="role" id="role" required
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                    <option value="admin_negeri" @selected($selectedRole === 'admin_negeri')>Negeri</option>
                    @if(! auth()->user()->isAdminNegeri())
                    <option value="admin_hq" @selected($selectedRole === 'admin_hq')>Admin</option>
                    @endif
                </select>
                @error('role')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @endif
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
                    <option value="">— Pilih negeri —</option>
                    @foreach($negeriList as $value => $label)
                    <option value="{{ $value }}" @selected(old('negeri', $user->negeri) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @endif
                @error('negeri')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="mb-3 text-sm font-medium text-amber-900">Reset kata laluan</p>
                <p class="mb-4 text-xs text-amber-800">Biarkan kosong jika tidak mahu menukar kata laluan.</p>
                <div class="space-y-4">
                    <x-auth.password-field
                        label="Kata laluan baharu"
                        name="password"
                        input-id="admin-reset-password"
                        autocomplete="new-password"
                    />
                    <x-auth.password-field
                        label="Sahkan kata laluan baharu"
                        name="password_confirmation"
                        input-id="admin-reset-password-confirm"
                        autocomplete="new-password"
                    />
                </div>
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="glass-btn-primary rounded-xl px-4 py-2.5 text-sm font-medium">
                    Simpan perubahan
                </button>
                <a href="{{ route('users.index') }}" class="glass-btn-secondary rounded-xl px-4 py-2.5 text-sm font-medium">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@include('partials.auth-password-toggle-script')
@if(! ($lockNegeri ?? false) && ! $user->is(auth()->user()))
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
