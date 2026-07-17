@extends('layouts.app')

@section('title', 'Senarai Pengguna')
@section('header_title', 'Senarai Pengguna')
@section('header_subtitle', 'Pengurusan Pengguna — senarai semua pengguna sistem')

@section('content')
<div class="space-y-4" id="users-page"
    data-users-url="{{ route('users.index') }}"
    data-initial-sort="{{ $sort }}"
    data-initial-direction="{{ $direction }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-md">
            <label for="users-search" class="sr-only">Cari pengguna</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                type="search"
                id="users-search"
                value="{{ $search }}"
                placeholder="Cari nama, emel, negeri..."
                autocomplete="off"
                class="glass-input w-full rounded-xl py-2.5 pl-10 pr-10 text-sm"
            >
            <span id="users-search-loading" class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 text-slate-400">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </span>
        </div>
        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2 glass-btn-primary rounded-xl px-4 py-2.5 text-sm font-medium whitespace-nowrap">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Pendaftaran Pengguna
        </a>
    </div>

    <div id="users-list" class="space-y-4">
        @include('users.partials.table')
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const page = document.getElementById('users-page');
    if (!page) return;

    const list = document.getElementById('users-list');
    const searchInput = document.getElementById('users-search');
    const loading = document.getElementById('users-search-loading');
    const baseUrl = page.dataset.usersUrl;

    let sort = page.dataset.initialSort || 'created_at';
    let direction = page.dataset.initialDirection || 'desc';
    let debounceTimer = null;
    let controller = null;

    function setLoading(isLoading) {
        if (!loading) return;
        loading.classList.toggle('hidden', !isLoading);
    }

    function fetchUsers(pageNumber) {
        if (controller) controller.abort();
        controller = new AbortController();

        const params = new URLSearchParams();
        params.set('partial', '1');
        if (searchInput?.value.trim()) params.set('search', searchInput.value.trim());
        params.set('sort', sort);
        params.set('direction', direction);
        if (pageNumber) params.set('page', pageNumber);

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
                const url = `${baseUrl}?${params.toString()}`;
                window.history.replaceState({}, '', url);
            })
            .catch((error) => {
                if (error.name !== 'AbortError') console.error(error);
            })
            .finally(() => setLoading(false));
    }

    function bindListEvents() {
        list.querySelectorAll('[data-users-sort]').forEach((button) => {
            button.addEventListener('click', () => {
                const column = button.dataset.usersSort;
                if (sort === column) {
                    direction = direction === 'asc' ? 'desc' : 'asc';
                } else {
                    sort = column;
                    direction = 'asc';
                }
                fetchUsers(1);
            });
        });

    }

    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchUsers(1), 300);
    });

    bindListEvents();
})();
</script>
@endpush
