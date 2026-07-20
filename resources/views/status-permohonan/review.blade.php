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
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Negeri</p>
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
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Sah Sehingga</p>
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
            <h2 class="text-base font-semibold text-slate-900">Kemajuan Permohonan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Klik pada mana-mana langkah untuk melihat butiran bahagian tersebut.</p>
        </div>
        <div class="px-4 py-5">
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

            @if(filled($contract->remark))
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

        {{-- Langkah 2: Semakan HQ --}}
        <div data-step-panel="1" class="space-y-4 {{ $currentStepIndex === 1 ? '' : 'hidden' }}">
            @if($contract->isPendingHqReview())
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Pengesahan HQ</h2>
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
                                        data-confirm-message="Permohonan akan kekal dalam senarai semakan HQ. Anda pasti mahu menolak permohonan tarik semula ini?"
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
                <div class="px-4 py-3">
                    @if($currentStepIndex >= 2)
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status Semasa</p>
                        <span class="{{ $contract->applicationStatusBadgeClass() }} mt-1 inline-flex">
                            {{ $contract->applicationStatusLabel(auth()->user()) }}
                        </span>
                    @else
                        <p class="text-sm text-slate-500">Peringkat ini belum bermula.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- Langkah 4: Draf Lulus --}}
        <div data-step-panel="3" class="space-y-4 {{ $currentStepIndex === 3 ? '' : 'hidden' }}">
            @if(auth()->user()->isAdminNegeri() && $contract->isDrafPerjanjianLulus())
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Tindakan Negeri</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Sahkan penerimaan draf akhir dan kembalikan kepada Admin.</p>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('status-permohonan.return-hq', $contract) }}"
                        class="space-y-4 px-4 py-3"
                    >
                        @csrf
                        <label class="flex items-start gap-3 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="draf_akhir_acknowledged"
                                value="1"
                                required
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                @checked(old('draf_akhir_acknowledged'))
                            >
                            <span>
                                Draf akhir dikembalikan kepada AADK Negeri untuk penyediaan dokumen perjanjian dan dapatkan tandatangan pemilik premis. Kembalikan kepada Cawangan Pembangunan AADK.
                            </span>
                        </label>

                        @error('draf_akhir_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <button
                                type="submit"
                                class="glass-btn-primary rounded-lg px-4 py-2 text-sm font-medium"
                            >
                                Hantar Semula ke Admin
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Draf Lulus</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Draf perjanjian diluluskan dan dikembalikan kepada Negeri.</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-sm text-slate-600">
                            @if($currentStepIndex > 3)
                                Draf perjanjian telah diluluskan dan tindakan Negeri telah selesai.
                            @elseif($currentStepIndex === 3)
                                Menunggu tindakan Pegawai Negeri untuk mengesahkan penerimaan draf akhir.
                            @else
                                Peringkat ini belum bermula.
                            @endif
                        </p>
                    </div>
                </section>
            @endif
        </div>

        {{-- Langkah 5: Pengesahan & Tandatangan --}}
        <div data-step-panel="4" class="space-y-4 {{ $currentStepIndex === 4 ? '' : 'hidden' }}">
            @if(auth()->user()->isAdminHq() && $contract->isDrafDikembalikanHq())
                <section class="glass-card overflow-hidden">
                    <div class="glass-divider-soft border-b px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">Tindakan Admin</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Sahkan kesemua langkah berikut sebelum menekan Selesai untuk memasukkan permohonan ke dalam Senarai Kontrak Sewaan.</p>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('status-permohonan.finalize', $contract) }}"
                        class="space-y-4 px-4 py-3"
                    >
                        @csrf
                        <label class="flex items-start gap-3 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="terima_dokumen_acknowledged"
                                value="1"
                                required
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                @checked(old('terima_dokumen_acknowledged'))
                            >
                            <span>
                                Cawangan Pembangunan AADK menerima dokumen perjanjian dan mengemukakan kepada TKPP AADK untuk tandatangan bagi pihak AADK/Kerajaan Malaysia.
                            </span>
                        </label>

                        <label class="flex items-start gap-3 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="perjanjian_ditandatangani_acknowledged"
                                value="1"
                                required
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                @checked(old('perjanjian_ditandatangani_acknowledged'))
                            >
                            <span>
                                Perjanjian ditandatangani TKPP AADK dikembalikan kepada AADK Negeri untuk dimatikan setem dan edaran kepada pemilik premis.
                            </span>
                        </label>

                        <label class="flex items-start gap-3 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="salinan_promis_acknowledged"
                                value="1"
                                required
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                @checked(old('salinan_promis_acknowledged'))
                            >
                            <span>
                                1 salinan perjanjian dihantar ke Cawangan Pembangunan AADK untuk dimuat naik ke dalam Sistem ProMIS dan rekod fail Cawangan Pembangunan.
                            </span>
                        </label>

                        @error('terima_dokumen_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('perjanjian_ditandatangani_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('salinan_promis_acknowledged')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center justify-end gap-2">
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
                        <h2 class="text-base font-semibold text-slate-900">Pengesahan & Tandatangan</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Pengesahan penerimaan dan tandatangan perjanjian oleh Admin.</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-sm text-slate-600">
                            @if($currentStepIndex > 4)
                                Perjanjian telah disahkan dan permohonan telah selesai.
                            @elseif($currentStepIndex === 4)
                                Menunggu tindakan Admin untuk pengesahan dan tandatangan perjanjian.
                            @else
                                Peringkat ini belum bermula.
                            @endif
                        </p>
                    </div>
                </section>
            @endif
        </div>

        {{-- Langkah 6: Selesai --}}
        <div data-step-panel="5" class="space-y-4 {{ $currentStepIndex === 5 ? '' : 'hidden' }}">
            <section class="glass-card overflow-hidden">
                <div class="glass-divider-soft border-b px-4 py-3">
                    <h2 class="text-base font-semibold text-slate-900">Selesai</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Permohonan selesai dan dimasukkan ke dalam Senarai Kontrak Sewaan.</p>
                </div>
                <div class="px-4 py-3">
                    @if($currentStepIndex >= 5)
                        <p class="text-sm text-emerald-700">Permohonan telah selesai dan dimasukkan ke dalam Senarai Kontrak Sewaan.</p>
                    @else
                        <p class="text-sm text-slate-600">Peringkat ini belum bermula. Permohonan akan dimasukkan ke dalam Senarai Kontrak Sewaan setelah selesai.</p>
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
@endpush
