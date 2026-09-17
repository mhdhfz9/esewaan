@php
    $activeSteps = $activeSteps ?? ($contract ? \App\Support\AdminProceedSteps::activeStepsFor($contract) : []);
    $totalSteps = count($activeSteps);
    $progressPercent = $totalSteps > 0 ? (int) round(($completedCount / $totalSteps) * 100) : 0;
    $isReadyForHq = $progressPercent >= 100;
    $stepHasError = $errors->has('completed');
    $clientNavigation = $clientNavigation ?? false;
    $readOnly = $readOnly ?? false;
@endphp
<nav aria-label="Langkah tindakan" class="w-full" id="proceed-stepper">
    <div class="mb-5">
        <div class="mb-2 flex items-center justify-between text-xs">
            <span class="font-medium text-slate-700">Tindakan</span>
            <span id="proceed-progress-count" class="{{ $isReadyForHq ? 'font-semibold text-emerald-700' : 'text-slate-500' }}">{{ $progressPercent }}%</span>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full glass-progress-track">
            <div
                id="proceed-progress-bar"
                class="h-full rounded-full transition-all duration-300 {{ $isReadyForHq ? 'glass-progress-fill-emerald' : ($progressPercent > 0 ? 'glass-progress-fill-amber' : 'glass-progress-fill') }}"
                style="width: {{ $progressPercent }}%"
            ></div>
        </div>
        @if($isReadyForHq)
            <p id="proceed-ready-status" class="mt-2 text-xs font-medium text-emerald-700">
                Permohonan sedia untuk dihantar ke Ibu Pejabat
            </p>
        @else
            <p id="proceed-ready-status" class="mt-2 hidden text-xs font-medium text-emerald-700"></p>
        @endif
    </div>

    <ol class="space-y-0">
        @foreach($activeSteps as $index => $stepNumber)
            @php
                $displayNumber = $index + 1;
                $definition = \App\Support\AdminProceedSteps::definitionForStep($stepNumber);
                $stepKey = $definition['key'];
                $isCompleted = ($progress[$stepKey]['completed'] ?? false) === true;
                $isCurrent = $stepNumber === $step;
                $isNavigable = ! $readOnly && ($clientNavigation || ($contract && \App\Support\AdminProceedSteps::isNavigable($contract, $stepNumber)));
                $isLast = $index === $totalSteps - 1;
                $isError = $isCurrent && $stepHasError;
            @endphp
            <li class="relative flex gap-3" data-step-item="{{ $stepNumber }}" data-display-number="{{ $displayNumber }}">
                <div class="flex flex-col items-center">
                    @if($readOnly)
                        <span
                            @class([
                                'relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                'glass-step-active ring-4 ring-blue-200/80' => $isCurrent,
                                'glass-subtle border border-slate-300/50 text-slate-600' => ! $isCurrent,
                            ])
                        >{{ $displayNumber }}</span>
                    @elseif($isNavigable)
                        @if($clientNavigation)
                            <button
                                type="button"
                                data-proceed-step="{{ $stepNumber }}"
                                aria-current="{{ $isCurrent ? 'step' : 'false' }}"
                                @class([
                                    'proceed-step-trigger proceed-step-badge relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                                    'bg-red-600 text-white ring-4 ring-red-100 focus-visible:ring-red-500' => $isError,
                                    'glass-step-active ring-4 ring-blue-200/80 focus-visible:ring-blue-500' => $isCurrent && ! $isError,
                                    'glass-step-done focus-visible:ring-emerald-500' => $isCompleted && ! $isCurrent,
                                    'glass-step-pending focus-visible:ring-amber-400' => ! $isCompleted && ! $isCurrent,
                                ])
                            >
                                @if($isCompleted && ! $isCurrent)
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                @else
                                    {{ $displayNumber }}
                                @endif
                            </button>
                        @else
                        <a
                            href="{{ route($proceedRouteName ?? 'admin-proceed.show', ['contract' => $contract, 'step' => $stepNumber]) }}"
                            aria-current="{{ $isCurrent ? 'step' : 'false' }}"
                            @class([
                                'relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                                'bg-red-600 text-white ring-4 ring-red-100 focus-visible:ring-red-500' => $isError,
                                'glass-step-active ring-4 ring-blue-200/80 focus-visible:ring-blue-500' => $isCurrent && ! $isError,
                                'glass-step-done focus-visible:ring-emerald-500' => $isCompleted && ! $isCurrent,
                                'glass-step-pending focus-visible:ring-amber-400' => ! $isCompleted && ! $isCurrent,
                            ])
                        >
                            @if($isCompleted && ! $isCurrent)
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            @else
                                {{ $displayNumber }}
                            @endif
                        </a>
                        @endif
                    @else
                        <span
                            aria-disabled="true"
                            class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-slate-200 bg-slate-50 text-xs font-semibold text-slate-400"
                            title="Lengkapkan langkah sebelumnya terlebih dahulu"
                        >{{ $displayNumber }}</span>
                    @endif

                    @unless($isLast)
                        <div data-step-connector="{{ $stepNumber }}" @class([
                            'proceed-step-connector my-1 w-0.5 flex-1 min-h-[1.75rem] rounded-full',
                            $isError ? 'bg-red-300' : ($isCompleted ? 'bg-emerald-300' : 'bg-amber-300'),
                        ])></div>
                    @endunless
                </div>

                <div @class(['min-w-0 flex-1', ! $isLast ? 'pb-5' : 'pb-0'])>
                    @if($readOnly)
                        <div @class([
                            'rounded-xl border px-3.5 py-3',
                            'glass-step-card-active shadow-sm' => $isCurrent,
                            'border-transparent' => ! $isCurrent,
                        ])>
                            <p @class([
                                'text-[11px] font-medium uppercase tracking-wide',
                                'text-blue-700' => $isCurrent,
                                'text-slate-400' => ! $isCurrent,
                            ])>
                                @if($isCurrent)
                                    Seterusnya
                                @else
                                    Langkah {{ $displayNumber }}
                                @endif
                            </p>
                            <p @class([
                                'mt-0.5 text-sm font-medium leading-snug',
                                'text-slate-900' => $isCurrent,
                                'text-slate-600' => ! $isCurrent,
                            ])>{{ $definition['title'] }}</p>
                        </div>
                    @elseif($isNavigable)
                        @if($clientNavigation)
                            <button
                                type="button"
                                data-proceed-step="{{ $stepNumber }}"
                                aria-current="{{ $isCurrent ? 'step' : 'false' }}"
                                @class([
                                    'proceed-step-trigger proceed-step-card group block w-full rounded-2xl border px-3.5 py-3 text-left transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                                    'border-red-300 bg-red-50/80 shadow-sm ring-1 ring-red-200 focus-visible:ring-red-500' => $isError,
                                    'glass-step-card-active focus-visible:ring-blue-500' => $isCurrent && ! $isError,
                                    'glass-step-card-pending focus-visible:ring-amber-400' => ! $isCompleted && ! $isCurrent,
                                    'glass-step-card-done focus-visible:ring-emerald-500' => $isCompleted && ! $isCurrent,
                                ])
                            >
                        @else
                        <a
                            href="{{ route($proceedRouteName ?? 'admin-proceed.show', ['contract' => $contract, 'step' => $stepNumber]) }}"
                            aria-current="{{ $isCurrent ? 'step' : 'false' }}"
                            @class([
                                'group block rounded-2xl border px-3.5 py-3 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                                'border-red-300 bg-red-50/80 shadow-sm ring-1 ring-red-200 focus-visible:ring-red-500' => $isError,
                                'glass-step-card-active focus-visible:ring-blue-500' => $isCurrent && ! $isError,
                                'glass-step-card-pending focus-visible:ring-amber-400' => ! $isCompleted && ! $isCurrent,
                                'glass-step-card-done focus-visible:ring-emerald-500' => $isCompleted && ! $isCurrent,
                            ])
                        >
                        @endif

                            <p data-step-status="{{ $stepNumber }}" @class([
                                'text-[11px] font-medium uppercase tracking-wide',
                                'text-red-600' => $isError,
                                'text-blue-700' => $isCurrent && ! $isError,
                                'text-emerald-600' => $isCompleted && ! $isCurrent,
                                'text-amber-600' => ! $isCompleted && ! $isCurrent,
                            ])>
                                @if($isError)
                                    Perlu pengesahan
                                @elseif($isCompleted && ! $isCurrent)
                                    Selesai
                                @elseif($isCurrent)
                                    Sedang dijalankan
                                @elseif(! $isCompleted)
                                    Belum selesai
                                @else
                                    Langkah {{ $displayNumber }}
                                @endif
                            </p>
                            <p data-step-title="{{ $stepNumber }}" @class([
                                'mt-0.5 text-sm font-medium leading-snug',
                                'text-red-900' => $isError,
                                'text-slate-900' => $isCurrent && ! $isError,
                                'text-emerald-800' => $isCompleted && ! $isCurrent,
                                'text-slate-700 group-hover:text-slate-900' => ! $isCurrent && ! $isCompleted,
                            ])>{{ $definition['title'] }}</p>

                        @if($clientNavigation)
                            </button>
                        @else
                        </a>
                        @endif
                    @else
                        <div class="rounded-xl border border-transparent px-3.5 py-3 opacity-50">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Langkah {{ $displayNumber }}</p>
                            <p class="mt-0.5 text-sm font-medium leading-snug text-slate-500">{{ $definition['title'] }}</p>
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</nav>
