@extends('layouts.app')

@section('title', 'Langkah Tindakan')
@section('header_title', 'Langkah Tindakan')
@section('header_subtitle', $contract->premise?->nama_ptj ?? 'Lengkapkan semua langkah tindakan permohonan')

@section('content')
<div>
    <form
        id="form-tindakan"
        method="POST"
        action="{{ route('admin-proceed.update', $contract) }}"
        class="glass-card block overflow-hidden"
        data-autosave-url="{{ route('application.autosave', $contract) }}"
        data-initial-step="{{ $initialStep ?? $step }}"
        data-active-steps="{{ json_encode($activeSteps) }}"
        data-remark-required="{{ $contract->isFollowUpApplication() ? '1' : '0' }}"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="current_step" id="current-step-input" value="{{ $initialStep ?? $step }}">

        @include('application.partials.proceed-panel')

        <div class="glass-divider flex items-center justify-end gap-3 border-t px-6 py-4">
            <a href="{{ route('application.edit', $contract) }}" class="mr-auto text-xs font-medium text-slate-500 transition-colors hover:text-slate-800">
                Kemaskini borang permohonan
            </a>
            <p id="form-save-status" class="hidden text-xs text-slate-500" aria-live="polite"></p>
            <button type="button" id="form-save-button" class="glass-btn-secondary rounded-xl px-5 py-2 text-sm font-medium disabled:opacity-60">
                Seterusnya
            </button>
            <div id="submit-hq-wrap" @class(['contents' => $contract->isReadyToSendToHq(), 'hidden' => ! $contract->isReadyToSendToHq()])>
                <button
                    type="button"
                    id="submit-hq-button"
                    class="status-confirm-trigger glass-btn-primary rounded-xl px-5 py-2 text-sm font-medium"
                    data-confirm-title="Hantar Permohonan ke Ibu Pejabat"
                    data-confirm-message="Anda pasti mahu menghantar permohonan ini kepada Ibu Pejabat untuk semakan? Sila semak maklumat premis sebelum meneruskan."
                    data-confirm-form="submit-hq-form"
                    data-confirm-button="Ya, Hantar"
                    data-confirm-tone="success"
                >
                    Hantar ke Ibu Pejabat
                </button>
            </div>
        </div>
    </form>

    <form id="submit-hq-form" method="POST" action="{{ route('status-permohonan.submit-hq', $contract) }}" class="hidden">
        @csrf
    </form>
</div>

@include('application.partials.form-required-field-scripts')
@include('partials.confirm-action-modal')
@include('application.partials.proceed-scripts', [
    'formId' => 'form-tindakan',
    'saveUrl' => route('admin-proceed.update', $contract),
    'redirectWhenComplete' => route('status-permohonan.index'),
])
@push('scripts')
<script>
(function () {
    const form = document.getElementById('form-tindakan');
    window.ApplicationFormValidation?.bind(form);

    const modal = document.getElementById('confirm-action-modal');
    const modalTitle = document.getElementById('confirm-action-title');
    const modalMessage = document.getElementById('confirm-action-message');
    const modalDetailsWrap = document.getElementById('confirm-action-details-wrap');
    const modalWithdrawalReasonRow = document.getElementById('confirm-action-withdrawal-reason-row');
    const modalWithdrawalReason = document.getElementById('confirm-action-withdrawal-reason');
    const modalConfirm = document.getElementById('confirm-action-confirm');
    const modalCancel = document.getElementById('confirm-action-cancel');
    const modalIcon = document.getElementById('confirm-action-icon');
    const formSaveStatus = document.getElementById('form-save-status');
    let pendingForm = null;

    const iconTemplates = {
        success: '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
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

    function showFormStatus(message) {
        if (!formSaveStatus) return;
        formSaveStatus.textContent = message;
        formSaveStatus.classList.remove('hidden');
        window.setTimeout(() => formSaveStatus.classList.add('hidden'), 3000);
    }

    document.querySelectorAll('.status-confirm-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const targetForm = document.getElementById(trigger.dataset.confirmForm);
            if (!targetForm || !modal || !modalConfirm || !modalTitle || !modalMessage) return;

            if (trigger.id === 'submit-hq-button' && form && !window.ApplicationFormValidation?.validate(form)) {
                showFormStatus('Sila lengkapkan semua ruangan yang wajib diisi.');
                return;
            }

            modalTitle.textContent = trigger.dataset.confirmTitle || 'Sahkan Tindakan';
            modalMessage.textContent = trigger.dataset.confirmMessage || '';
            setConfirmDetails(trigger);
            modalConfirm.textContent = trigger.dataset.confirmButton || 'Sahkan';
            modalConfirm.className = 'glass-btn-success rounded-xl px-4 py-2 text-sm font-medium';

            if (modalIcon) {
                modalIcon.className = 'mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600';
                modalIcon.innerHTML = iconTemplates.success;
            }

            pendingForm = targetForm;
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
@endsection
