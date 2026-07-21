@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')
@section('header_subtitle', 'Ringkasan permohonan dan kontrak sewaan semasa')

@section('content')
<div class="space-y-6">
    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {{-- 1. Permohonan baharu --}}
        <a href="{{ route('status-permohonan.index') }}" class="glass-card glass-kpi-card block p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50/80 ring-1 ring-sky-100/80">
                    <svg class="h-5 w-5 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($permohonanBaharu) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Permohonan Baharu</p>
            <p class="mt-1.5 text-xs text-sky-600">Menunggu semakan HQ</p>
        </a>

        {{-- 2. Progress permohonan --}}
        <a href="{{ route('status-permohonan.index') }}" class="glass-card glass-kpi-card block p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50/80 ring-1 ring-indigo-100/80">
                    <svg class="h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($progressPermohonan) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Progress Permohonan</p>
            <p class="mt-1.5 text-xs text-indigo-600">Dalam penyediaan draf perjanjian</p>
        </a>

        {{-- 3. Kontrak aktif --}}
        <a href="{{ route('kontrak-sewaan.index') }}" class="glass-card glass-kpi-card block p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50/80 ring-1 ring-emerald-100/80">
                    <svg class="h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($kontrakAktif) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Kontrak Aktif</p>
            <p class="mt-1.5 text-xs text-emerald-600">Dalam senarai kontrak sewaan</p>
        </a>

        {{-- 4. Kontrak tinggal 8 bulan --}}
        <a href="#peringatan" class="glass-card glass-kpi-card block p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50/80 ring-1 ring-amber-100/80">
                    <svg class="h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($kontrakLapanBulan) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Kontrak &le; 8 Bulan</p>
            <p class="mt-1.5 text-xs text-amber-600">Perlu penyediaan JRP segera</p>
        </a>
    </div>

    {{-- Alert list (8 bulan) --}}
    <div id="peringatan" class="glass-card overflow-hidden scroll-mt-24">
        <div class="glass-divider-soft flex items-center justify-between border-b px-5 py-4">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h3 class="font-semibold text-gray-900">Peringatan 8 Bulan – Tindakan Segera</h3>
                @if($alertList->isNotEmpty())
                <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-xs font-bold text-amber-700">{{ $alertList->count() }}</span>
                @endif
            </div>
            <p class="text-xs text-gray-400">Kontrak aktif dengan baki &le; 8 bulan</p>
        </div>
        <div class="divide-y divide-slate-200/50">
            @forelse($alertList as $c)
                <a href="{{ route('kontrak-sewaan.show', $c) }}" class="glass-row-hover flex items-center gap-3 px-5 py-3.5 transition-colors">
                    <div class="h-2 w-2 flex-shrink-0 rounded-full bg-amber-500"></div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $c->premise?->nama_ptj ?? '–' }}</p>
                        <p class="text-xs text-gray-400">{{ $c->premise?->negeri ?? '–' }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs font-semibold text-amber-700">{{ $c->daysUntilContractEndLabel() }}</p>
                    </div>
                </a>
            @empty
                <div class="py-10 text-center text-gray-400">
                    <svg class="mx-auto mb-2 h-8 w-8 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm font-medium text-emerald-600">Tiada kontrak dalam zon peringatan</p>
                    <p class="mt-1 text-xs">Semua kontrak aktif mempunyai baki lebih 8 bulan</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
