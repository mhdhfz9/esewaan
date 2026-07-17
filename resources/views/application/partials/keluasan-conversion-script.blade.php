@push('scripts')
<script>
(function () {
    const MPS_TO_KPS = 10.7639;
    const form = document.getElementById('form-sewaan');
    const mpsInput = document.getElementById('keluasan_mp');
    const kpsInput = document.getElementById('keluasan_kps');

    if (!mpsInput || !kpsInput) {
        return;
    }

    const isLocked = mpsInput.dataset.keluasanLocked === '1'
        || kpsInput.dataset.keluasanLocked === '1';

    let syncing = false;

    function formatArea(value) {
        if (!Number.isFinite(value)) {
            return '';
        }

        return (Math.round(value * 100) / 100).toFixed(2);
    }

    function parseArea(raw) {
        const value = parseFloat(String(raw ?? '').trim());

        return Number.isFinite(value) ? value : null;
    }

    function refreshRequiredState() {
        window.ApplicationFormValidation?.refresh?.(form);
    }

    function convertFromMps() {
        if (syncing) {
            return;
        }

        syncing = true;
        const mps = parseArea(mpsInput.value);
        kpsInput.value = mps === null ? '' : formatArea(mps * MPS_TO_KPS);
        syncing = false;
        refreshRequiredState();
    }

    function convertFromKps() {
        if (syncing || isLocked) {
            return;
        }

        syncing = true;
        const kps = parseArea(kpsInput.value);
        mpsInput.value = kps === null ? '' : formatArea(kps / MPS_TO_KPS);

        mpsInput.dispatchEvent(new Event('input', { bubbles: true }));
        mpsInput.dispatchEvent(new Event('change', { bubbles: true }));
        syncing = false;
        refreshRequiredState();
    }

    if (!isLocked) {
        mpsInput.addEventListener('input', convertFromMps);
        mpsInput.addEventListener('change', convertFromMps);
        kpsInput.addEventListener('input', convertFromKps);
        kpsInput.addEventListener('change', convertFromKps);
    }

    if ((mpsInput.value || '').trim() !== '' && (kpsInput.value || '').trim() === '') {
        convertFromMps();
    } else {
        refreshRequiredState();
    }
})();
</script>
@endpush
