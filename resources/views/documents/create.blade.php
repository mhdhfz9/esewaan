@extends('layouts.app')

@section('title', 'Muat Naik Dokumen')
@section('header_title', 'Muat Naik Dokumen')
@section('header_subtitle', 'Upload dokumen sokongan kontrak')

@section('content')
<div class="mx-auto max-w-xl glass-card p-6">
    <h1 class="mb-2 text-xl font-bold text-slate-800">Muat Naik Dokumen Sokongan</h1>
    <p class="mb-6 text-sm text-slate-600">{{ $contract->premise?->nama_ptj }} – Kontrak tamat {{ $contract->tarikh_tamat?->format('d/m/Y') }}</p>
    <form method="post" action="{{ route('documents.store', $contract) }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label for="jenis" class="mb-1 block text-sm font-medium text-slate-700">Jenis Dokumen *</label>
            <select name="jenis" id="jenis" required class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                <option value="surat_tawaran">Surat Tawaran</option>
                <option value="gambar_premis">Gambar Premis</option>
                <option value="lain">Lain-lain</option>
            </select>
        </div>
        <div class="mb-6">
            <label for="document" class="mb-1 block text-sm font-medium text-slate-700">Fail * (PDF, JPG, PNG – max 10MB)</label>
            <input type="file" name="document" id="document" required accept=".pdf,.jpg,.jpeg,.png" class="glass-input w-full rounded-xl px-3 py-2 text-sm">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="glass-btn-primary rounded-xl px-4 py-2 text-sm font-medium">Muat Naik</button>
            <a href="{{ route('status-permohonan.index') }}" class="glass-btn-secondary rounded-xl px-4 py-2 text-sm font-medium">Batal</a>
        </div>
    </form>
</div>
@endsection
