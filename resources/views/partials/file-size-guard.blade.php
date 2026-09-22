{{--
    Client-side file size guard. Stops oversized uploads before the request hits the server.
    Usage: @include('partials.file-size-guard', ['inputId' => 'draft-document', 'errorId' => 'draft-document-size-error'])
--}}
@php
    $maxKilobytes = \App\Support\UploadLimits::effectiveMaxKilobytes();
    $maxBytes = $maxKilobytes * 1024;
    $humanLimit = \App\Support\UploadLimits::humanEffectiveLimit();
    $inputId = $inputId ?? 'document';
    $errorId = $errorId ?? $inputId.'-size-error';
    $formId = $formId ?? null;
@endphp

<p id="{{ $errorId }}" class="mt-1 hidden text-xs font-medium text-red-600" role="alert" aria-live="polite"></p>

<script>
(function () {
    const input = document.getElementById(@json($inputId));
    const errorEl = document.getElementById(@json($errorId));
    const maxBytes = {{ (int) $maxBytes }};
    const humanLimit = @json($humanLimit);
    const form = @json($formId) ? document.getElementById(@json($formId)) : input?.closest('form');

    if (! input || ! errorEl) {
        return;
    }

    function clearError() {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
        input.setCustomValidity('');
    }

    function showError(message) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
        input.setCustomValidity(message);
    }

    function validateSelectedFile() {
        clearError();

        const file = input.files?.[0];
        if (! file) {
            return true;
        }

        if (file.size > maxBytes) {
            const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
            showError('Fail terlalu besar (' + sizeMb + 'MB). Had maksimum ialah ' + humanLimit + '. Sila pilih fail yang lebih kecil.');
            input.value = '';
            return false;
        }

        return true;
    }

    input.addEventListener('change', validateSelectedFile);

    form?.addEventListener('submit', function (event) {
        if (! validateSelectedFile()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            input.focus();
        }
    }, true);
})();
</script>
