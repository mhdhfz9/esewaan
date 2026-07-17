@extends('layouts.app')

@section('title', 'Kemaskini Profil')
@section('header_title', 'Kemaskini Profil')
@section('header_subtitle', auth()->user()->name)

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="glass-card p-6">
        <h1 class="mb-6 text-xl font-bold text-slate-800">Kemaskini profil saya</h1>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama penuh</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Emel</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @if(! $user->isAdminHq())
            <div>
                <label for="negeri" class="mb-1 block text-sm font-medium text-slate-700">Negeri</label>
                @if($canEditNegeri)
                <select name="negeri" id="negeri"
                    class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                    <option value="">— Pilih negeri —</option>
                    @foreach($negeriList as $value => $label)
                    <option value="{{ $value }}" @selected(old('negeri', $user->negeri) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @else
                <p class="glass-field-static px-3 py-2 text-sm text-slate-700">{{ $user->negeri ?? '–' }}</p>
                <p class="mt-1 text-xs text-slate-500">Negeri ditetapkan oleh pentadbir dan tidak boleh diubah di sini.</p>
                @endif
                @error('negeri')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Peranan</label>
                <p class="glass-field-static px-3 py-2 text-sm text-slate-700">{{ $user->roleLabel() }}</p>
                <p class="mt-1 text-xs text-slate-500">Peranan hanya boleh diubah oleh pentadbir.</p>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="mb-3 text-sm font-medium text-amber-900">Tukar kata laluan</p>
                <p class="mb-4 text-xs text-amber-800">Biarkan kosong jika tidak mahu menukar kata laluan.</p>
                <div class="space-y-4">
                    <x-auth.password-field
                        label="Kata laluan baharu"
                        name="password"
                        input-id="profile-password"
                        autocomplete="new-password"
                    />
                    <x-auth.password-field
                        label="Sahkan kata laluan baharu"
                        name="password_confirmation"
                        input-id="profile-password-confirm"
                        autocomplete="new-password"
                    />
                </div>
                @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="glass-btn-primary rounded-xl px-4 py-2.5 text-sm font-medium">
                    Simpan perubahan
                </button>
                <a href="{{ route('profile.show') }}" class="glass-btn-secondary rounded-xl px-4 py-2.5 text-sm font-medium">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@include('partials.auth-password-toggle-script')
@endsection
