@extends('layouts.app')

@php
    $isEdit = isset($contract);
    $parent = $parentContract ?? null;
    $parentPremise = $parent?->premise;
    $isFollowUp = $isEdit && $contract->isFollowUpApplication() && $parent;
    $isLanjutan = $isFollowUp && $contract->isLanjutan();
    $lockKeluasan = $isLanjutan;
    $parentRent = $parentPremise?->kadar_sewa ?? $parent?->kadar_sewa_bulanan;

    $kadarSewaValue = old('kadar_sewa');
    if ($kadarSewaValue === null) {
        $kadarSewaValue = $contract?->premise?->kadar_sewa;
        if ($kadarSewaValue === null) {
            $contractRate = $contract?->kadar_sewa_bulanan;
            $kadarSewaValue = ($contractRate !== null && (float) $contractRate > 0)
                ? $contractRate
                : '';
        }
    }

    $keluasanValue = old('keluasan_mp');
    if ($keluasanValue === null) {
        $keluasanValue = $contract?->keluasan_mp;

        if ($keluasanValue === null && $isLanjutan) {
            $keluasanValue = $parent?->keluasan_mp;
        }

        $keluasanValue = $keluasanValue ?? '';
    }

    $keluasanKpsValue = filled($keluasanValue)
        ? number_format((float) $keluasanValue * 10.7639, 2, '.', '')
        : '';

    $sahSehinggaValue = old('sah_sehingga', $contract?->sah_sehingga?->format('Y-m-d') ?? '');
@endphp

@section('title', $isFollowUp ? 'Permohonan '.\App\Support\ApplicationCategories::label($contract->kategori_permohonan) : ($isEdit ? 'Kemaskini Permohonan' : 'Permohonan Baru'))
@section('header_title', $isFollowUp ? 'Permohonan '.\App\Support\ApplicationCategories::label($contract->kategori_permohonan) : ($isEdit ? 'Kemaskini Permohonan' : 'Permohonan Baru'))
@section('header_subtitle', $isFollowUp ? 'Lengkapkan maklumat baharu dan langkah tindakan untuk permohonan susulan' : ($isEdit ? 'Kemaskini maklumat borang dan langkah tindakan dalam satu halaman' : 'Isi maklumat borang permohonan sewaan baharu'))

@section('content')
<div>
    <form
        id="form-sewaan"
        method="POST"
        action="{{ $isEdit ? route('application.update', $contract) : route('application.store') }}"
        class="block space-y-6"
        @if($isEdit && isset($stepPanels))
            data-autosave-url="{{ route('application.autosave', $contract) }}"
            data-initial-step="{{ $initialStep ?? $step }}"
            data-active-steps="{{ json_encode($activeSteps) }}"
            data-remark-required="{{ $isFollowUp ? '1' : '0' }}"
        @endif
    >
        @csrf
        @if($isEdit)
            @method('PUT')
            @if(isset($stepPanels))
                <input type="hidden" name="current_step" id="current-step-input" value="{{ $initialStep ?? $step }}">
            @endif
        @endif

        @if($isFollowUp)
            <section class="glass-panel-muted overflow-hidden">
                <div class="border-b border-slate-200/80 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-800">Maklumat Kontrak Sedia Ada</h2>
                </div>
                <div class="px-6 py-5">
                    <div class="pointer-events-none grid grid-cols-1 gap-3 md:grid-cols-3 [&_p]:text-slate-500 [&_.glass-field-static]:border-slate-300/80 [&_.glass-field-static]:bg-slate-100 [&_.glass-field-static]:text-slate-800 [&_.glass-field-static]:shadow-none">
                        @include('partials.readonly-field', ['label' => 'Nama Premis Lama', 'value' => $parentPremise?->nama_ptj])
                        @include('partials.readonly-field', ['label' => 'Kadar Sewa Lama (RM)', 'value' => $parentRent !== null ? number_format((float) $parentRent, 2) : null])
                        @include('partials.readonly-field', ['label' => 'Sah Sehingga Lama', 'value' => $parent?->sah_sehingga?->format('d/m/Y')])
                        @include('partials.readonly-field', ['label' => 'Jenis Bangunan Lama', 'value' => \App\Support\BuildingTypes::all()[$parentPremise?->jenis_bangunan] ?? $parentPremise?->jenis_bangunan])
                        @include('partials.readonly-field', ['label' => 'Pemilik Lama', 'value' => $parentPremise?->nama_pemilik])
                        @include('partials.readonly-field', ['label' => 'Alamat Lama', 'value' => $parentPremise?->alamat_penuh, 'wrapperClass' => 'md:col-span-3', 'multiline' => true])
                    </div>
                </div>
            </section>
        @endif

        <section class="glass-card overflow-hidden">
            <div class="glass-divider border-b px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $isFollowUp ? 'Maklumat Baharu' : ($isEdit ? 'Kemaskini Borang Permohonan Sewaan' : 'Borang Permohonan Baru Sewaan') }}
                </h2>
                @if($isFollowUp)
                    <p class="mt-1 text-xs text-slate-600">Isi maklumat baharu untuk permohonan ini.</p>
                @endif
            </div>

            <div class="px-6 py-5">
                <div class="mx-auto max-w-4xl space-y-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="kategori_permohonan" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                            <span>Kategori Permohonan *</span>
                            @include('partials.form-field-hint', ['text' => $isEdit
                                ? 'Kategori dikunci selepas permohonan dicipta.'
                                : 'Permohonan baharu dikunci kepada kategori Baru. Pindah dan lanjutan dibuat dari senarai kontrak sewaan.'])
                        </label>
                        @php
                            $lockedKategori = $isEdit
                                ? $contract->kategori_permohonan
                                : \App\Support\ApplicationCategories::BARU;
                        @endphp
                        <select
                            id="kategori_permohonan"
                            required
                            disabled
                            class="glass-input w-full cursor-not-allowed rounded-xl px-3 py-2 text-sm opacity-70"
                        >
                            <option value="{{ $lockedKategori }}" selected>
                                {{ \App\Support\ApplicationCategories::label($lockedKategori) }}
                            </option>
                        </select>
                        <input type="hidden" name="kategori_permohonan" value="{{ $lockedKategori }}">
                        <p class="mt-1.5 text-xs glass-accent-text">
                            {{ $isEdit
                                ? 'Kategori permohonan dikunci selepas permohonan dicipta.'
                                : 'Permohonan baharu.' }}
                        </p>
                        @error('kategori_permohonan')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="negeri" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                            <span>Negeri *</span>
                            @include('partials.form-field-hint', ['text' => 'Negeri lokasi premis yang dipohon. Dikunci mengikut negeri pentadbir anda.'])
                        </label>
                        <select id="negeri" required disabled class="glass-input w-full cursor-not-allowed rounded-xl px-3 py-2 text-sm text-slate-600">
                            <option value="{{ $adminNegeri }}" selected>{{ $adminNegeri }}</option>
                        </select>
                        <input type="hidden" name="negeri" value="{{ old('negeri', $contract?->premise?->negeri ?? $adminNegeri) }}">
                        <p class="mt-1.5 text-xs glass-accent-text">Negeri dikunci mengikut negeri pentadbir anda.</p>
                        @error('negeri')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <svg class="h-4 w-4 glass-icon-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        <p class="text-sm font-semibold text-gray-700">Maklumat Premis</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="nama_ptj" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span>Nama Premis *</span>
                                @include('partials.form-field-hint', ['text' => 'Nama premis atau pejabat yang hendak disewa, contoh: AADK Daerah Pasir Puteh.'])
                            </label>
                            <input type="text" name="nama_ptj" id="nama_ptj" value="{{ old('nama_ptj', $contract?->premise?->nama_ptj) }}" required placeholder="Cth: AADK Daerah Pasir Puteh" class="glass-input w-full rounded-xl px-3 py-2 text-sm placeholder:text-slate-400">
                            @error('nama_ptj')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="jenis_bangunan" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span>Jenis Bangunan *</span>
                                @include('partials.form-field-hint', ['text' => 'Pilih kategori bangunan premis: kompleks kerajaan atau komersial.'])
                            </label>
                            <select name="jenis_bangunan" id="jenis_bangunan" required class="glass-input w-full rounded-xl px-3 py-2 text-sm">
                                <option value="">— Pilih jenis bangunan —</option>
                                @foreach($jenisList as $value => $label)
                                    <option value="{{ $value }}" @selected(old('jenis_bangunan', $contract?->premise?->jenis_bangunan) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('jenis_bangunan')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="kadar_sewa" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span>Kadar Sewa {{ $isFollowUp ? 'Baharu ' : '' }}(RM) *</span>
                                @include('partials.form-field-hint', ['text' => 'Kadar sewa bulanan premis dalam Ringgit Malaysia.'])
                            </label>
                            <input
                                type="number"
                                name="kadar_sewa"
                                id="kadar_sewa"
                                value="{{ $kadarSewaValue }}"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                                @if($isFollowUp) data-parent-rent="{{ $parentRent !== null ? (float) $parentRent : '' }}" @endif
                                class="glass-input w-full rounded-xl px-3 py-2 text-sm placeholder:text-slate-400"
                            >
                            @if($isFollowUp)
                                <p id="rent-diff-badge" class="mt-1.5 hidden text-xs font-medium" data-below-label="Perbezaan < RM500" data-above-label="Perbezaan ≥ RM500"></p>
                            @endif
                            @error('kadar_sewa')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="min-w-0">
                                    <label for="keluasan_mp" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        <span>Keluasan (mps) *</span>
                                        @include('partials.form-field-hint', ['text' => $lockKeluasan
                                            ? 'Keluasan dikunci mengikut kontrak sedia ada untuk permohonan lanjutan.'
                                            : 'Keluasan premis dalam meter persegi (mps). Akan ditukar automatik kepada kaki persegi (kps).'])
                                    </label>
                                    <input
                                        type="number"
                                        name="keluasan_mp"
                                        id="keluasan_mp"
                                        value="{{ $keluasanValue }}"
                                        step="0.01"
                                        min="0"
                                        required
                                        @readonly($lockKeluasan)
                                        placeholder="0.00"
                                        data-area-unit="mps"
                                        data-keluasan-locked="{{ $lockKeluasan ? '1' : '0' }}"
                                        class="glass-input w-full rounded-xl px-3 py-2 text-sm placeholder:text-slate-400 {{ $lockKeluasan ? 'cursor-not-allowed opacity-70' : '' }}"
                                    >
                                    @if($lockKeluasan)
                                        <p class="mt-1.5 text-xs glass-accent-text">Dikunci mengikut kontrak sedia ada.</p>
                                    @endif
                                    @error('keluasan_mp')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div class="min-w-0">
                                    <label for="keluasan_kps" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        <span>Keluasan (kps) *</span>
                                        @include('partials.form-field-hint', ['text' => $lockKeluasan
                                            ? 'Keluasan dikunci mengikut kontrak sedia ada untuk permohonan lanjutan.'
                                            : 'Keluasan premis dalam kaki persegi (kps). Akan ditukar automatik kepada meter persegi (mps). 1 mps ≈ 10.7639 kps.'])
                                    </label>
                                    <input
                                        type="number"
                                        id="keluasan_kps"
                                        value="{{ $keluasanKpsValue }}"
                                        step="0.01"
                                        min="0"
                                        required
                                        @readonly($lockKeluasan)
                                        placeholder="0.00"
                                        data-area-unit="kps"
                                        data-keluasan-locked="{{ $lockKeluasan ? '1' : '0' }}"
                                        class="glass-input w-full rounded-xl px-3 py-2 text-sm placeholder:text-slate-400 {{ $lockKeluasan ? 'cursor-not-allowed opacity-70' : '' }}"
                                    >
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="sah_sehingga" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span>Sah Sehingga {{ $isFollowUp ? 'Baharu ' : '' }}*</span>
                                @include('partials.form-field-hint', ['text' => 'Tarikh sah laku permohonan atau kelulusan berkaitan premis ini.'])
                            </label>
                            <input
                                type="date"
                                name="sah_sehingga"
                                id="sah_sehingga"
                                value="{{ $sahSehinggaValue }}"
                                required
                                class="glass-input w-full rounded-xl px-3 py-2 text-sm"
                            >
                            @error('sah_sehingga')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="alamat_penuh" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span>Alamat Penuh Premis *</span>
                                @include('partials.form-field-hint', ['text' => 'Alamat lengkap premis termasuk tingkat, jalan, bandar dan poskod.'])
                            </label>
                            <textarea name="alamat_penuh" id="alamat_penuh" rows="2" required placeholder="Cth: Tingkat 3, Plaza MARA Lot A-31 Jalan Kota." class="glass-input w-full resize-none rounded-xl px-3 py-2 text-sm placeholder:text-slate-400">{{ old('alamat_penuh', $contract?->premise?->alamat_penuh) }}</textarea>
                            @error('alamat_penuh')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <svg class="h-4 w-4 glass-icon-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <p class="text-sm font-semibold text-gray-700">Maklumat Pemilik</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="nama_pemilik" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span>Nama Pemilik *</span>
                                @include('partials.form-field-hint', ['text' => 'Nama pemilik premis atau agensi pemilik, contoh: Majlis Amanah Rakyat (MARA).'])
                            </label>
                            <input type="text" name="nama_pemilik" id="nama_pemilik" value="{{ old('nama_pemilik', $contract?->premise?->nama_pemilik) }}" required placeholder="Cth: Majlis Amanah Rakyat (MARA)" class="glass-input w-full rounded-xl px-3 py-2 text-sm placeholder:text-slate-400">
                            @error('nama_pemilik')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                @if($isFollowUp)
                    <div id="remark-field-section">
                        <div class="mb-3 flex items-center gap-2">
                            <svg class="h-4 w-4 glass-icon-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <p class="text-sm font-semibold text-gray-700">Remark</p>
                        </div>
                        <div>
                            <label for="remark" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span>Remark *</span>
                                @include('partials.form-field-hint', ['text' => 'Catatan wajib berkaitan permohonan '.strtolower(\App\Support\ApplicationCategories::label($contract->kategori_permohonan)).' ini.'])
                            </label>
                            <textarea name="remark" id="remark" rows="2" required placeholder="Masukkan remark..." class="glass-input w-full resize-none rounded-xl px-3 py-2 text-sm placeholder:text-slate-400">{{ old('remark', $contract?->remark) }}</textarea>
                            @error('remark')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            @if($contract->isAwaitingFollowUpRemark())
                                <p class="mt-1.5 text-xs font-medium text-amber-700">Remark diperlukan sebelum permohonan boleh dihantar ke Admin.</p>
                            @endif
                        </div>
                    </div>
                @endif
                </div>
            </div>
        </section>

        @if($isEdit && isset($stepPanels))
            <section class="glass-card overflow-hidden">
                @include('application.partials.proceed-panel')
            </section>
        @endif

        <section class="glass-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
            @if($isFollowUp)
                <button
                    type="submit"
                    form="cancel-follow-up-form"
                    class="text-sm font-medium text-red-600 transition-colors hover:text-red-700"
                    onclick="return confirm('Batalkan permohonan {{ \App\Support\ApplicationCategories::label($contract->kategori_permohonan) }} ini dan kembali ke kontrak asal? Semua maklumat yang dimasukkan akan dibuang.');"
                >
                    Batal permohonan susulan
                </button>
            @else
                <span></span>
            @endif

            <div class="flex flex-wrap items-center justify-end gap-3">
            @if($isEdit && isset($stepPanels))
                @if($contract->isAwaitingFollowUpRemark())
                    <p class="text-sm font-medium text-amber-700">Sila isi Remark sebelum menghantar ke Admin.</p>
                @endif

                <p id="form-save-status" class="mr-auto hidden text-xs text-slate-500" aria-live="polite"></p>

                <button type="button" id="form-save-button" class="glass-btn-secondary rounded-xl px-5 py-2 text-sm font-medium disabled:opacity-60">
                    Seterusnya
                </button>

                <div id="submit-hq-wrap" @class(['contents' => $contract->isReadyToSendToHq(), 'hidden' => ! $contract->isReadyToSendToHq()])>
                    <button
                        type="button"
                        id="submit-hq-button"
                        class="status-confirm-trigger glass-btn-primary rounded-xl px-5 py-2 text-sm font-medium"
                        data-confirm-title="Hantar Permohonan ke Admin"
                        data-confirm-message="Anda pasti mahu menghantar permohonan ini kepada Admin untuk semakan? Sila semak maklumat premis sebelum meneruskan."
                        data-confirm-form="submit-hq-form"
                        data-confirm-button="Ya, Hantar"
                        data-confirm-tone="success"
                    >
                        Hantar ke Admin
                    </button>
                </div>
            @else
            <button type="submit" class="glass-btn-primary rounded-xl px-5 py-2 text-sm font-medium disabled:opacity-60">
                @if($isEdit)
                    Simpan
                @else
                    Teruskan ke Tindakan
                @endif
            </button>
            @endif
            </div>
            </div>
        </section>
    </form>

    @if($isEdit && isset($stepPanels))
    <form id="submit-hq-form" method="POST" action="{{ route('status-permohonan.submit-hq', $contract) }}" class="hidden">
        @csrf
    </form>
    @endif

    @if($isFollowUp)
    <form id="cancel-follow-up-form" method="POST" action="{{ route('kontrak-sewaan.follow-up.cancel', $contract) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endif
</div>

@include('application.partials.form-required-field-scripts')
@include('application.partials.keluasan-conversion-script')

@if($isEdit && isset($stepPanels))
@include('partials.confirm-action-modal')
@include('application.partials.proceed-scripts', [
    'formId' => 'form-sewaan',
    'saveUrl' => route('admin-proceed.update', $contract),
    'redirectWhenComplete' => route('status-permohonan.index'),
    'useFullFormAutosave' => true,
])
@push('scripts')
<script>
(function () {
    const form = document.getElementById('form-sewaan');
    window.ApplicationFormValidation?.bind(form);

    const modal = document.getElementById('confirm-action-modal');
    const modalTitle = document.getElementById('confirm-action-title');
    const modalMessage = document.getElementById('confirm-action-message');
    const modalDetailsWrap = document.getElementById('confirm-action-details-wrap');
    const modalWithdrawalReasonRow = document.getElementById('confirm-action-withdrawal-reason-row');
    const modalWithdrawalReason = document.getElementById('confirm-action-withdrawal-reason');
    const modalConfirm = document.getElementById('confirm-action-confirm');
    const modalCancel = document.getElementById('confirm-action-cancel');
    const modalIcon = document.getElementById('confirm-action-icon');
    const formSaveStatus = document.getElementById('form-save-status');
    let pendingForm = null;

    const iconTemplates = {
        success: '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
    };

    function closeConfirmModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        pendingForm = null;
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

    function showFormStatus(message) {
        if (!formSaveStatus) return;
        formSaveStatus.textContent = message;
        formSaveStatus.classList.remove('hidden');
        window.setTimeout(() => formSaveStatus.classList.add('hidden'), 3000);
    }

    document.querySelectorAll('.status-confirm-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const targetForm = document.getElementById(trigger.dataset.confirmForm);
            if (!targetForm || !modal || !modalConfirm || !modalTitle || !modalMessage) return;

            if (trigger.id === 'submit-hq-button' && form && !window.ApplicationFormValidation?.validate(form)) {
                showFormStatus('Sila lengkapkan semua medan bertanda biru/merah sebelum menghantar ke HQ.');
                return;
            }

            modalTitle.textContent = trigger.dataset.confirmTitle || 'Sahkan Tindakan';
            modalMessage.textContent = trigger.dataset.confirmMessage || '';
            setConfirmDetails(trigger);
            modalConfirm.textContent = trigger.dataset.confirmButton || 'Sahkan';
            modalConfirm.className = 'glass-btn-success rounded-xl px-4 py-2 text-sm font-medium';

            if (modalIcon) {
                modalIcon.className = 'mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600';
                modalIcon.innerHTML = iconTemplates.success;
            }

            pendingForm = targetForm;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            modalConfirm.focus();
        });
    });

    modalCancel?.addEventListener('click', closeConfirmModal);
    modalConfirm?.addEventListener('click', () => {
        pendingForm?.submit();
        closeConfirmModal();
    });
    modal?.querySelectorAll('[data-confirm-dismiss]').forEach((el) => el.addEventListener('click', closeConfirmModal));
})();
</script>
@endpush
@else
@push('scripts')
<script>
(function () {
    const form = document.getElementById('form-sewaan');
    window.ApplicationFormValidation?.bind(form);

    form?.addEventListener('submit', (event) => {
        if (!window.ApplicationFormValidation?.validate(form)) {
            event.preventDefault();
        }
    });
})();
</script>
@endpush
@endif

@if($isFollowUp)
<script>
    (function () {
        const input = document.getElementById('kadar_sewa');
        const badge = document.getElementById('rent-diff-badge');
        if (!input || !badge) {
            return;
        }

        const parentRent = parseFloat(input.dataset.parentRent);
        if (isNaN(parentRent)) {
            return;
        }

        const threshold = 500;
        const format = (value) => value.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const update = () => {
            const value = parseFloat(input.value);
            if (isNaN(value)) {
                badge.classList.add('hidden');
                return;
            }

            const difference = value - parentRent;
            const magnitude = Math.abs(difference);
            const isBelow = magnitude < threshold;
            const label = isBelow ? badge.dataset.belowLabel : badge.dataset.aboveLabel;
            const sign = difference > 0 ? '+' : (difference < 0 ? '−' : '');

            badge.textContent = `${label} — Lama RM${format(parentRent)} → Baharu RM${format(value)} (${sign}RM${format(magnitude)})`;
            badge.classList.remove('hidden', 'text-emerald-700', 'text-amber-700');
            badge.classList.add(isBelow ? 'text-emerald-700' : 'text-amber-700');
        };

        input.addEventListener('input', update);
        update();
    })();
</script>
@endif

@if($isFollowUp && session('error'))
<script>
    document.getElementById('remark-field-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
</script>
@endif
@endsection
