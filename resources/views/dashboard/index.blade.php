@extends('layouts.app')

@section('title', 'Papan Pemuka')
@section('header_title', 'Papan Pemuka')
@section('header_subtitle', 'Ringkasan permohonan dan kontrak sewaan semasa')

@section('content')
@php
    $kontrakClassTotal = max(1, (int) ($kontrakClassChart['total'] ?? 0));
    $kontrakClassValues = $kontrakClassChart['values'] ?? [0, 0, 0];
    $kontrakClassColors = $kontrakClassChart['colors'] ?? ['#059669', '#d97706', '#dc2626'];
    $kontrakClassLabels = $kontrakClassChart['labels'] ?? [];
    $donutStops = [];
    $cursor = 0;
    foreach ($kontrakClassValues as $index => $value) {
        $start = $cursor;
        $share = ((int) $value / $kontrakClassTotal) * 100;
        $cursor += $share;
        $donutStops[] = ($kontrakClassColors[$index] ?? '#94a3b8').' '.$start.'% '.$cursor.'%';
    }
    $donutBackground = $donutStops === []
        ? 'conic-gradient(#e2e8f0 0% 100%)'
        : 'conic-gradient('.implode(', ', $donutStops).')';
@endphp

<div class="space-y-6">
    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <a href="{{ route('status-permohonan.index') }}" class="glass-card glass-kpi-card block p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50/80 ring-1 ring-sky-100/80">
                    <svg class="h-5 w-5 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($permohonanBaharu) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Permohonan Baharu</p>
            <p class="mt-1.5 text-xs text-sky-600">Menunggu semakan Ibu Pejabat</p>
        </a>

        <a href="#dalam-tindakan" class="glass-card glass-kpi-card block p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50/80 ring-1 ring-indigo-100/80">
                    <svg class="h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($progressPermohonan) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Dalam Tindakan</p>
            <p class="mt-1.5 text-xs text-indigo-600">Dalam penyediaan draf perjanjian</p>
        </a>

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

        <a href="#peringatan-lapan" class="glass-card glass-kpi-card block p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50/80 ring-1 ring-amber-100/80">
                    <svg class="h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($kontrakLapanBulan) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Kontrak Bawah 8 Bulan</p>
            <p class="mt-1.5 text-xs text-amber-600">3 hingga bawah 8 bulan</p>
        </a>

        <a href="#peringatan-tiga" class="glass-card glass-kpi-card block border border-red-200/80 bg-red-50/70 p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 ring-1 ring-red-200">
                    <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-red-700 lg:text-3xl">{{ number_format($kontrakTigaBulan) }}</p>
            <p class="mt-1 text-xs font-medium text-red-700">Kontrak Bawah 3 Bulan</p>
            <p class="mt-1.5 text-xs text-red-600">Perlu tindakan segera</p>
        </a>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="glass-card overflow-hidden p-5">
            <div class="mb-4">
                <h3 class="font-semibold text-slate-900">Taburan Baki Tempoh Kontrak</h3>
                <p class="mt-0.5 text-xs text-slate-500">Bilangan kontrak aktif mengikut kelas baki tempoh</p>
            </div>
            <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-center">
                <div
                    class="relative h-40 w-40 shrink-0 rounded-full"
                    style="background: {{ $donutBackground }};"
                    role="img"
                    aria-label="Carta taburan baki tempoh kontrak"
                >
                    <div class="absolute inset-6 flex flex-col items-center justify-center rounded-full bg-white shadow-inner">
                        <p class="text-2xl font-bold text-slate-900">{{ number_format($kontrakAktif) }}</p>
                        <p class="text-[11px] font-medium text-slate-500">aktif</p>
                    </div>
                </div>
                <ul class="w-full space-y-3">
                    @foreach($kontrakClassLabels as $index => $label)
                        <li class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="h-3 w-3 shrink-0 rounded-full" style="background: {{ $kontrakClassColors[$index] }}"></span>
                                <span class="truncate text-sm text-slate-700">{{ $label }}</span>
                            </div>
                            <span class="text-sm font-semibold text-slate-900">{{ number_format($kontrakClassValues[$index] ?? 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="glass-card overflow-hidden p-5">
            <div class="mb-4">
                <h3 class="font-semibold text-slate-900">Bilangan Mengikut Status Tindakan</h3>
                <p class="mt-0.5 text-xs text-slate-500">Hanya status yang mempunyai kontrak dipaparkan</p>
            </div>
            @if(($statusChart['labels'] ?? []) === [])
                <div class="flex h-40 items-center justify-center text-sm text-slate-500">
                    Tiada permohonan dalam tindakan buat masa ini.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($statusChart['labels'] as $index => $label)
                        @php
                            $value = (int) ($statusChart['values'][$index] ?? 0);
                            $width = max(4, (int) round(($value / max(1, (int) $statusChart['max'])) * 100));
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between gap-3">
                                <p class="truncate text-xs font-medium text-slate-700" title="{{ $label }}">{{ $label }}</p>
                                <p class="shrink-0 text-xs font-semibold text-indigo-700">{{ number_format($value) }}</p>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-indigo-500" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Dalam Tindakan: pecahan mengikut status --}}
    <div id="dalam-tindakan" class="glass-card overflow-hidden scroll-mt-24">
        <div class="glass-divider-soft flex items-center justify-between border-b px-5 py-4">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                <h3 class="font-semibold text-gray-900">Status Tindakan</h3>
                @if($progressPermohonan > 0)
                <span class="rounded-md bg-indigo-100 px-1.5 py-0.5 text-xs font-bold text-indigo-700">{{ $progressPermohonan }}</span>
                @endif
            </div>
            <p class="text-xs text-gray-400">Bilangan kontrak mengikut status permohonan</p>
        </div>
        <div class="divide-y divide-slate-200/50">
            @foreach($dalamTindakanByStatus as $row)
                <div class="flex items-center gap-3 px-5 py-3.5">
                    <div class="h-2 w-2 flex-shrink-0 rounded-full {{ $row['count'] > 0 ? 'bg-indigo-500' : 'bg-slate-300' }}"></div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $row['status'] }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-sm font-semibold {{ $row['count'] > 0 ? 'text-indigo-700' : 'text-slate-400' }}">{{ number_format($row['count']) }}</p>
                        <p class="text-xs text-gray-400">kontrak</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Alert list (3 bulan) --}}
    <div id="peringatan-tiga" class="glass-card overflow-hidden scroll-mt-24 border border-red-200/70">
        <div class="glass-divider-soft flex items-center justify-between border-b border-red-100 bg-red-50/60 px-5 py-4">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                <h3 class="font-semibold text-red-800">Kontrak Bawah 3 Bulan</h3>
                @if($alertListTigaBulan->isNotEmpty())
                <span class="rounded-md bg-red-100 px-1.5 py-0.5 text-xs font-bold text-red-700">{{ $alertListTigaBulan->count() }}</span>
                @endif
            </div>
            <p class="text-xs text-red-600/80">Kontrak aktif dengan baki bawah 3 bulan</p>
        </div>
        <div class="divide-y divide-red-100/80">
            @forelse($alertListTigaBulan as $c)
                <a href="{{ route('kontrak-sewaan.show', $c) }}" class="glass-row-hover flex items-center gap-3 px-5 py-3.5">
                    <div class="h-2 w-2 flex-shrink-0 rounded-full bg-red-500"></div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $c->premise?->nama_ptj ?? '–' }}</p>
                        <p class="text-xs text-gray-400">{{ $c->premise?->negeri ?? '–' }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs font-semibold text-red-700">{{ $c->daysUntilContractEndLabel() }}</p>
                    </div>
                </a>
            @empty
                <div class="py-10 text-center text-gray-400">
                    <p class="text-sm font-medium text-emerald-600">Tiada kontrak bawah 3 bulan</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Alert list (8 bulan, excluding 3 months) --}}
    <div id="peringatan-lapan" class="glass-card overflow-hidden scroll-mt-24">
        <div class="glass-divider-soft flex items-center justify-between border-b px-5 py-4">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h3 class="font-semibold text-gray-900">Kontrak Bawah 8 Bulan</h3>
                @if($alertListLapanBulan->isNotEmpty())
                <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-xs font-bold text-amber-700">{{ $alertListLapanBulan->count() }}</span>
                @endif
            </div>
            <p class="text-xs text-gray-400">Baki 3 hingga bawah 8 bulan (tidak termasuk bawah 3 bulan)</p>
        </div>
        <div class="divide-y divide-slate-200/50">
            @forelse($alertListLapanBulan as $c)
                <a href="{{ route('kontrak-sewaan.show', $c) }}" class="glass-row-hover flex items-center gap-3 px-5 py-3.5">
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
                    <p class="text-sm font-medium text-emerald-600">Tiada kontrak dalam julat bawah 8 bulan</p>
                    <p class="mt-1 text-xs">Kontrak kritikal bawah 3 bulan dipaparkan dalam senarai merah</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
