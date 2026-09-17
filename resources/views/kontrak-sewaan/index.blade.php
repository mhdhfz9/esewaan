@extends('layouts.app')

@php
    $tab = $tab ?? 'active';
@endphp

@section('title', 'Kontrak Sewaan')
@section('header_title', 'Kontrak Sewaan')
@section('header_subtitle', $tab === 'history'
    ? 'Senarai kontrak sewaan yang telah tamat tempoh'
    : 'Senarai permohonan yang telah disahkan oleh Ibu Pejabat')

@section('content')
<div class="space-y-4" id="kontrak-sewaan-page" data-list-url="{{ route('kontrak-sewaan.index') }}" data-current-tab="{{ $tab }}">
    <div class="glass-tabs">
        <button
            type="button"
            data-kontrak-tab="active"
            @class([
                'glass-tab',
                'is-active' => $tab === 'active',
            ])
        >
            Senarai Aktif
        </button>
        <button
            type="button"
            data-kontrak-tab="history"
            @class([
                'glass-tab',
                'is-active' => $tab === 'history',
            ])
        >
            Sejarah
        </button>
    </div>

    <div class="relative w-full lg:max-w-md">
        <label for="kontrak-sewaan-search" class="sr-only">Cari kontrak sewaan</label>
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
            type="search"
            id="kontrak-sewaan-search"
            value="{{ $search }}"
            placeholder="{{ $tab === 'history' ? 'Cari kontrak tamat tempoh...' : 'Cari nama premis, negeri, kategori...' }}"
            autocomplete="off"
            class="glass-input w-full rounded-xl py-2.5 pl-10 pr-10 text-sm shadow-sm"
        >
        <span id="kontrak-sewaan-search-loading" class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 text-slate-400">
            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </span>
    </div>

    <div id="kontrak-sewaan-list" class="space-y-4">
        @include($tab === 'history' ? 'kontrak-sewaan.partials.history-table' : 'kontrak-sewaan.partials.table')
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const page = document.getElementById('kontrak-sewaan-page');
    if (!page) return;

    const list = document.getElementById('kontrak-sewaan-list');
    const searchInput = document.getElementById('kontrak-sewaan-search');
    const loading = document.getElementById('kontrak-sewaan-search-loading');
    const baseUrl = page.dataset.listUrl;
    let currentTab = page.dataset.currentTab || 'active';
    let currentPage = Number(new URL(window.location.href).searchParams.get('page') || 1);

    let debounceTimer = null;
    let controller = null;

    function setLoading(isLoading) {
        if (!loading) return;
        loading.classList.toggle('hidden', !isLoading);
    }

    function updateTabButtons() {
        page.querySelectorAll('[data-kontrak-tab]').forEach((button) => {
            const isActive = button.dataset.kontrakTab === currentTab;
            button.classList.toggle('is-active', isActive);
        });

        if (searchInput) {
            searchInput.placeholder = currentTab === 'history'
                ? 'Cari kontrak tamat tempoh...'
                : 'Cari nama premis, negeri, kategori...';
        }
    }

    function fetchList(pageNumber) {
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

        setLoading(true);

        fetch(`${baseUrl}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
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
                window.history.replaceState({}, '', `${baseUrl}?${params.toString()}`);
            })
            .catch((error) => {
                if (error.name !== 'AbortError') console.error(error);
            })
            .finally(() => setLoading(false));
    }

    function bindListEvents() {
        list.querySelectorAll('#kontrak-sewaan-pagination a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const url = new URL(link.href);
                fetchList(url.searchParams.get('page') || 1);
            });
        });
    }

    page.querySelectorAll('[data-kontrak-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextTab = button.dataset.kontrakTab;
            if (!nextTab || nextTab === currentTab) {
                return;
            }

            currentTab = nextTab;
            currentPage = 1;
            updateTabButtons();
            fetchList(1);
        });
    });

    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchList(1), 300);
    });

    updateTabButtons();
    bindListEvents();
})();
</script>
@endpush
