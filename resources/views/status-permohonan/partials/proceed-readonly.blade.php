@php
    $totalActiveSteps = $totalActiveSteps ?? count($stepPanels ?? []);
    $completedCount = $completedCount ?? 0;
    $progressPercent = $totalActiveSteps > 0 ? (int) round(($completedCount / $totalActiveSteps) * 100) : 0;
    $compact = $compact ?? false;
@endphp

<div @class(['space-y-6' => ! $compact, 'space-y-3' => $compact])>
    <div>
        <div class="mb-2 flex items-center justify-between text-xs">
            <span class="font-medium text-slate-700">Kemajuan Langkah Tindakan</span>
            <span @class(['font-semibold text-emerald-700' => $progressPercent >= 100, 'text-slate-500' => $progressPercent < 100])>
                {{ $completedCount }} / {{ $totalActiveSteps }} langkah ({{ $progressPercent }}%)
            </span>
        </div>
        <div @class(['overflow-hidden rounded-full glass-progress-track', 'h-1.5' => ! $compact, 'h-1' => $compact])>
            <div
                @class([
                    'h-full rounded-full transition-all duration-300',
                    'glass-progress-fill-emerald' => $progressPercent >= 100,
                    'glass-progress-fill-amber' => $progressPercent > 0 && $progressPercent < 100,
                    'glass-progress-fill' => $progressPercent === 0,
                ])
                style="width: {{ $progressPercent }}%"
            ></div>
        </div>
    </div>

    <div @class(['space-y-5' => ! $compact, 'space-y-2' => $compact])>
        @foreach($stepPanels as $panel)
            <section @class([
                'glass-subtle rounded-2xl border border-slate-200/60',
                'p-5' => ! $compact,
                'rounded-xl p-3' => $compact,
            ])>
                <div @class(['mb-4 flex flex-wrap items-start justify-between gap-3' => ! $compact, 'mb-2 flex flex-wrap items-start justify-between gap-2' => $compact])>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                            Langkah {{ $panel['position'] }} / {{ $totalActiveSteps }}
                        </p>
                        <h3 @class(['mt-1 font-semibold text-slate-900', 'text-base' => ! $compact, 'text-sm' => $compact])>{{ $panel['title'] }}</h3>
                        @unless($compact)
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $panel['description'] }}</p>
                        @endunless
                    </div>
                    <span @class([
                        'inline-flex shrink-0 items-center rounded-lg px-2.5 py-1 text-xs font-medium',
                        'bg-emerald-100 text-emerald-800' => $panel['completed'],
                        'bg-amber-100 text-amber-800' => ! $panel['completed'],
                    ])>
                        {{ $panel['completed'] ? 'Selesai' : 'Belum selesai' }}
                    </span>
                </div>

                @if($panel['has_agency_checklist'])
                    <div @class(['mb-4 rounded-xl border border-slate-300 bg-white/40 p-4' => ! $compact, 'mb-2 rounded-lg border border-slate-300 bg-white/40 p-2' => $compact])>
                        <div @class(['space-y-2' => ! $compact, 'space-y-1' => $compact])>
                            @foreach(\App\Support\AdminProceedSteps::agencyDefinitions() as $agencyKey => $agencyLabel)
                                @php
                                    $agencyChecked = ($panel['agencies'][$agencyKey] ?? false) === true;
                                    $agencyRequiresDate = \App\Support\AdminProceedSteps::agencyRequiresDate($agencyKey);
                                    $agencyDate = $panel['agency_dates'][$agencyKey] ?? null;
                                @endphp
                                <div class="flex flex-col gap-2 rounded-lg border border-slate-300 bg-white/50 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                                    <label class="flex min-w-0 items-start gap-3">
                                        <input
                                            type="checkbox"
                                            disabled
                                            @checked($agencyChecked)
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-slate-800"
                                        >
                                        <span @class(['text-sm', 'text-slate-800' => $agencyChecked, 'text-slate-500' => ! $agencyChecked])>
                                            {{ $agencyLabel }}
                                        </span>
                                    </label>
                                    @if($agencyRequiresDate && $agencyChecked)
                                        <span @class([
                                            'ml-7 text-xs text-slate-600 sm:ml-0 sm:text-sm',
                                            'font-medium text-slate-800' => filled($agencyDate),
                                        ])>
                                            {{ filled($agencyDate) ? \Illuminate\Support\Carbon::parse($agencyDate)->format('d/m/Y') : 'Tiada tarikh' }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($panel['requires_reference'] ?? false)
                    <div @class(['mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2' => ! $compact, 'mb-2 grid grid-cols-1 gap-2 sm:grid-cols-2' => $compact])>
                        @include('partials.readonly-field', [
                            'label' => 'No. Rujukan',
                            'value' => $panel['no_rujukan'] ?? null,
                            'empty' => 'Tiada no. rujukan direkodkan.',
                        ])
                        @include('partials.readonly-field', [
                            'label' => 'Tarikh Surat',
                            'value' => filled($panel['tarikh_surat'] ?? null)
                                ? \Illuminate\Support\Carbon::parse($panel['tarikh_surat'])->format('d/m/Y')
                                : null,
                            'empty' => 'Tiada tarikh surat direkodkan.',
                        ])
                    </div>
                @endif

                <div @class(['border-t border-slate-200/60 pt-4' => ! $compact, 'border-t border-slate-200/60 pt-2' => $compact])>
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Pengesahan Pentadbir Negeri</p>
                    <div @class(['space-y-3' => ! $compact, 'space-y-1.5' => $compact])>
                        <label @class(['flex items-start gap-3.5 rounded-xl border border-slate-300 bg-white/40 px-4 py-3.5' => ! $compact, 'flex items-start gap-2 rounded-lg border border-slate-300 bg-white/40 px-2 py-1.5' => $compact])>
                            <input
                                type="checkbox"
                                disabled
                                @checked($panel['confirmed_accurate'] ?? false)
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-slate-800"
                            >
                            <span @class(['leading-relaxed text-slate-700', 'text-sm' => ! $compact, 'text-xs' => $compact])>
                                <span class="font-medium text-slate-900">
                                    @if(($panel['position'] ?? null) === 1)
                                        Saya mengesahkan surat niat telah dihantar ke premis
                                    @elseif(($panel['key'] ?? null) === \App\Support\AdminProceedSteps::STEP_BORANG_JRP)
                                        Saya mengesahkan borang JRP telah diisi dan dimuat naik ke PROMIS
                                    @else
                                        Saya mengesahkan maklumat ini tepat dan benar
                                    @endif
                                </span>
                            </span>
                        </label>

                        <label @class(['flex items-start gap-3.5 rounded-xl border border-slate-300 bg-white/40 px-4 py-3.5' => ! $compact, 'flex items-start gap-2 rounded-lg border border-slate-300 bg-white/40 px-2 py-1.5' => $compact])>
                            <input
                                type="checkbox"
                                disabled
                                @checked($panel['confirmed_promis'] ?? false)
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-slate-800"
                            >
                            <span @class(['leading-relaxed text-slate-700', 'text-sm' => ! $compact, 'text-xs' => $compact])>
                                <span class="font-medium text-slate-900">
                                    @if(($panel['position'] ?? null) === 1)
                                        Surat tawaran pemilik premis telah diterima dan dimuat naik ke PROMIS
                                    @elseif(($panel['key'] ?? null) === \App\Support\AdminProceedSteps::STEP_BORANG_JRP)
                                        Saya mengesahkan maklumat ini tepat dan benar
                                    @else
                                        Maklumat ini telah dimuat naik ke dalam sistem PROMIS.
                                    @endif
                                </span>
                            </span>
                        </label>

                        @if(($panel['key'] ?? null) !== \App\Support\AdminProceedSteps::STEP_BORANG_JRP)
                        <label @class(['flex items-start gap-3.5 rounded-xl border border-slate-300 bg-white/40 px-4 py-3.5' => ! $compact, 'flex items-start gap-2 rounded-lg border border-slate-300 bg-white/40 px-2 py-1.5' => $compact])>
                            <input
                                type="checkbox"
                                disabled
                                @checked($panel['completed'])
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-slate-800"
                            >
                            <span @class(['leading-relaxed text-slate-700', 'text-sm' => ! $compact, 'text-xs' => $compact])>
                                <span class="font-medium text-slate-900">Saya mengesahkan langkah ini telah selesai dilaksanakan.</span>
                            </span>
                        </label>
                        @endif
                    </div>
                </div>

                @include('partials.readonly-field', [
                    'label' => 'Catatan',
                    'value' => $panel['notes'] ?? null,
                    'multiline' => true,
                    'empty' => 'Tiada catatan direkodkan untuk langkah ini.',
                    'wrapperClass' => 'mt-5',
                ])
            </section>
        @endforeach
    </div>
</div>
