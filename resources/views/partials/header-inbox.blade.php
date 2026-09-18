@php
    $inboxTotal = (int) ($sidebarNotifications['inbox_total'] ?? 0);
    $previewItems = collect($inboxPreviewItems ?? []);

    if ($previewItems->isEmpty() && $inboxTotal > 0 && auth()->check() && auth()->user()->isAdmin()) {
        $previewItems = app(\App\Services\SidebarNotificationService::class)->inboxItems(auth()->user(), 8);
    }
@endphp

<div class="relative z-40" id="header-inbox" data-inbox-poll-url="{{ route('sidebar-notifications') }}">
    <button
        type="button"
        id="header-inbox-toggle"
        class="header-inbox-toggle relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-800 shadow-sm transition-colors hover:border-slate-400 hover:bg-slate-50 hover:text-slate-950"
        aria-expanded="false"
        aria-controls="header-inbox-panel"
        title="Peti Masuk"
    >
        <span class="sr-only">Peti Masuk</span>
        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0l-8 5-8-5" />
        </svg>
        <span
            data-sidebar-notification-badge="inbox_total"
            @class([
                'absolute -right-1 -top-1 inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-4 text-white ring-2 ring-white',
                'hidden' => $inboxTotal < 1,
            ])
        >{{ $inboxTotal > 99 ? '99+' : $inboxTotal }}</span>
    </button>

    <div
        id="header-inbox-panel"
        class="header-inbox-panel absolute right-0 top-full z-[80] mt-2 hidden w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-2xl"
        role="menu"
        aria-label="Peti Masuk"
    >
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-slate-900">Peti Masuk</p>
                <p class="text-xs text-slate-600">Notifikasi permohonan &amp; kontrak</p>
            </div>
            <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-indigo-700 underline-offset-2 hover:text-indigo-900 hover:underline">
                Lihat semua
            </a>
        </div>

        <div id="header-inbox-list" class="max-h-80 overflow-y-auto bg-white">
            @forelse($previewItems as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="header-inbox-item block border-b border-slate-100 px-4 py-3 transition-colors last:border-b-0 hover:bg-indigo-50"
                    role="menuitem"
                >
                    <p class="header-inbox-item-category text-[10px] font-semibold uppercase tracking-wide text-indigo-700">{{ $item['category_label'] }}</p>
                    <p class="header-inbox-item-title mt-0.5 line-clamp-2 text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                    <p class="header-inbox-item-message mt-0.5 line-clamp-1 text-xs text-slate-600">{{ $item['message'] }}</p>
                </a>
            @empty
                <p class="header-inbox-empty px-4 py-8 text-center text-sm text-slate-600">Tiada notifikasi baharu.</p>
            @endforelse
        </div>

        <div id="header-inbox-footer" @class(['border-t border-slate-200 bg-slate-50 px-4 py-2.5', 'hidden' => $previewItems->isEmpty()])>
            <a href="{{ route('notifications.index') }}" class="block text-center text-xs font-semibold text-indigo-700 hover:text-indigo-900">
                Buka peti masuk penuh
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('header-inbox');
    const toggle = document.getElementById('header-inbox-toggle');
    const panel = document.getElementById('header-inbox-panel');
    const list = document.getElementById('header-inbox-list');
    const footer = document.getElementById('header-inbox-footer');
    const pollUrl = root ? root.getAttribute('data-inbox-poll-url') : null;

    if (! root || ! toggle || ! panel || ! list) {
        return;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderInboxItems(items) {
        const rows = Array.isArray(items) ? items : [];

        if (rows.length === 0) {
            list.innerHTML = '<p class="header-inbox-empty px-4 py-8 text-center text-sm text-slate-600">Tiada notifikasi baharu.</p>';
            if (footer) {
                footer.classList.add('hidden');
            }
            return;
        }

        list.innerHTML = rows.map(function (item) {
            return (
                '<a href="' + escapeHtml(item.url) + '" class="header-inbox-item block border-b border-slate-100 px-4 py-3 transition-colors last:border-b-0 hover:bg-indigo-50" role="menuitem">' +
                    '<p class="header-inbox-item-category text-[10px] font-semibold uppercase tracking-wide text-indigo-700">' + escapeHtml(item.category_label) + '</p>' +
                    '<p class="header-inbox-item-title mt-0.5 line-clamp-2 text-sm font-semibold text-slate-900">' + escapeHtml(item.title) + '</p>' +
                    '<p class="header-inbox-item-message mt-0.5 line-clamp-1 text-xs text-slate-600">' + escapeHtml(item.message) + '</p>' +
                '</a>'
            );
        }).join('');

        if (footer) {
            footer.classList.remove('hidden');
        }
    }

    function refreshInboxItems() {
        if (! pollUrl) {
            return;
        }

        fetch(pollUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('Inbox refresh failed');
                }

                return response.json();
            })
            .then(function (payload) {
                if (payload && Object.prototype.hasOwnProperty.call(payload, 'inbox_items')) {
                    renderInboxItems(payload.inbox_items);
                }
            })
            .catch(function () {
                // Keep the last rendered list on transient errors.
            });
    }

    window.__esewaanRenderHeaderInbox = renderInboxItems;

    function setOpen(open) {
        panel.classList.toggle('hidden', ! open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            refreshInboxItems();
        }
    }

    toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        setOpen(panel.classList.contains('hidden'));
    });

    document.addEventListener('click', function (event) {
        if (! root.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
})();
</script>
