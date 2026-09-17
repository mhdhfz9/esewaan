<div id="proceed-section">
    <div class="glass-divider border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Langkah Tindakan</h2>
        <p class="mt-1 text-xs text-slate-600">
            Lengkapkan semua langkah tindakan sebelum menghantar permohonan kepada Ibu Pejabat.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(240px,280px)_1fr]">
        <aside class="glass-divider border-b px-4 py-5 sm:px-5 lg:border-b-0 lg:border-r lg:py-6">
            @include('partials.admin-proceed-stepper', [
                'contract' => $contract,
                'step' => $initialStep ?? $step,
                'progress' => $progress,
                'completedCount' => $completedCount,
                'activeSteps' => $activeSteps,
                'clientNavigation' => true,
            ])
        </aside>

        <div class="min-w-0 px-6 py-6">
            @foreach($stepPanels as $panel)
                <div
                    id="proceed-step-panel-{{ $panel['number'] }}"
                    class="proceed-step-panel mx-auto max-w-2xl {{ ($initialStep ?? $step) === $panel['number'] ? '' : 'hidden' }}"
                    data-step="{{ $panel['number'] }}"
                    data-step-key="{{ $panel['key'] }}"
                    @if($panel['has_agency_checklist']) data-requires-agencies="1" @endif
                    @if($panel['key'] === \App\Support\AdminProceedSteps::STEP_BORANG_JRP) data-confirmations-complete-step="1" @endif
                >
                    <div class="mb-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Langkah {{ $panel['position'] }} / {{ $totalActiveSteps }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $panel['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $panel['description'] }}</p>
                    </div>

                    @if($panel['has_agency_checklist'])
                        <div class="proceed-agency-checklist glass-subtle mb-5 rounded-2xl border border-slate-300 p-4">
                            <div class="space-y-2.5">
                                @foreach(\App\Support\AdminProceedSteps::agencyDefinitions() as $agencyKey => $agencyLabel)
                                    @php
                                        $agencyChecked = filter_var(
                                            old('proceed_steps.'.$panel['key'].'.agencies.'.$agencyKey, $panel['agencies'][$agencyKey] ?? false),
                                            FILTER_VALIDATE_BOOLEAN
                                        );
                                        $agencyRequiresDate = \App\Support\AdminProceedSteps::agencyRequiresDate($agencyKey);
                                        $agencyDate = old(
                                            'proceed_steps.'.$panel['key'].'.agency_dates.'.$agencyKey,
                                            $panel['agency_dates'][$agencyKey] ?? null
                                        );
                                    @endphp
                                    <div class="flex flex-col gap-2 rounded-xl border border-slate-300 bg-white/50 px-3 py-2 transition-colors hover:border-slate-400 hover:bg-white/70 sm:flex-row sm:items-center sm:justify-between">
                                        <label class="flex min-w-0 cursor-pointer items-start gap-3">
                                            <input
                                                type="checkbox"
                                                name="proceed_steps[{{ $panel['key'] }}][agencies][{{ $agencyKey }}]"
                                                value="1"
                                                @checked($agencyChecked)
                                                class="proceed-agency-checkbox mt-0.5 h-4 w-4 shrink-0 rounded border-white/60 text-slate-800 focus:ring-slate-500/30"
                                                data-agency-key="{{ $agencyKey }}"
                                                @if($agencyRequiresDate) data-requires-date="1" @endif
                                            >
                                            <span class="text-sm text-slate-700">{{ $agencyLabel }}</span>
                                        </label>
                                        @if($agencyRequiresDate)
                                            <div
                                                class="proceed-agency-date-wrap ml-7 sm:ml-0 {{ $agencyChecked ? '' : 'hidden' }}"
                                                data-agency-date-for="{{ $agencyKey }}"
                                            >
                                                <label class="sr-only" for="proceed_agency_date_{{ $panel['key'] }}_{{ $agencyKey }}">Tarikh surat {{ $agencyLabel }}</label>
                                                <input
                                                    type="date"
                                                    id="proceed_agency_date_{{ $panel['key'] }}_{{ $agencyKey }}"
                                                    name="proceed_steps[{{ $panel['key'] }}][agency_dates][{{ $agencyKey }}]"
                                                    value="{{ $agencyDate }}"
                                                    class="proceed-agency-date glass-input w-full rounded-lg px-2.5 py-1.5 text-sm sm:w-40"
                                                    data-agency-key="{{ $agencyKey }}"
                                                    @disabled(! $agencyChecked)
                                                >
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <p class="proceed-agency-hint mt-3 hidden text-xs text-amber-700">Sila tandakan semua agensi di atas sebelum mengesahkan langkah ini.</p>
                        </div>
                    @endif

                    @if($panel['requires_reference'] ?? false)
                        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="proceed_no_rujukan_{{ $panel['key'] }}" class="mb-1.5 block text-sm font-medium text-slate-700">No. Rujukan *</label>
                                <input
                                    type="text"
                                    name="proceed_steps[{{ $panel['key'] }}][no_rujukan]"
                                    id="proceed_no_rujukan_{{ $panel['key'] }}"
                                    value="{{ old('proceed_steps.'.$panel['key'].'.no_rujukan', $panel['no_rujukan'] ?? '') }}"
                                    required
                                    maxlength="100"
                                    placeholder="Cth: AADK/BKP/PB 200-3/02"
                                    class="proceed-step-no-rujukan glass-input w-full rounded-xl px-3 py-2 text-sm"
                                >
                            </div>
                            <div>
                                <label for="proceed_tarikh_surat_{{ $panel['key'] }}" class="mb-1.5 block text-sm font-medium text-slate-700">Tarikh Surat *</label>
                                <input
                                    type="date"
                                    name="proceed_steps[{{ $panel['key'] }}][tarikh_surat]"
                                    id="proceed_tarikh_surat_{{ $panel['key'] }}"
                                    value="{{ old('proceed_steps.'.$panel['key'].'.tarikh_surat', $panel['tarikh_surat'] ?? '') }}"
                                    required
                                    class="proceed-step-tarikh-surat glass-input w-full rounded-xl px-3 py-2 text-sm"
                                >
                            </div>
                        </div>
                    @endif

                    <div class="mb-5 space-y-3">
                        <label class="proceed-step-checkbox-label glass-subtle flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-300 px-4 py-4 transition-all hover:border-slate-400 hover:bg-white/45">
                            <input
                                type="checkbox"
                                name="proceed_steps[{{ $panel['key'] }}][confirmed_accurate]"
                                value="1"
                                @checked(old('proceed_steps.'.$panel['key'].'.confirmed_accurate', $panel['confirmed_accurate'] ?? false))
                                class="proceed-step-confirmed-accurate mt-0.5 h-4 w-4 rounded border-white/60 text-slate-800 focus:ring-slate-500/30"
                            >
                            <span class="text-sm text-slate-700">
                                <span class="font-medium text-slate-900">
                                    @if($panel['position'] === 1)
                                        Saya mengesahkan surat niat telah dihantar ke premis
                                    @elseif($panel['key'] === \App\Support\AdminProceedSteps::STEP_BORANG_JRP)
                                        Saya mengesahkan borang JRP telah diisi dan dimuat naik ke PROMIS
                                    @else
                                        Saya mengesahkan maklumat ini tepat dan benar
                                    @endif
                                </span>
                            </span>
                        </label>

                        <label class="proceed-step-checkbox-label glass-subtle flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-300 px-4 py-4 transition-all hover:border-slate-400 hover:bg-white/45">
                            <input
                                type="checkbox"
                                name="proceed_steps[{{ $panel['key'] }}][confirmed_promis]"
                                value="1"
                                @checked(old('proceed_steps.'.$panel['key'].'.confirmed_promis', $panel['confirmed_promis'] ?? false))
                                class="proceed-step-confirmed-promis mt-0.5 h-4 w-4 rounded border-white/60 text-slate-800 focus:ring-slate-500/30"
                            >
                            <span class="text-sm text-slate-700">
                                <span class="font-medium text-slate-900">
                                    @if($panel['position'] === 1)
                                        Surat tawaran pemilik premis telah diterima dan dimuat naik ke PROMIS
                                    @elseif($panel['key'] === \App\Support\AdminProceedSteps::STEP_BORANG_JRP)
                                        Saya mengesahkan maklumat ini tepat dan benar
                                    @else
                                        Maklumat ini telah dimuat naik ke dalam sistem PROMIS.
                                    @endif
                                </span>
                            </span>
                        </label>
                    </div>

                    @if($panel['key'] !== \App\Support\AdminProceedSteps::STEP_BORANG_JRP)
                    <label class="proceed-step-checkbox-label glass-subtle flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-300 px-4 py-4 transition-all hover:border-slate-400 hover:bg-white/45">
                        <input
                            type="checkbox"
                            name="proceed_steps[{{ $panel['key'] }}][completed]"
                            value="1"
                            @checked(old('proceed_steps.'.$panel['key'].'.completed', $panel['marked_complete'] ?? $panel['completed']))
                            class="proceed-step-completed mt-0.5 h-4 w-4 rounded border-white/60 text-slate-800 focus:ring-slate-500/30"
                        >
                        <span class="text-sm text-slate-700">
                            <span class="font-medium text-slate-900">Saya mengesahkan langkah ini telah selesai dilaksanakan.</span>
                        </span>
                    </label>
                    @endif

                    <div class="mt-5">
                        <label for="proceed_notes_{{ $panel['key'] }}" class="mb-1.5 block text-sm font-medium text-slate-700">Catatan</label>
                        <textarea
                            name="proceed_steps[{{ $panel['key'] }}][notes]"
                            id="proceed_notes_{{ $panel['key'] }}"
                            rows="3"
                            class="proceed-step-notes glass-input w-full rounded-xl px-3 py-2 text-sm"
                            placeholder="Masukkan catatan untuk langkah ini (pilihan)..."
                        >{{ old('proceed_steps.'.$panel['key'].'.notes', $panel['notes']) }}</textarea>
                    </div>
                </div>
            @endforeach

            <p id="proceed-save-status" class="mx-auto mt-4 max-w-2xl hidden text-xs text-slate-500" aria-live="polite"></p>
        </div>
    </div>
</div>
