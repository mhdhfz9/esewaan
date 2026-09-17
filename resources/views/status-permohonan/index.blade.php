@extends('layouts.app')

@php
    $isAdminHq = auth()->user()->isAdminHq();
    $pageTitle = $isAdminHq ? 'Senarai Permohonan' : 'Status Permohonan';
    $tab = $tab ?? 'active';
@endphp

@section('title', $pageTitle)
@section('header_title', $pageTitle)
@section('header_subtitle', $isAdminHq ? 'Semak dan sahkan permohonan yang dihantar oleh pentadbir negeri' : 'Pantau status permohonan dan lengkapkan tindakan')

@section('content')
<div class="space-y-4" id="status-permohonan-page" data-status-url="{{ route('status-permohonan.index') }}" data-sync-url="{{ route('status-permohonan.sync') }}" data-current-tab="{{ $tab }}">
    @if($isAdminHq)
        <div class="glass-tabs">
            <button
                type="button"
                data-status-tab="active"
                @class([
                    'glass-tab',
                    'is-active' => $tab === 'active',
                ])
            >
                Senarai Aktif
            </button>
            <button
                type="button"
                data-status-tab="history"
                @class([
                    'glass-tab',
                    'is-active' => $tab === 'history',
                ])
            >
                Sejarah
            </button>
        </div>
    @endif

    <div class="relative w-full lg:max-w-md">
        <label for="status-permohonan-search" class="sr-only">Cari permohonan</label>
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
            type="search"
            id="status-permohonan-search"
            value="{{ $search }}"
            placeholder="{{ $tab === 'history' ? 'Cari nama premis, negeri, alasan padam...' : 'Cari nama premis, kategori, negeri...' }}"
            autocomplete="off"
            class="glass-input w-full rounded-xl py-2.5 pl-10 pr-10 text-sm shadow-sm"
        >
        <span id="status-permohonan-search-loading" class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 text-slate-400">
            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </span>
    </div>

    <div id="status-permohonan-list" class="space-y-4">
        @include($tab === 'history' ? 'status-permohonan.partials.history-table' : 'status-permohonan.partials.table')
    </div>
</div>

@include('partials.confirm-action-modal')
@endsection

@push('scripts')
<script>
(function () {
    const page = document.getElementById('status-permohonan-page');
    if (!page) return;

    const list = document.getElementById('status-permohonan-list');
    const searchInput = document.getElementById('status-permohonan-search');
    const loading = document.getElementById('status-permohonan-search-loading');
    const baseUrl = page.dataset.statusUrl;
    const syncUrl = page.dataset.syncUrl;
    let currentTab = page.dataset.currentTab || 'active';
    let currentPage = Number(new URL(window.location.href).searchParams.get('page') || 1);

    let debounceTimer = null;
    let controller = null;
    const pollIntervalMs = 15000;
    const listInteraction = window.EsewaanListInteraction;

    function buildSyncParams() {
        const params = new URLSearchParams();
        if (searchInput?.value.trim()) params.set('search', searchInput.value.trim());
        if (currentTab === 'history') params.set('tab', 'history');

        return params;
    }

    const hoverGuard = listInteraction.attachHoverGuard(list, () => {
        fetchList(currentPage, { silent: true });
    });

    let listPoller = null;

    function scrollContainer() {
        return document.querySelector('main.flex-1.overflow-y-auto');
    }

    function setLoading(isLoading) {
        if (!loading) return;
        loading.classList.toggle('hidden', !isLoading);
    }

    function updateTabButtons() {
        page.querySelectorAll('[data-status-tab]').forEach((button) => {
            const isActive = button.dataset.statusTab === currentTab;
            button.classList.toggle('is-active', isActive);
        });

        if (searchInput) {
            searchInput.placeholder = currentTab === 'history'
                ? 'Cari nama premis, negeri, alasan padam...'
                : 'Cari nama premis, kategori, negeri...';
        }
    }

    function fetchList(pageNumber, options = {}) {
        const silent = options.silent === true;

        if (pageNumber) {
            currentPage = Number(pageNumber);
        }

        if (controller) controller.abort();
        controller = new AbortController();

        const params = new URLSearchParams();
        params.set('partial', '1');
        if (searchInput?.value.trim()) params.set('search', searchInput.value.trim());
        if (currentPage > 1) params.set('page', String(currentPage));
        if (currentTab === 'history') params.set('tab', 'history');

        if (! silent) {
            setLoading(true);
        }

        const preservedScrollTop = silent ? scrollContainer()?.scrollTop ?? null : null;

        fetch(`${baseUrl}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
                ...(silent ? { 'X-Background-Request': '1' } : {}),
            },
            signal: controller.signal,
        })
            .then((response) => {
                if (!response.ok) throw new Error('Gagal memuatkan senarai.');
                return response.text();
            })
            .then((html) => {
                list.innerHTML = html;
                bindListEvents();

                if (! silent) {
                    window.history.replaceState({}, '', `${baseUrl}?${params.toString()}`);
                }

                if (preservedScrollTop !== null) {
                    const container = scrollContainer();
                    if (container) {
                        container.scrollTop = preservedScrollTop;
                    }
                }
            })
            .catch((error) => {
                if (error.name !== 'AbortError') console.error(error);
            })
            .finally(() => {
                if (! silent) {
                    setLoading(false);
                }

                pollListState();
            });
    }

    function pollListState() {
        listPoller?.poll();
    }

    function bindListEvents() {
        list.querySelectorAll('#status-permohonan-pagination a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const url = new URL(link.href);
                fetchList(url.searchParams.get('page') || 1);
            });
        });

        bindConfirmTriggers();
    }

    const modal = document.getElementById('confirm-action-modal');
    const modalTitle = document.getElementById('confirm-action-title');
    const modalMessage = document.getElementById('confirm-action-message');
    const modalDetailsWrap = document.getElementById('confirm-action-details-wrap');
    const modalWithdrawalReasonRow = document.getElementById('confirm-action-withdrawal-reason-row');
    const modalWithdrawalReason = document.getElementById('confirm-action-withdrawal-reason');
    const modalReasonWrap = document.getElementById('confirm-action-reason-wrap');
    const modalReason = document.getElementById('confirm-action-reason');
    const modalReasonLabel = document.getElementById('confirm-action-reason-label');
    const modalReasonError = document.getElementById('confirm-action-reason-error');
    const modalIcon = document.getElementById('confirm-action-icon');
    const modalCancel = document.getElementById('confirm-action-cancel');
    const modalConfirm = document.getElementById('confirm-action-confirm');
    let pendingForm = null;

    const iconTemplates = {
        success: '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>',
        danger: '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>',
    };

    function closeConfirmModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        pendingForm = null;
        if (modalReason) modalReason.value = '';
        if (modalReasonError) modalReasonError.classList.add('hidden');
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

    function openConfirmModal(trigger) {
        if (!modal || !modalTitle || !modalMessage || !modalConfirm) return;

        const form = document.getElementById(trigger.dataset.confirmForm);
        if (!form) return;

        const tone = trigger.dataset.confirmTone || 'primary';
        const requireReason = trigger.dataset.confirmRequireReason === 'true';
        const reasonField = trigger.dataset.confirmReasonField || 'delete_reason';
        const reasonLabel = trigger.dataset.confirmReasonLabel || 'Alasan Padam *';

        modalTitle.textContent = trigger.dataset.confirmTitle || 'Sahkan Tindakan';
        modalMessage.textContent = trigger.dataset.confirmMessage || '';

        setConfirmDetails(trigger);

        if (modalReasonWrap) {
            modalReasonWrap.classList.toggle('hidden', !requireReason);
        }
        if (modalReasonLabel) {
            modalReasonLabel.textContent = reasonLabel;
        }
        if (modalReason) {
            modalReason.value = '';
        }
        if (modalReasonError) {
            modalReasonError.classList.add('hidden');
        }

        modalConfirm.textContent = trigger.dataset.confirmButton || 'Sahkan';
        modalConfirm.dataset.requireReason = requireReason ? 'true' : 'false';
        modalConfirm.dataset.reasonField = reasonField;

        modalConfirm.className = 'rounded-xl px-4 py-2 text-sm font-medium';
        if (tone === 'danger') {
            modalConfirm.classList.add('glass-btn-danger');
        } else if (tone === 'success') {
            modalConfirm.classList.add('glass-btn-success');
        } else {
            modalConfirm.classList.add('glass-btn-primary');
        }

        if (modalIcon) {
            modalIcon.className = 'mb-4 flex h-11 w-11 items-center justify-center rounded-full';
            if (tone === 'danger') {
                modalIcon.classList.add('bg-red-50', 'text-red-600');
            } else if (tone === 'success') {
                modalIcon.classList.add('bg-emerald-50', 'text-emerald-600');
            } else {
                modalIcon.classList.add('bg-slate-100', 'text-slate-600');
            }
            modalIcon.innerHTML = iconTemplates[tone] || iconTemplates.danger;
        }

        pendingForm = form;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        (requireReason && modalReason ? modalReason : modalConfirm).focus();
    }

    function bindConfirmTriggers() {
        list.querySelectorAll('.status-confirm-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => openConfirmModal(trigger));
        });
    }

    page.querySelectorAll('[data-status-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextTab = button.dataset.statusTab;
            if (!nextTab || nextTab === currentTab) return;
            currentTab = nextTab;
            page.dataset.currentTab = currentTab;
            updateTabButtons();
            fetchList(1);
        });
    });

    if (modalCancel) {
        modalCancel.addEventListener('click', closeConfirmModal);
    }

    if (modalConfirm) {
        modalConfirm.addEventListener('click', () => {
            if (!pendingForm) return;

            if (modalConfirm.dataset.requireReason === 'true') {
                const reason = modalReason?.value.trim() || '';
                if (reason.length < 10) {
                    modalReasonError?.classList.remove('hidden');
                    modalReason?.focus();
                    return;
                }

                const reasonField = modalConfirm.dataset.reasonField || 'delete_reason';
                const reasonInput = pendingForm.querySelector(`[name="${reasonField}"]`);
                if (reasonInput) {
                    reasonInput.value = reason;
                }
            }

            pendingForm.submit();
            closeConfirmModal();
        });
    }

    if (modal) {
        modal.querySelectorAll('[data-confirm-dismiss]').forEach((element) => {
            element.addEventListener('click', closeConfirmModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeConfirmModal();
            }
        });
    }

    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchList(1), 300);
    });

    updateTabButtons();
    bindListEvents();

    listPoller = listInteraction.createFingerprintPoller({
        syncUrl,
        buildParams: buildSyncParams,
        onFingerprintChange: () => fetchList(currentPage, { silent: true }),
        intervalMs: pollIntervalMs,
        shouldPoll: () => ! modal || modal.classList.contains('hidden'),
        deferRefresh: (refreshFn) => hoverGuard.deferRefresh(refreshFn),
    });

    listPoller.start();
})();
</script>
@endpush
