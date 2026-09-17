@extends('layouts.app')

@php
    $p = $contract->premise;
    $steps = $contract->draftAgreementProgressSteps();
    $currentStepIndex = $contract->draftAgreementCurrentStepIndex();
@endphp

@section('title', 'Semak Permohonan')
@section('header_title', 'Semak Permohonan')
@section('header_subtitle', $p?->nama_ptj ?? 'Kandungan permohonan sewaan')

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

    <section class="glass-card">
        <div class="glass-divider-soft border-b px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Kemajuan Permohonan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Klik pada mana-mana langkah untuk melihat butiran bahagian tersebut.</p>
        </div>
        <div class="overflow-visible px-4 pb-6 pt-4">
            @include('status-permohonan.partials.progress-stepper', ['steps' => $steps])
        </div>
    </section>

    <div id="step-panels" class="scroll-mt-24">
        {{-- Langkah 1: Permohonan Baru --}}
        <div data-step-panel="0" class="space-y-4 {{ $currentStepIndex === 0 ? '' : 'hidden' }}">
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
        </div>

        {{-- Langkah 2: Semakan Ibu Pejabat --}}
        <div data-step-panel="1" class="space-y-4 {{ $currentStepIndex === 1 ? '' : 'hidden' }}">
            @if($contract->isPendingHqReview())
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Pengesahan Ibu Pejabat</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            @if($contract->hasPendingWithdrawalRequest())
                                Permohonan ini menunggu keputusan tarik semula daripada negeri.
                            @else
                                Lengkapkan checklist JRP sebelum meneruskan permohonan dengan draf perjanjian.
                            @endif
                        </p>
                    </div>

                    @if($contract->hasPendingWithdrawalRequest())
                        <div class="space-y-3 px-4 py-3">
                            <div class="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Permohonan Tarik Semula</p>
                                <p class="mt-1 text-sm text-amber-900">{{ $contract->applicationStatusLabel(auth()->user()) }}</p>
                                <div class="mt-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Alasan</p>
                                    <p class="mt-0.5 whitespace-pre-wrap text-sm text-slate-800">{{ $contract->withdrawal_reason ?: '–' }}</p>
                                </div>
                                @if($contract->withdrawalRequestedBy?->name)
                                    <p class="mt-2 text-xs text-slate-600">Dimohon oleh: {{ $contract->withdrawalRequestedBy->name }}</p>
                                @endif
                            </div>

                            @include('status-permohonan.partials.hq-jrp-checklist-readonly', ['contract' => $contract])

                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <form
                                    id="approve-withdrawal-form-review"
                                    method="POST"
                                    action="{{ route('status-permohonan.resolve-withdrawal', $contract) }}"
                                >
                                    @csrf
                                    <input type="hidden" name="decision" value="approve">
                                    <button
                                        type="button"
                                        class="status-confirm-trigger glass-btn-success rounded-lg px-4 py-2 text-sm font-medium"
                                        data-confirm-title="Luluskan Permohonan Tarik Semula"
                                        data-confirm-message="Permohonan akan dikembalikan kepada pentadbir negeri untuk dikemaskini semula. Anda pasti mahu meluluskan permohonan tarik semula ini?"
                                        data-confirm-withdrawal-reason="{{ $contract->withdrawal_reason }}"
                                        data-confirm-form="approve-withdrawal-form-review"
                                        data-confirm-button="Ya, Luluskan"
                                        data-confirm-tone="success"
                                    >
                                        Luluskan Tarik Semula
                                    </button>
                                </form>
                                <form
                                    id="reject-withdrawal-form-review"
                                    method="POST"
                                    action="{{ route('status-permohonan.resolve-withdrawal', $contract) }}"
                                >
                                    @csrf
                                    <input type="hidden" name="decision" value="reject">
                                    <button
                                        type="button"
                                        class="status-confirm-trigger rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 transition-colors hover:bg-red-50"
                                        data-confirm-title="Tolak Permohonan Tarik Semula"
                                        data-confirm-message="Permohonan akan kekal dalam senarai semakan Ibu Pejabat. Anda pasti mahu menolak permohonan tarik semula ini?"
                                        data-confirm-withdrawal-reason="{{ $contract->withdrawal_reason }}"
                                        data-confirm-form="reject-withdrawal-form-review"
                                        data-confirm-button="Ya, Tolak"
                                        data-confirm-tone="danger"
                                    >
                                        Tolak Tarik Semula
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <form
                            id="approve-hq-form-review"
                            method="POST"
                            action="{{ route('status-permohonan.approve', $contract) }}"
                            class="space-y-3 px-4 py-3"
                        >
                            @csrf

                            @include('status-permohonan.partials.hq-jrp-checklist', ['contract' => $contract])

                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <button
                                    type="button"
                                    id="approve-hq-review-trigger"
                                    class="status-confirm-trigger glass-btn-success rounded-lg px-4 py-2 text-sm font-medium"
                                    data-confirm-title="Teruskan Dengan Draf Perjanjian"
                                    data-confirm-message="Anda pasti mahu meneruskan permohonan ini dengan draf perjanjian selepas semakan?"
                                    data-confirm-form="approve-hq-form-review"
                                    data-confirm-button="Ya, Teruskan"
                                    data-confirm-tone="success"
                                >
                                    Teruskan Dengan Draf Perjanjian
                                </button>
                            </div>
                        </form>
                    @endif
                </section>
            @else
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Langkah Tindakan Pentadbir</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Rekod checklist JRP yang telah dilengkapkan.</p>
                    </div>
                    <div class="px-4 py-3">
                        @include('status-permohonan.partials.hq-jrp-checklist-readonly', ['contract' => $contract])
                    </div>
                </section>
            @endif
        </div>

        {{-- Langkah 3: Penyediaan Draf --}}
        <div data-step-panel="2" class="space-y-4 {{ $currentStepIndex === 2 ? '' : 'hidden' }}">
            <section class="glass-card overflow-hidden">
                <div class="glass-divider-soft border-b px-4 py-3">
                    <h2 class="text-base font-semibold text-slate-900">Status Draf Perjanjian</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Status semasa permohonan dalam peringkat penyediaan draf perjanjian.</p>
                </div>
                <div class="space-y-4 px-4 py-3">
                    @if($currentStepIndex >= 2)
                        @if($currentStepIndex === 2)
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status Semasa</p>
                            <span class="{{ $contract->applicationStatusBadgeClass() }} mt-1 inline-flex">
                                {{ $contract->applicationStatusLabel(auth()->user()) }}
                            </span>
                        @endif

                        @include('status-permohonan.partials.draft-document-history', ['contract' => $contract])

                        @if(auth()->user()->isAdminNegeri() && $contract->canUploadDraftAgreement())
                            <form
                                method="POST"
                                action="{{ route('status-permohonan.upload-draft', $contract) }}"
                                enctype="multipart/form-data"
                                class="space-y-3 border-t border-slate-200 pt-4"
                            >
                                @csrf
                                <div>
                                    <label for="draft-document" class="mb-1 block text-sm font-medium text-slate-700">
                                        Muat Naik Draf Perjanjian (PDF) *
                                    </label>
                                    <p class="mb-2 text-xs text-slate-500">
                                        @if((int) $contract->semakan_count === 0)
                                            Muat naik pertama akan dihantar sebagai Semakan 1.
                                        @else
                                            Muat naik seterusnya akan dihantar sebagai Semakan {{ (int) $contract->semakan_count + 1 }}.
                                        @endif
                                        Hanya PDF, maksimum {{ \App\Support\UploadLimits::humanEffectiveLimit() }}.
                                    </p>
                                    <input
                                        type="file"
                                        name="document"
                                        id="draft-document"
                                        required
                                        accept=".pdf,application/pdf"
                                        class="glass-input w-full rounded-xl px-3 py-2 text-sm"
                                    >
                                    @error('document')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="glass-btn-primary rounded-lg px-4 py-2 text-sm font-medium">
                                        Hantar untuk Semakan PUU
                                    </button>
                                </div>
                            </form>
                        @elseif($currentStepIndex > 2)
                            <p class="text-sm text-slate-600">Penyediaan draf perjanjian awal telah selesai.</p>
                        @endif
                    @else
                        <p class="text-sm text-slate-500">Peringkat ini belum bermula.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- Langkah 4: Dalam tindakan PUU --}}
        <div data-step-panel="3" class="space-y-4 {{ $currentStepIndex === 3 ? '' : 'hidden' }}">
            <section class="glass-card overflow-hidden">
                <div class="glass-divider-soft border-b px-4 py-3">
                    <h2 class="text-base font-semibold text-slate-900">Dalam tindakan PUU</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Status semakan draf perjanjian oleh PUU.</p>
                </div>
                <div class="space-y-4 px-4 py-3">
                    @if($currentStepIndex >= 3)
                        @if($currentStepIndex === 3)
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status Semasa</p>
                            <span class="{{ $contract->applicationStatusBadgeClass() }} mt-1 inline-flex">
                                {{ $contract->applicationStatusLabel(auth()->user()) }}
                            </span>
                        @endif

                        @include('status-permohonan.partials.draft-document-history', ['contract' => $contract])

                        @if(auth()->user()->isAdminHq() && $contract->isSemakanPuu())
                            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 pt-4">
                                <form
                                    id="reject-puu-form"
                                    method="POST"
                                    action="{{ route('status-permohonan.reject-puu', $contract) }}"
                                    class="inline"
                                >
                                    @csrf
                                    <button
                                        type="button"
                                        class="status-confirm-trigger glass-btn-secondary rounded-lg px-4 py-2 text-sm font-medium"
                                        data-confirm-title="Semak Semula Semakan PUU"
                                        data-confirm-message="Draf akan dikembalikan kepada Negeri untuk muat naik semula. Teruskan?"
                                        data-confirm-form="reject-puu-form"
                                        data-confirm-button="Ya, Semak Semula"
                                        data-confirm-tone="danger"
                                    >
                                        Semak Semula
                                    </button>
                                </form>
                                <form
                                    id="approve-puu-form"
                                    method="POST"
                                    action="{{ route('status-permohonan.approve-puu', $contract) }}"
                                    class="inline"
                                >
                                    @csrf
                                    <button
                                        type="button"
                                        class="status-confirm-trigger glass-btn-primary rounded-lg px-4 py-2 text-sm font-medium"
                                        data-confirm-title="Luluskan Semakan PUU"
                                        data-confirm-message="Semakan PUU diluluskan dan permohonan akan diteruskan ke Draf Lulus (Selesai). Teruskan?"
                                        data-confirm-form="approve-puu-form"
                                        data-confirm-button="Ya, Luluskan"
                                        data-confirm-tone="success"
                                    >
                                        Luluskan
                                    </button>
                                </form>
                            </div>
                        @elseif($currentStepIndex > 3)
                            <p class="text-sm text-slate-600">Semakan PUU untuk pusingan semasa telah selesai.</p>
                        @endif
                    @else
                        <p class="text-sm text-slate-500">Peringkat ini belum bermula.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- Langkah 5: Pindaan Berdasarkan PUU --}}
        <div data-step-panel="4" class="space-y-4 {{ $currentStepIndex === 4 ? '' : 'hidden' }}">
            <section class="glass-card overflow-hidden">
                <div class="glass-divider-soft border-b px-4 py-3">
                    <h2 class="text-base font-semibold text-slate-900">Pindaan Berdasarkan PUU</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Pindaan draf perjanjian mengikut ulasan dan keputusan PUU.</p>
                </div>
                <div class="space-y-4 px-4 py-3">
                    @if($currentStepIndex >= 4)
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status Semasa</p>
                        <span class="{{ $contract->applicationStatusBadgeClass() }} mt-1 inline-flex">
                            {{ $contract->applicationStatusLabel(auth()->user()) }}
                        </span>

                        @include('status-permohonan.partials.draft-document-history', ['contract' => $contract])

                        @if($currentStepIndex === 4)
                            <p class="text-sm text-slate-600">Pindaan draf perjanjian mengikut ulasan dan keputusan PUU.</p>
                        @elseif($currentStepIndex > 4)
                            <p class="text-sm text-slate-600">Pindaan draf berdasarkan PUU telah selesai.</p>
                        @endif
                    @else
                        <p class="text-sm text-slate-500">Peringkat ini belum bermula.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- Langkah 6: Draf Lulus --}}
        <div data-step-panel="5" class="space-y-4 {{ $currentStepIndex === 5 ? '' : 'hidden' }}">
            @if(auth()->user()->isAdminNegeri() && $contract->isDrafPerjanjianLulus())
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Tindakan Negeri</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Sahkan penerimaan draf akhir dan kembalikan kepada Ibu Pejabat.</p>
                    </div>
                    <form
                        id="negeri-draft-acknowledgement-form"
                        method="POST"
                        action="{{ route('status-permohonan.return-hq', $contract) }}"
                        class="space-y-4 px-4 py-3"
                        data-autosave-url="{{ route('status-permohonan.negeri-acknowledgements-autosave', $contract) }}"
                    >
                        @csrf
                        @php
                            $savedAcknowledgements = is_array($contract->negeri_draft_acknowledgements)
                                ? $contract->negeri_draft_acknowledgements
                                : [];
                        @endphp
                        <div class="space-y-3">
                            @foreach(\App\Support\NegeriDraftAcknowledgements::labels() as $acknowledgementKey => $acknowledgementLabel)
                                <label class="flex items-start gap-3 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="{{ $acknowledgementKey }}"
                                        value="1"
                                        class="negeri-draft-ack mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        data-ack-key="{{ $acknowledgementKey }}"
                                        @checked(filter_var(old($acknowledgementKey, $savedAcknowledgements[$acknowledgementKey] ?? false), FILTER_VALIDATE_BOOLEAN))
                                    >
                                    <span>{{ $acknowledgementLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('draf_akhir_diterima_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('dokumen_perjanjian_disediakan_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('dokumen_perjanjian_ditandatangani_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('dokumen_asal_dihantar_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <p id="negeri-draft-save-status" class="mr-auto hidden text-xs text-slate-500" aria-live="polite"></p>
                            <button
                                type="button"
                                id="negeri-draft-save-button"
                                class="glass-btn-secondary rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-60"
                            >
                                Simpan
                            </button>
                            <button
                                type="submit"
                                class="glass-btn-primary rounded-lg px-4 py-2 text-sm font-medium"
                            >
                                Hantar Semula ke Ibu Pejabat
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Draf Lulus</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Semakan PUU diluluskan — status permohonan Dokumen Perjanjian dikembalikan ke Cawangan Pembangunan AADK.</p>
                    </div>
                    <div class="space-y-4 px-4 py-3">
                        <p class="text-sm text-slate-600">
                            @if($currentStepIndex > 5)
                                Draf perjanjian telah diluluskan dan tindakan Negeri telah selesai.
                            @elseif($currentStepIndex === 5)
                                Menunggu tindakan Pegawai Negeri untuk mengesahkan penerimaan draf akhir.
                            @else
                                Peringkat ini belum bermula.
                            @endif
                        </p>
                        @if($currentStepIndex > 5)
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
                        @endif
                    </div>
                </section>
            @endif
        </div>

        {{-- Langkah 7: Pengesahan & Tandatangan --}}
        <div data-step-panel="6" class="space-y-4 {{ $currentStepIndex === 6 ? '' : 'hidden' }}">
            @if(auth()->user()->isAdminHq() && $contract->isDrafDikembalikanHq())
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Tindakan Ibu Pejabat</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Sahkan kesemua langkah berikut sebelum menghantar semula kepada Negeri untuk Mati Setem.</p>
                    </div>
                    <form
                        id="hq-draft-acknowledgement-form"
                        method="POST"
                        action="{{ route('status-permohonan.finalize', $contract) }}"
                        class="space-y-4 px-4 py-3"
                        data-autosave-url="{{ route('status-permohonan.hq-acknowledgements-autosave', $contract) }}"
                    >
                        @csrf
                        @php
                            $savedHqAcknowledgements = is_array($contract->hq_draft_acknowledgements)
                                ? $contract->hq_draft_acknowledgements
                                : [];
                        @endphp
                        <div class="space-y-3">
                            @foreach(\App\Support\HqDraftAcknowledgements::labels() as $acknowledgementKey => $acknowledgementLabel)
                                <label class="flex items-start gap-3 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="{{ $acknowledgementKey }}"
                                        value="1"
                                        class="hq-draft-ack mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        data-ack-key="{{ $acknowledgementKey }}"
                                        @checked(filter_var(old($acknowledgementKey, $savedHqAcknowledgements[$acknowledgementKey] ?? false), FILTER_VALIDATE_BOOLEAN))
                                    >
                                    <span>{{ $acknowledgementLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('terima_dokumen_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('perjanjian_ditandatangani_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('salinan_promis_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <p id="hq-draft-save-status" class="mr-auto hidden text-xs text-slate-500" aria-live="polite"></p>
                            <button
                                type="button"
                                id="hq-draft-save-button"
                                class="glass-btn-secondary rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-60"
                            >
                                Simpan
                            </button>
                            <button
                                type="submit"
                                class="glass-btn-success rounded-lg px-4 py-2 text-sm font-medium"
                            >
                                Hantar ke Negeri
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Pengesahan & Tandatangan</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Pengesahan penerimaan dan tandatangan perjanjian oleh Ibu Pejabat.</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-sm text-slate-600">
                            @if($currentStepIndex > 6)
                                Perjanjian telah disahkan dan dihantar kepada Negeri untuk Mati Setem.
                            @elseif($currentStepIndex === 6)
                                Menunggu tindakan Ibu Pejabat untuk pengesahan dan tandatangan perjanjian.
                            @else
                                Peringkat ini belum bermula.
                            @endif
                        </p>
                    </div>
                </section>
            @endif
        </div>

        {{-- Langkah 8: Mati Setem --}}
        <div data-step-panel="7" class="space-y-4 {{ $currentStepIndex === 7 ? '' : 'hidden' }}">
            @if(auth()->user()->isAdminNegeri() && $contract->isMatiSetem())
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Tindakan Negeri</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Sahkan mati setem dan edaran dokumen sebelum menyelesaikan permohonan.</p>
                    </div>
                    <form
                        id="negeri-mati-setem-form"
                        method="POST"
                        action="{{ route('status-permohonan.complete-mati-setem', $contract) }}"
                        class="space-y-4 px-4 py-3"
                        data-autosave-url="{{ route('status-permohonan.mati-setem-autosave', $contract) }}"
                    >
                        @csrf
                        @php
                            $savedMatiSetemAcknowledgements = is_array($contract->negeri_mati_setem_acknowledgements)
                                ? $contract->negeri_mati_setem_acknowledgements
                                : [];
                        @endphp
                        <div class="space-y-3">
                            @foreach(\App\Support\NegeriMatiSetemAcknowledgements::labels() as $acknowledgementKey => $acknowledgementLabel)
                                <label class="flex items-start gap-3 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="{{ $acknowledgementKey }}"
                                        value="1"
                                        class="negeri-mati-setem-ack mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        data-ack-key="{{ $acknowledgementKey }}"
                                        @checked(filter_var(old($acknowledgementKey, $savedMatiSetemAcknowledgements[$acknowledgementKey] ?? false), FILTER_VALIDATE_BOOLEAN))
                                    >
                                    <span>{{ $acknowledgementLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('mati_setem_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <p id="negeri-mati-setem-save-status" class="mr-auto hidden text-xs text-slate-500" aria-live="polite"></p>
                            <button
                                type="button"
                                id="negeri-mati-setem-save-button"
                                class="glass-btn-secondary rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-60"
                            >
                                Simpan
                            </button>
                            <button
                                type="submit"
                                class="glass-btn-success rounded-lg px-4 py-2 text-sm font-medium"
                            >
                                Selesai
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Mati Setem</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Dokumen dimatikan setem dan diedarkan kepada pemilik premis.</p>
                    </div>
                    <div class="space-y-4 px-4 py-3">
                        <p class="text-sm text-slate-600">
                            @if($currentStepIndex > 7)
                                Mati setem telah selesai dan permohonan dimasukkan ke dalam Senarai Kontrak Sewaan.
                            @elseif($currentStepIndex === 7)
                                Menunggu tindakan Negeri untuk mati setem.
                            @else
                                Peringkat ini belum bermula.
                            @endif
                        </p>
                        @if($currentStepIndex > 7)
                            <div class="glass-subtle rounded-xl border border-slate-200/60 p-4">
                                @include('status-permohonan.partials.negeri-mati-setem-readonly', [
                                    'contract' => $contract,
                                    'acknowledgementsCompleted' => true,
                                ])
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        {{-- Langkah 9: Selesai --}}
        <div data-step-panel="8" class="space-y-4 {{ $currentStepIndex === 8 ? '' : 'hidden' }}">
            <section class="glass-card overflow-hidden">
                <div class="glass-divider-soft border-b px-4 py-3">
                    <h2 class="text-base font-semibold text-slate-900">Selesai</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Permohonan selesai dan dimasukkan ke dalam Senarai Kontrak Sewaan.</p>
                </div>
                <div class="px-4 py-3">
                    @if($currentStepIndex >= 8)
                        <p class="text-sm text-emerald-700">Permohonan telah selesai dan dimasukkan ke dalam Senarai Kontrak Sewaan.</p>
                    @else
                        <p class="text-sm text-slate-600">Peringkat ini belum bermula. Permohonan akan dimasukkan ke dalam Senarai Kontrak Sewaan selepas Mati Setem selesai.</p>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>

@include('partials.confirm-action-modal')
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('confirm-action-modal');
    const modalTitle = document.getElementById('confirm-action-title');
    const modalMessage = document.getElementById('confirm-action-message');
    const modalDetailsWrap = document.getElementById('confirm-action-details-wrap');
    const modalWithdrawalReasonRow = document.getElementById('confirm-action-withdrawal-reason-row');
    const modalWithdrawalReason = document.getElementById('confirm-action-withdrawal-reason');
    const modalConfirm = document.getElementById('confirm-action-confirm');
    const modalCancel = document.getElementById('confirm-action-cancel');
    const modalIcon = document.getElementById('confirm-action-icon');
    let pendingForm = null;

    const iconTemplates = {
        success: '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
        danger: '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>',
    };

    function closeConfirmModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        pendingForm = null;
        document.body.classList.remove('overflow-hidden');
    }

    function setConfirmDetails(trigger) {
        const withdrawalReason = (trigger.dataset.confirmWithdrawalReason || '').trim();
        const showWithdrawalReason = withdrawalReason !== '';

        if (modalWithdrawalReasonRow) {
            modalWithdrawalReasonRow.classList.toggle('hidden', !showWithdrawalReason);
        }

        if (modalWithdrawalReason) {
            modalWithdrawalReason.textContent = withdrawalReason || '–';
        }

        if (modalDetailsWrap) {
            modalDetailsWrap.classList.toggle('hidden', !showWithdrawalReason);
        }
    }

    document.querySelectorAll('.status-confirm-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const form = document.getElementById(trigger.dataset.confirmForm);
            if (!form || !modal || !modalConfirm || !modalTitle || !modalMessage) return;

            const tone = trigger.dataset.confirmTone || 'success';

            modalTitle.textContent = trigger.dataset.confirmTitle || 'Sahkan Tindakan';
            modalMessage.textContent = trigger.dataset.confirmMessage || '';
            setConfirmDetails(trigger);
            modalConfirm.textContent = trigger.dataset.confirmButton || 'Sahkan';
            modalConfirm.className = 'rounded-xl px-4 py-2 text-sm font-medium';

            if (tone === 'danger') {
                modalConfirm.classList.add('glass-btn-danger');
            } else {
                modalConfirm.classList.add('glass-btn-success');
            }

            if (modalIcon) {
                modalIcon.className = 'mb-4 flex h-11 w-11 items-center justify-center rounded-full';
                if (tone === 'danger') {
                    modalIcon.classList.add('bg-red-50', 'text-red-600');
                } else {
                    modalIcon.classList.add('bg-emerald-50', 'text-emerald-600');
                }
                modalIcon.innerHTML = iconTemplates[tone] || iconTemplates.success;
            }

            pendingForm = form;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            modalConfirm.focus();
        });
    });

    modalCancel?.addEventListener('click', closeConfirmModal);
    modalConfirm?.addEventListener('click', () => {
        pendingForm?.submit();
        closeConfirmModal();
    });
    modal?.querySelectorAll('[data-confirm-dismiss]').forEach((el) => el.addEventListener('click', closeConfirmModal));
})();

(function () {
    const panels = Array.from(document.querySelectorAll('[data-step-panel]'));
    const buttons = Array.from(document.querySelectorAll('[data-step-index]'));
    if (!panels.length) return;

    function activate(index) {
        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.stepPanel !== index);
        });
        buttons.forEach((button) => {
            const circle = button.querySelector('[data-step-circle]');
            const isViewing = button.dataset.stepIndex === index;
            if (circle) {
                circle.classList.toggle('outline', isViewing);
                circle.classList.toggle('outline-2', isViewing);
                circle.classList.toggle('outline-offset-2', isViewing);
                circle.classList.toggle('outline-indigo-400', isViewing);
            }
        });
    }

    buttons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            activate(button.dataset.stepIndex);
        });
    });

    activate(String(@json($currentStepIndex)));
})();
</script>
<script>
(function () {
    const form = document.getElementById('negeri-draft-acknowledgement-form');
    if (!form) {
        return;
    }

    const autosaveUrl = form.dataset.autosaveUrl;
    const status = document.getElementById('negeri-draft-save-status');
    const saveButton = document.getElementById('negeri-draft-save-button');
    let autosaveTimer = null;
    let autosaveInFlight = null;
    let autosavePending = false;
    const AUTOSAVE_DEBOUNCE_MS = 400;

    function showStatus(message) {
        if (!status) {
            return;
        }

        status.textContent = message;
        status.classList.remove('hidden');
    }

    function collectFormData() {
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

        form.querySelectorAll('.negeri-draft-ack').forEach((checkbox) => {
            formData.append(`acknowledgements[${checkbox.dataset.ackKey}]`, checkbox.checked ? '1' : '0');
        });

        return formData;
    }

    async function saveProgress() {
        if (!autosaveUrl) {
            return;
        }

        if (autosaveInFlight) {
            autosavePending = true;
            return autosaveInFlight;
        }

        if (saveButton) {
            saveButton.disabled = true;
        }

        autosaveInFlight = fetch(autosaveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: collectFormData(),
        })
            .then(async (response) => {
                if (response.status === 429) {
                    throw new Error('Terlalu banyak permintaan. Sila tunggu sebentar.');
                }

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal menyimpan progres.');
                }

                showStatus(data.message || 'Data disimpan');
                return data;
            })
            .catch((error) => {
                showStatus(error.message || 'Gagal menyimpan progres.');
                throw error;
            })
            .finally(() => {
                autosaveInFlight = null;
                if (saveButton) {
                    saveButton.disabled = false;
                }

                if (autosavePending) {
                    autosavePending = false;
                    saveProgress();
                }
            });

        return autosaveInFlight;
    }

    form.querySelectorAll('.negeri-draft-ack').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            clearTimeout(autosaveTimer);
            autosaveTimer = window.setTimeout(() => {
                saveProgress();
            }, AUTOSAVE_DEBOUNCE_MS);
        });
    });

    saveButton?.addEventListener('click', () => {
        clearTimeout(autosaveTimer);
        saveProgress();
    });
})();

(function () {
    const form = document.getElementById('hq-draft-acknowledgement-form');
    if (!form) {
        return;
    }

    const autosaveUrl = form.dataset.autosaveUrl;
    const status = document.getElementById('hq-draft-save-status');
    const saveButton = document.getElementById('hq-draft-save-button');
    let autosaveTimer = null;
    let autosaveInFlight = null;
    let autosavePending = false;
    const AUTOSAVE_DEBOUNCE_MS = 400;

    function showStatus(message) {
        if (!status) {
            return;
        }

        status.textContent = message;
        status.classList.remove('hidden');
    }

    function collectFormData() {
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

        form.querySelectorAll('.hq-draft-ack').forEach((checkbox) => {
            formData.append(`acknowledgements[${checkbox.dataset.ackKey}]`, checkbox.checked ? '1' : '0');
        });

        return formData;
    }

    async function saveProgress() {
        if (!autosaveUrl) {
            return;
        }

        if (autosaveInFlight) {
            autosavePending = true;
            return autosaveInFlight;
        }

        if (saveButton) {
            saveButton.disabled = true;
        }

        autosaveInFlight = fetch(autosaveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: collectFormData(),
        })
            .then(async (response) => {
                if (response.status === 429) {
                    throw new Error('Terlalu banyak permintaan. Sila tunggu sebentar.');
                }

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal menyimpan progres.');
                }

                showStatus(data.message || 'Data disimpan');
                return data;
            })
            .catch((error) => {
                showStatus(error.message || 'Gagal menyimpan progres.');
                throw error;
            })
            .finally(() => {
                autosaveInFlight = null;
                if (saveButton) {
                    saveButton.disabled = false;
                }

                if (autosavePending) {
                    autosavePending = false;
                    saveProgress();
                }
            });

        return autosaveInFlight;
    }

    form.querySelectorAll('.hq-draft-ack').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            clearTimeout(autosaveTimer);
            autosaveTimer = window.setTimeout(() => {
                saveProgress();
            }, AUTOSAVE_DEBOUNCE_MS);
        });
    });

    saveButton?.addEventListener('click', () => {
        clearTimeout(autosaveTimer);
        saveProgress();
    });
})();

(function () {
    const form = document.getElementById('negeri-mati-setem-form');
    if (!form) {
        return;
    }

    const autosaveUrl = form.dataset.autosaveUrl;
    const status = document.getElementById('negeri-mati-setem-save-status');
    const saveButton = document.getElementById('negeri-mati-setem-save-button');
    let autosaveTimer = null;
    let autosaveInFlight = null;
    let autosavePending = false;
    const AUTOSAVE_DEBOUNCE_MS = 400;

    function showStatus(message) {
        if (!status) {
            return;
        }

        status.textContent = message;
        status.classList.remove('hidden');
    }

    function collectFormData() {
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

        form.querySelectorAll('.negeri-mati-setem-ack').forEach((checkbox) => {
            formData.append(`acknowledgements[${checkbox.dataset.ackKey}]`, checkbox.checked ? '1' : '0');
        });

        return formData;
    }

    async function saveProgress() {
        if (!autosaveUrl) {
            return;
        }

        if (autosaveInFlight) {
            autosavePending = true;
            return autosaveInFlight;
        }

        if (saveButton) {
            saveButton.disabled = true;
        }

        autosaveInFlight = fetch(autosaveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: collectFormData(),
        })
            .then(async (response) => {
                if (response.status === 429) {
                    throw new Error('Terlalu banyak permintaan. Sila tunggu sebentar.');
                }

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal menyimpan progres.');
                }

                showStatus(data.message || 'Data disimpan');
                return data;
            })
            .catch((error) => {
                showStatus(error.message || 'Gagal menyimpan progres.');
                throw error;
            })
            .finally(() => {
                autosaveInFlight = null;
                if (saveButton) {
                    saveButton.disabled = false;
                }

                if (autosavePending) {
                    autosavePending = false;
                    saveProgress();
                }
            });

        return autosaveInFlight;
    }

    form.querySelectorAll('.negeri-mati-setem-ack').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            clearTimeout(autosaveTimer);
            autosaveTimer = window.setTimeout(() => {
                saveProgress();
            }, AUTOSAVE_DEBOUNCE_MS);
        });
    });

    saveButton?.addEventListener('click', () => {
        clearTimeout(autosaveTimer);
        saveProgress();
    });
})();
</script>
@endpush
