@extends('layouts.app')

@php
    $p = $contract->premise;
@endphp

@section('title', 'Semak Permohonan')
@section('header_title', 'Semak Permohonan')
@section('header_subtitle', $p?->nama_ptj ?? 'Kandungan permohonan sewaan')

@section('content')
<div class="space-y-4">
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
            <h2 class="text-base font-semibold text-slate-900">Langkah Tindakan Pentadbir Negeri</h2>
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
            <h2 class="text-base font-semibold text-slate-900">Pengesahan HQ</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                @if($contract->hasPendingWithdrawalRequest())
                    Permohonan ini menunggu keputusan tarik semula daripada negeri.
                @else
                    Lengkapkan checklist JRP sebelum mengesahkan permohonan.
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
                        data-confirm-title="Sahkan Permohonan"
                        data-confirm-message="Anda pasti mahu mengesahkan permohonan ini selepas semakan?"
                        data-confirm-form="approve-hq-form-review"
                        data-confirm-button="Ya, Sahkan"
                        data-confirm-tone="success"
                    >
                        Sahkan Permohonan
                    </button>
                </div>
            </form>
        @endif
    </section>
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
</script>
@endpush
