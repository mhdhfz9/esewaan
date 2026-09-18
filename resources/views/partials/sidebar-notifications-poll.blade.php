@if(auth()->user()->isAdmin())
<script>
(function () {
    const pollUrl = @json(route('sidebar-notifications'));
    const pollIntervalMs = 15000;
    let previousCounts = null;

    function formatBadgeCount(count) {
        return count > 99 ? '99+' : String(count);
    }

    function updateSidebarBadges(counts) {
        Object.entries(counts).forEach(function (entry) {
            const key = entry[0];
            const count = entry[1];

            if (key === 'inbox_items') {
                return;
            }

            document.querySelectorAll('[data-sidebar-notification-badge="' + key + '"]').forEach(function (badge) {
                if (count > 0) {
                    badge.textContent = formatBadgeCount(count);
                    badge.classList.remove('hidden');

                    if (previousCounts !== null && (previousCounts[key] ?? 0) < count) {
                        badge.classList.add('animate-pulse');
                        window.setTimeout(function () {
                            badge.classList.remove('animate-pulse');
                        }, 2000);
                    }
                } else {
                    badge.classList.add('hidden');
                }
            });
        });

        if (Array.isArray(counts.inbox_items) && typeof window.__esewaanRenderHeaderInbox === 'function') {
            window.__esewaanRenderHeaderInbox(counts.inbox_items);
        }

        previousCounts = Object.assign({}, counts);
        delete previousCounts.inbox_items;
    }

    function poll() {
        fetch(pollUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('Poll failed');
                }

                return response.json();
            })
            .then(updateSidebarBadges)
            .catch(function () {
                // Ignore transient network errors; the next poll will retry.
            });
    }

    poll();
    window.setInterval(poll, pollIntervalMs);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            poll();
        }
    });
})();
</script>
@endif
