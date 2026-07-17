@extends('layouts.app')

@php
    $p = $contract->premise;
@endphp

@section('title', 'Butiran Kontrak Sewaan')
@section('header_title', 'Butiran Kontrak Sewaan')
@section('header_subtitle', $p?->nama_ptj ?? 'Maklumat kontrak sewaan')

@section('content')
<div class="space-y-6">
    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Maklumat Premis</h2>
            <p class="mt-0.5 text-xs text-slate-500">Butiran permohonan yang telah disahkan oleh HQ.</p>
        </div>

        <div class="space-y-6 px-6 py-5">
            @if($contract->isFollowUpApplication())
                @include('partials.follow-up-comparison', ['contract' => $contract])
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Negeri</p>
                    <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $contract->displayAdminNegeriName() }}</p>
                </div>
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Tarikh Disahkan HQ</p>
                    <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $contract->hq_approved_at?->format('d/m/Y') ?? '–' }}</p>
                </div>
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</p>
                    <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $contract->workflowLabel() }}</p>
                </div>
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Tempoh Kontrak</p>
                    <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $contract->contractPeriodLabel() }}</p>
                </div>
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Baki Tempoh</p>
                    <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $contract->daysUntilContractEndLabel() }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Kategori Permohonan</p>
                    <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ \App\Support\ApplicationCategories::label($contract->kategori_permohonan) }}</p>
                </div>
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Negeri</p>
                    <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $p?->negeri ?? '–' }}</p>
                </div>
            </div>

            <div class="border-t border-slate-200/60 pt-6">
                <p class="mb-3 text-sm font-semibold text-gray-700">Maklumat Premis</p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Nama Premis</p>
                        <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $p?->nama_ptj ?? '–' }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Jenis Bangunan</p>
                        <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ \App\Support\BuildingTypes::all()[$p?->jenis_bangunan] ?? ($p?->jenis_bangunan ?? '–') }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Kadar Sewa (RM)</p>
                        <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ number_format((float) ($p?->kadar_sewa ?? $contract->kadar_sewa_bulanan), 2) }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Keluasan (mps)</p>
                        <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $contract->keluasan_mp ? number_format((float) $contract->keluasan_mp, 2) : '–' }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Keluasan (kps)</p>
                        <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $contract->keluasan_mp ? number_format((float) $contract->keluasan_mp * 10.7639, 2) : '–' }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Sah Sehingga</p>
                        <p class="glass-field-static px-3 py-2 text-sm text-slate-800">{{ $contract->sah_sehingga?->format('d/m/Y') ?? '–' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Alamat Penuh Premis</p>
                        <p class="glass-field-static whitespace-pre-wrap px-3 py-2 text-sm text-slate-800">{{ $p?->alamat_penuh ?? '–' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Langkah Tindakan Pentadbir Negeri</h2>
            <p class="mt-0.5 text-xs text-slate-500">Status kemajuan langkah tindakan yang telah dilengkapkan.</p>
        </div>

        <div class="px-6 py-4">
            @include('status-permohonan.partials.proceed-readonly', [
                'stepPanels' => $stepPanels,
                'completedCount' => $completedCount,
                'totalActiveSteps' => $totalActiveSteps,
                'compact' => true,
            ])
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Pengesahan HQ</h2>
            <p class="mt-0.5 text-xs text-slate-500">Checklist JRP yang telah direkodkan oleh HQ.</p>
        </div>

        <div class="px-6 py-5">
            @include('status-permohonan.partials.hq-jrp-checklist-readonly', [
                'contract' => $contract,
            ])
            @if(! is_array($contract->hq_jrp_checklist) || $contract->hq_jrp_checklist === [])
                <p class="text-sm text-slate-500">Tiada checklist JRP direkodkan untuk permohonan ini.</p>
            @endif
        </div>
    </section>
</div>
@endsection
