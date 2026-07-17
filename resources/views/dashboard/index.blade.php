@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')
@section('header_subtitle', 'Overview of all premise contracts')

@section('content')
<div class="space-y-6">
    {{-- Filter (admin only) --}}
    <form method="get" action="{{ route('dashboard') }}" class="glass-filter-bar flex flex-wrap items-end gap-4 p-4">
        <div>
            <label for="negeri" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Negeri</label>
            <select name="negeri" id="negeri" class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                <option value="">Semua Negeri</option>
                @foreach($negeriList as $key => $label)
                    <option value="{{ $key }}" {{ $filterNegeri === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="jenis_bangunan" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Jenis Premis</label>
            <select name="jenis_bangunan" id="jenis_bangunan" class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                <option value="">Semua Jenis</option>
                @foreach($jenisList as $key => $label)
                    <option value="{{ $key }}" {{ $filterJenis === $key ? 'selected' : '' }}>{{ $label ?: $key }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="glass-btn-primary rounded-xl px-4 py-2 text-sm font-medium">Tapis</button>
    </form>

    {{-- KPI cards (admin only) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card glass-kpi-card p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/50 ring-1 ring-white/70">
                    <svg class="h-5 w-5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($totalPremises) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Jumlah Premis</p>
        </div>
        <div class="glass-card glass-kpi-card p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50/80 ring-1 ring-amber-100/80">
                    <svg class="h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($amaranLapanBulan) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Amaran 8 Bulan</p>
            <p class="mt-1.5 text-xs text-amber-600">Kontrak perlu penyediaan JRP</p>
        </div>
        <div class="glass-card glass-kpi-card p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/50 ring-1 ring-white/70">
                    <svg class="h-5 w-5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-2xl font-bold leading-tight text-slate-900 lg:text-3xl">{{ number_format($tamatTempoh) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Tamat Tempoh</p>
        </div>
        <div class="glass-card glass-kpi-card p-4 lg:p-5">
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50/80 ring-1 ring-emerald-100/80">
                    <svg class="h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <p class="text-lg font-bold leading-tight text-slate-900">RM {{ number_format($jumlahKutipanBulanan, 0) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">Jumlah Kutipan Bulanan</p>
            <p class="mt-1.5 text-xs text-emerald-600">Kontrak aktif yang belum tamat tempoh</p>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass-card p-5">
            <div class="mb-4">
                <h3 class="font-semibold text-slate-900">Status Kontrak</h3>
                <p class="text-xs text-slate-400">Distribution by status</p>
            </div>
            <div class="h-64">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>
        <div class="glass-card p-5">
            <div class="mb-4">
                <h3 class="font-semibold text-slate-900">Premis Mengikut Negeri</h3>
                <p class="text-xs text-slate-400">Bilangan premis per negeri</p>
            </div>
            <div class="h-64">
                <canvas id="chartNegeri"></canvas>
            </div>
        </div>
    </div>

    <div class="glass-card p-5">
        <div class="mb-4">
            <h3 class="font-semibold text-slate-900">Status Aliran Kerja (Peringkat Proses)</h3>
            <p class="text-xs text-slate-400">Distribution by process stage</p>
        </div>
        <div class="h-64">
            <canvas id="chartPeringkat"></canvas>
        </div>
    </div>

    {{-- Alert list (8 bulan) --}}
    <div id="kontrak" class="glass-card overflow-hidden">
        <div class="glass-divider-soft flex items-center justify-between border-b px-5 py-4">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h3 class="font-semibold text-gray-900">Peringatan 8 Bulan – Tindakan Segera</h3>
                @if($alertList->isNotEmpty())
                <span class="bg-amber-100 text-amber-700 text-xs font-bold px-1.5 py-0.5 rounded-md">{{ $alertList->count() }}</span>
                @endif
            </div>
            <p class="text-xs text-gray-400">Kontrak dengan baki &le; 240 hari</p>
        </div>
        <div class="divide-y divide-slate-200/50">
            @if($alertList->isEmpty())
                <div class="py-10 text-center text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm font-medium text-emerald-600">Tiada kontrak dalam zon peringatan</p>
                    <p class="text-xs mt-1">Semua kontrak mempunyai baki lebih 8 bulan</p>
                </div>
            @else
                @foreach($alertList as $c)
                <a href="{{ auth()->user()->isAdminHq() ? route('status-permohonan.review', $c) : route('status-permohonan.index') }}" class="glass-row-hover flex items-center gap-3 px-5 py-3.5 transition-colors">
                    <div class="w-2 h-2 rounded-full flex-shrink-0 bg-amber-500"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $c->premise?->nama_ptj }}</p>
                        <p class="text-xs text-gray-400">{{ $c->tarikh_tamat?->format('d MMM yyyy') }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs font-semibold text-amber-700">{{ $c->baki_hari }} hari</p>
                    </div>
                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Dalam tempoh</span>
                </a>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Recent contracts --}}
    <div class="glass-card">
        <div class="glass-divider-soft flex items-center justify-between border-b px-5 py-4">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                <h3 class="font-semibold text-slate-900">Senarai Kontrak Terkini</h3>
            </div>
            <a href="{{ route('status-permohonan.index') }}" class="text-xs font-medium text-slate-600 hover:text-slate-800">Lihat semua</a>
        </div>
        <div class="divide-y divide-slate-200/50">
            @if($recentContracts->isEmpty())
                <p class="py-10 text-center text-gray-400 text-sm">Tiada rekod.</p>
            @else
                @foreach($recentContracts as $c)
                <a href="{{ auth()->user()->isAdminHq() ? route('status-permohonan.review', $c) : route('status-permohonan.index') }}" class="glass-row-hover flex items-start gap-3 px-5 py-3.5 transition-colors">
                    @php $sc = match($c->status_aktif) { 'aktif' => 'bg-emerald-50 text-emerald-700', 'tamat_tempoh' => 'bg-gray-100 text-gray-700', 'dalam_proses' => 'bg-amber-50 text-amber-700', default => 'bg-gray-100 text-gray-600' }; @endphp
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 {{ $sc }} text-xs font-bold">{{ substr($c->premise?->nama_ptj ?? '?', 0, 1) }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $c->premise?->nama_ptj }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $c->premise?->jenis_bangunan ?? '–' }}</p>
                        <p class="text-xs text-gray-300 mt-0.5">{{ $c->tarikh_mula?->format('d M Y') }} – {{ $c->tarikh_tamat?->format('d M Y') }}</p>
                    </div>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sc }}">{{ ucfirst(str_replace('_', ' ', $c->status_aktif)) }}</span>
                </a>
                @endforeach
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    const statusData = @json($statusCounts);
    const statusCtx = document.getElementById('chartStatus');
    if (statusCtx && Object.keys(statusData).length) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData).map(s => s.replace(/_/g, ' ')),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: ['#10b981', '#6b7280', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                animation: { animateRotate: true, animateScale: true }
            }
        });
    }

    const negeriData = @json($byNegeri);
    const negeriCtx = document.getElementById('chartNegeri');
    if (negeriCtx && negeriData.length) {
        new Chart(negeriCtx, {
            type: 'bar',
            data: {
                labels: negeriData.map(n => n.negeri),
                datasets: [{
                    label: 'Bilangan Premis',
                    data: negeriData.map(n => n.jumlah),
                    backgroundColor: 'rgba(30, 58, 95, 0.75)',
                    borderColor: 'rgb(30, 58, 95)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                animation: { duration: 800 }
            }
        });
    }

    const peringkatData = @json($peringkatCounts);
    const peringkatCtx = document.getElementById('chartPeringkat');
    if (peringkatCtx && peringkatData.length) {
        new Chart(peringkatCtx, {
            type: 'doughnut',
            data: {
                labels: peringkatData.map(p => p.peringkat_proses),
                datasets: [{
                    data: peringkatData.map(p => p.jumlah),
                    backgroundColor: ['#1e3a5f', '#475569', '#64748b', '#94a3b8', '#cbd5e1', '#e2e8f0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
                animation: { animateRotate: true }
            }
        });
    }
})();
</script>
@endpush
@endsection