<div
    id="global-loading-overlay"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="global-loading-title"
    aria-describedby="global-loading-message"
    aria-hidden="true"
>
    <div id="global-loading-backdrop"></div>
    <div id="global-loading-panel-wrap">
        <div id="global-loading-panel">
            <div id="global-loading-spinner" aria-hidden="true"></div>
            <p id="global-loading-title">Memuatkan</p>
            <p id="global-loading-message">Sila tunggu sebentar. Jangan tutup atau muat semula halaman.</p>
        </div>
    </div>
</div>

<style>
    #global-loading-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2147483646;
        pointer-events: auto;
    }

    #global-loading-overlay.is-visible {
        display: block;
    }

    #global-loading-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.62);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }

    #global-loading-panel-wrap {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    #global-loading-panel {
        min-width: 18rem;
        max-width: 22rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(255, 255, 255, 0.85);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
        padding: 1.75rem 1.5rem;
        text-align: center;
    }

    #global-loading-spinner {
        width: 3rem;
        height: 3rem;
        margin: 0 auto 1rem;
        border-radius: 9999px;
        border: 4px solid rgba(148, 163, 184, 0.35);
        border-top-color: #334155;
        animation: global-loading-spin 0.75s linear infinite;
    }

    #global-loading-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #0f172a;
    }

    #global-loading-message {
        margin: 0.45rem 0 0;
        font-size: 0.78rem;
        line-height: 1.45;
        color: #64748b;
    }

    body.global-loading-active {
        overflow: hidden !important;
        cursor: wait;
    }

    body.global-loading-active > *:not(#global-loading-overlay) {
        pointer-events: none !important;
        user-select: none !important;
    }

    @keyframes global-loading-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<script>
(function () {
    let activeRequests = 0;

    function overlayElement() {
        return document.getElementById('global-loading-overlay');
    }

    function shouldSkipFormLoader(form) {
        if (form?.dataset?.noGlobalLoader !== undefined) {
            return true;
        }

        const action = (form?.getAttribute('action') || '').toLowerCase();

        return action.includes('/login') || action.includes('/logout');
    }

    function renderOverlay() {
        const overlay = overlayElement();

        if (! overlay) {
            return;
        }

        const isVisible = activeRequests > 0;

        overlay.classList.toggle('is-visible', isVisible);
        overlay.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
        document.body.classList.toggle('global-loading-active', isVisible);
        document.body.setAttribute('aria-busy', isVisible ? 'true' : 'false');
    }

    function showGlobalLoading() {
        activeRequests += 1;
        renderOverlay();
    }

    function hideGlobalLoading() {
        activeRequests = Math.max(0, activeRequests - 1);
        renderOverlay();
    }

    function resetGlobalLoading() {
        activeRequests = 0;
        renderOverlay();
    }

    function blockLoadingKeys(event) {
        if (activeRequests <= 0) {
            return;
        }

        const key = event.key;

        if (key === 'F5' || ((event.ctrlKey || event.metaKey) && (key === 'r' || key === 'R'))) {
            event.preventDefault();
        }

        if (key === 'Escape' || key === 'Tab') {
            event.preventDefault();
        }
    }

    window.AppLoading = {
        show: showGlobalLoading,
        hide: hideGlobalLoading,
        reset: resetGlobalLoading,
    };

    document.addEventListener('keydown', blockLoadingKeys, true);

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || shouldSkipFormLoader(form)) {
            return;
        }

        showGlobalLoading();
    }, true);

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || shouldSkipFormLoader(form)) {
            return;
        }

        if (event.defaultPrevented) {
            hideGlobalLoading();
        }
    }, false);

    const nativeFormSubmit = HTMLFormElement.prototype.submit;

    HTMLFormElement.prototype.submit = function () {
        if (! shouldSkipFormLoader(this)) {
            showGlobalLoading();
        }

        return nativeFormSubmit.call(this);
    };

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            resetGlobalLoading();
        }
    });
})();
</script>
