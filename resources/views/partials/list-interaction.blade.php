<script>
@verbatim
(function () {
    if (window.EsewaanListInteraction) {
        return;
    }

    window.EsewaanListInteraction = {
        attachHoverGuard(listElement, onDeferredRefresh) {
            if (! listElement) {
                return {
                    isHovered: () => false,
                    deferRefresh(refreshFn) {
                        refreshFn?.();

                        return false;
                    },
                };
            }

            let isHovered = false;
            let pendingRefresh = false;

            listElement.addEventListener('mouseenter', () => {
                isHovered = true;
            });

            listElement.addEventListener('mouseleave', () => {
                isHovered = false;

                if (pendingRefresh) {
                    pendingRefresh = false;
                    onDeferredRefresh?.();
                }
            });

            return {
                isHovered: () => isHovered,
                deferRefresh(refreshFn) {
                    if (isHovered) {
                        pendingRefresh = true;

                        return true;
                    }

                    refreshFn?.();

                    return false;
                },
            };
        },

        createFingerprintPoller(options) {
            const {
                syncUrl,
                buildParams = () => new URLSearchParams(),
                onFingerprintChange,
                intervalMs = 15000,
                shouldPoll = () => true,
                deferRefresh = (refreshFn) => {
                    refreshFn?.();

                    return false;
                },
            } = options;

            let listFingerprint = null;
            let pollController = null;

            function pollListState() {
                if (! syncUrl) {
                    return;
                }

                if (pollController) {
                    pollController.abort();
                }

                pollController = new AbortController();

                fetch(`${syncUrl}?${buildParams().toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-Background-Request': '1',
                    },
                    signal: pollController.signal,
                })
                    .then((response) => {
                        if (! response.ok) {
                            throw new Error('Gagal menyemak kemas kini senarai.');
                        }

                        return response.json();
                    })
                    .then((data) => {
                        const nextFingerprint = data.fingerprint ?? null;

                        if (listFingerprint !== null && nextFingerprint !== null && nextFingerprint !== listFingerprint) {
                            deferRefresh(onFingerprintChange);

                            return;
                        }

                        listFingerprint = nextFingerprint;
                    })
                    .catch((error) => {
                        if (error.name !== 'AbortError') {
                            console.error(error);
                        }
                    });
            }

            function startPolling() {
                pollListState();

                window.setInterval(() => {
                    if (document.visibilityState !== 'visible') {
                        return;
                    }

                    if (! shouldPoll()) {
                        return;
                    }

                    pollListState();
                }, intervalMs);

                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        pollListState();
                    }
                });
            }

            return {
                poll: pollListState,
                start: startPolling,
                resetFingerprint() {
                    listFingerprint = null;
                },
            };
        },
    };
})();
@endverbatim
</script>
