@extends('layouts.app')

@php
    $p = $contract->premise;
    $steps = $contract->draftAgreementProgressSteps();
@endphp

@section('title', 'Butiran Kontrak Sewaan')
@section('header_title', 'Butiran Kontrak Sewaan')
@section('header_subtitle', $p?->nama_ptj ?? 'Maklumat kontrak sewaan')

@section('content')
<div class="space-y-4">
    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Maklumat Asas</h2>
            <p class="mt-0.5 text-xs text-slate-500">Ringkasan kategori, negeri dan pihak terlibat.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 px-4 py-3 text-sm md:grid-cols-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Kategori</p>
                <p class="mt-0.5 text-sm text-slate-800">{{ \App\Support\ApplicationCategories::label($contract->kategori_permohonan) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Negeri</p>
                <p class="mt-0.5 text-sm text-slate-800">{{ $p?->negeri ?? '–' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Nama Pemohon</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $contract->displayAdminNegeriName() }}</p>
            </div>
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Maklumat Premis & Pemilik</h2>
            <p class="mt-0.5 text-xs text-slate-500">Butiran premis, sewa dan pemilik.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 px-4 py-3 text-sm md:grid-cols-2">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Nama Premis</p>
                <p class="mt-0.5 text-slate-800">{{ $p?->nama_ptj ?? '–' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Jenis Bangunan</p>
                <p class="mt-0.5 text-slate-800">{{ \App\Support\BuildingTypes::all()[$p?->jenis_bangunan] ?? ($p?->jenis_bangunan ?? '–') }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Kadar Sewa (RM)</p>
                <p class="mt-0.5 text-slate-800">{{ number_format((float) ($p?->kadar_sewa ?? $contract->kadar_sewa_bulanan), 2) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Keluasan (mps)</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->keluasan_mp ? number_format((float) $contract->keluasan_mp, 2) : '–' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Keluasan (kps)</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->keluasan_mp ? number_format((float) $contract->keluasan_mp * 10.7639, 2) : '–' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tarikh Mula</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->tarikh_mula_tawaran?->format('d/m/Y') ?? '–' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tarikh Akhir Tempoh Tawaran Penyewaan</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->sah_sehingga?->format('d/m/Y') ?? '–' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Nama Pemilik</p>
                <p class="mt-0.5 text-slate-800">{{ $p?->nama_pemilik ?? '–' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Alamat Penuh Premis</p>
                <p class="mt-0.5 whitespace-pre-wrap text-slate-800">{{ $p?->alamat_penuh ?? '–' }}</p>
            </div>
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Maklumat Kontrak</h2>
            <p class="mt-0.5 text-xs text-slate-500">Status dan tempoh kontrak sewaan yang telah disahkan.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 px-4 py-3 text-sm md:grid-cols-2 lg:grid-cols-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tarikh Disahkan Ibu Pejabat</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->hq_approved_at?->format('d/m/Y') ?? '–' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</p>
                <p class="mt-0.5">
                    <span class="{{ $contract->applicationStatusBadgeClass() }} inline-flex">
                        {{ $contract->applicationStatusLabel(auth()->user()) }}
                    </span>
                </p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Workflow</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->workflowLabel() }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tempoh Kontrak</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->contractPeriodLabel() }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Baki Tempoh</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->daysUntilContractEndLabel() }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tarikh Mula</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->usesPlaceholderContractDates() ? '–' : ($contract->tarikh_mula?->format('d/m/Y') ?? '–') }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tarikh Tamat</p>
                <p class="mt-0.5 text-slate-800">{{ $contract->usesPlaceholderContractDates() ? '–' : ($contract->tarikh_tamat?->format('d/m/Y') ?? '–') }}</p>
            </div>
        </div>
    </section>

    <section class="glass-card">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Kemajuan Permohonan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Ringkasan peringkat permohonan sehingga kontrak sewaan.</p>
        </div>
        <div class="overflow-visible px-4 pb-6 pt-4">
            @include('status-permohonan.partials.progress-stepper', ['steps' => $steps])
        </div>
    </section>

    @if($contract->isFollowUpApplication())
        <section class="glass-card overflow-hidden">
            <div class="glass-divider-soft border-b px-4 py-3">
                <h2 class="text-base font-semibold text-slate-900">Perbandingan Permohonan</h2>
                <p class="mt-0.5 text-xs text-slate-500">Bandingkan maklumat kontrak lama dengan permohonan baharu.</p>
            </div>
            <div class="px-4 py-3">
                @include('partials.follow-up-comparison', ['contract' => $contract])
            </div>
        </section>
    @endif

    @if(filled($contract->remark) && ! $contract->isFollowUpApplication())
        <section class="glass-card overflow-hidden">
            <div class="glass-divider-soft border-b px-4 py-3">
                <h2 class="text-base font-semibold text-slate-900">Remark</h2>
                <p class="mt-0.5 text-xs text-slate-500">Catatan tambahan berkaitan permohonan.</p>
            </div>
            <div class="px-4 py-3">
                @include('partials.readonly-field', [
                    'label' => 'Remark',
                    'value' => $contract->remark,
                    'multiline' => true,
                ])
            </div>
        </section>
    @endif

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Langkah Tindakan Pegawai Negeri</h2>
            <p class="mt-0.5 text-xs text-slate-500">Status kemajuan langkah tindakan yang telah dilengkapkan.</p>
        </div>
        <div class="px-4 py-3">
            @include('status-permohonan.partials.proceed-readonly', [
                'stepPanels' => $stepPanels,
                'completedCount' => $completedCount,
                'totalActiveSteps' => $totalActiveSteps,
                'compact' => true,
            ])
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Pengesahan Ibu Pejabat</h2>
            <p class="mt-0.5 text-xs text-slate-500">Checklist JRP yang telah direkodkan oleh Ibu Pejabat.</p>
        </div>
        <div class="px-4 py-3">
            @include('status-permohonan.partials.hq-jrp-checklist-readonly', [
                'contract' => $contract,
            ])
            @if(! is_array($contract->hq_jrp_checklist) || $contract->hq_jrp_checklist === [])
                <p class="mt-3 text-sm text-slate-500">Tiada checklist JRP direkodkan untuk permohonan ini.</p>
            @endif
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Status Draf Perjanjian</h2>
            <p class="mt-0.5 text-xs text-slate-500">Rekod peringkat penyediaan dan semakan draf perjanjian.</p>
        </div>
        <div class="space-y-3 px-4 py-3 text-sm">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status Semasa</p>
                <span class="{{ $contract->applicationStatusBadgeClass() }} mt-1 inline-flex">
                    {{ $contract->applicationStatusLabel(auth()->user()) }}
                </span>
            </div>
            @if((int) $contract->semakan_count > 0)
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Bilangan Semakan PUU</p>
                    <p class="mt-0.5 text-slate-800">{{ $contract->semakanLabel() }}</p>
                </div>
            @endif
            @include('status-permohonan.partials.draft-document-history', ['contract' => $contract])
            <p class="text-sm text-slate-600">
                Draf perjanjian telah diluluskan dan tindakan Negeri serta pengesahan Ibu Pejabat telah selesai.
            </p>
            <div class="glass-subtle rounded-xl border border-slate-200/60 p-4">
                <p class="text-sm font-semibold text-slate-900">Tindakan Negeri</p>
                <p class="mt-0.5 text-xs text-slate-500">Pengesahan yang telah ditandakan.</p>
                <div class="mt-3">
                    @include('status-permohonan.partials.negeri-draft-acknowledgements-readonly', [
                        'contract' => $contract,
                        'acknowledgementsCompleted' => true,
                    ])
                </div>
            </div>
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Pengesahan & Tandatangan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pengesahan penerimaan dan tandatangan perjanjian oleh Ibu Pejabat.</p>
        </div>
        <div class="px-4 py-3">
            <p class="text-sm text-emerald-700">Perjanjian telah disahkan dan dihantar kepada Negeri untuk Mati Setem.</p>
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Mati Setem</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pengesahan mati setem dan edaran dokumen oleh Negeri.</p>
        </div>
        <div class="px-4 py-3">
            @include('status-permohonan.partials.negeri-mati-setem-readonly', [
                'contract' => $contract,
                'acknowledgementsCompleted' => true,
            ])
        </div>
    </section>

    <section class="glass-card overflow-hidden">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Selesai</h2>
            <p class="mt-0.5 text-xs text-slate-500">Permohonan selesai dan dimasukkan ke dalam Senarai Kontrak Sewaan.</p>
        </div>
        <div class="px-4 py-3">
            <p class="text-sm text-emerald-700">Permohonan telah selesai dan dimasukkan ke dalam Senarai Kontrak Sewaan.</p>
        </div>
    </section>
</div>
@endsection
