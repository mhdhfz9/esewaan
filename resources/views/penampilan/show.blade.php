@extends('layouts.app')

@section('title', 'Penampilan')
@section('header_title', 'Penampilan')
@section('header_subtitle', 'Sesuaikan rupa antara muka mengikut keperluan anda')

@section('content')
<div class="mx-auto max-w-3xl space-y-6" id="appearance-settings" data-appearance-page>
    <div class="glass-card p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900">Tema</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih mod warna untuk seluruh sistem.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Tema">
            <button type="button" class="appearance-option" data-appearance-key="theme" data-appearance-value="light" aria-pressed="false">
                <span class="appearance-option-title">Cerah</span>
                <span class="appearance-option-desc">Latar terang (Default)</span>
            </button>
            <button type="button" class="appearance-option" data-appearance-key="theme" data-appearance-value="dark" aria-pressed="false">
                <span class="appearance-option-title">Gelap</span>
                <span class="appearance-option-desc">Mod malam</span>
            </button>
            <button type="button" class="appearance-option" data-appearance-key="theme" data-appearance-value="system" aria-pressed="false">
                <span class="appearance-option-title">Sistem</span>
                <span class="appearance-option-desc">Ikut tetapan peranti</span>
            </button>
        </div>
    </div>

    <div class="glass-card p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900">Saiz fon</h2>
            <p class="mt-1 text-sm text-slate-500">Besarkan atau kecilkan teks di seluruh aplikasi.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" role="radiogroup" aria-label="Saiz fon">
            <button type="button" class="appearance-option" data-appearance-key="fontSize" data-appearance-value="sm" aria-pressed="false">
                <span class="appearance-option-title">Kecil</span>
                <span class="appearance-option-desc">14px</span>
            </button>
            <button type="button" class="appearance-option" data-appearance-key="fontSize" data-appearance-value="md" aria-pressed="false">
                <span class="appearance-option-title">Sederhana</span>
                <span class="appearance-option-desc">16px (Default)</span>
            </button>
            <button type="button" class="appearance-option" data-appearance-key="fontSize" data-appearance-value="lg" aria-pressed="false">
                <span class="appearance-option-title">Besar</span>
                <span class="appearance-option-desc">18px</span>
            </button>
            <button type="button" class="appearance-option" data-appearance-key="fontSize" data-appearance-value="xl" aria-pressed="false">
                <span class="appearance-option-title">Sangat besar</span>
                <span class="appearance-option-desc">20px</span>
            </button>
        </div>
    </div>

    <div class="glass-card p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900">Kontras</h2>
            <p class="mt-1 text-sm text-slate-500">Tingkatkan kebolehbacaan teks dan sempadan.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Kontras">
            <button type="button" class="appearance-option" data-appearance-key="contrast" data-appearance-value="normal" aria-pressed="false">
                <span class="appearance-option-title">Kontras biasa</span>
                <span class="appearance-option-desc">Gaya kaca standard</span>
            </button>
            <button type="button" class="appearance-option" data-appearance-key="contrast" data-appearance-value="high" aria-pressed="false">
                <span class="appearance-option-title">Kontras tinggi</span>
                <span class="appearance-option-desc">Teks &amp; sempadan lebih kuat</span>
            </button>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500">Tetapan disimpan pada peranti ini sahaja.</p>
        <button type="button" id="appearance-reset" class="glass-btn-secondary rounded-xl px-4 py-2 text-sm font-medium">
            Reset ke Default
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    if (! window.EsewaanAppearance) {
        return;
    }

    var api = window.EsewaanAppearance;
    var root = document.querySelector('[data-appearance-page]');
    if (! root) {
        return;
    }

    function syncUi(prefs) {
        root.querySelectorAll('[data-appearance-key]').forEach(function (btn) {
            var key = btn.getAttribute('data-appearance-key');
            var value = btn.getAttribute('data-appearance-value');
            var selected = String(prefs[key]) === value;
            btn.classList.toggle('is-selected', selected);
            btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    }

    syncUi(api.read());

    root.addEventListener('click', function (event) {
        var option = event.target.closest('[data-appearance-key]');
        if (! option) {
            return;
        }

        var prefs = api.read();
        prefs[option.getAttribute('data-appearance-key')] = option.getAttribute('data-appearance-value');
        syncUi(api.save(prefs));
    });

    document.getElementById('appearance-reset')?.addEventListener('click', function () {
        syncUi(api.reset());
    });
})();
</script>
@endpush
