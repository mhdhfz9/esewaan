@php
    /** @var array<int, array{label: string, description: string, state: string}> $steps */
@endphp
<div class="overflow-x-auto overflow-y-visible">
    <ol class="flex min-w-[56rem] items-start px-2 py-3">
        @foreach($steps as $index => $step)
            @php
                $isCompleted = $step['state'] === 'completed';
                $isCurrent = $step['state'] === 'current';
                $leftActive = $index > 0 && ($steps[$index - 1]['state'] === 'completed');
                $rightActive = $isCompleted;
            @endphp
            <li class="relative flex flex-1 flex-col">
                @unless($loop->first)
                    <span @class([
                        'absolute left-0 top-5 h-0.5 w-1/2 -translate-y-1/2',
                        'bg-indigo-600' => $leftActive,
                        'bg-slate-200' => ! $leftActive,
                    ])></span>
                @endunless
                @unless($loop->last)
                    <span @class([
                        'absolute right-0 top-5 h-0.5 w-1/2 -translate-y-1/2',
                        'bg-indigo-600' => $rightActive,
                        'bg-slate-200' => ! $rightActive,
                    ])></span>
                @endunless

                <button
                    type="button"
                    data-step-index="{{ $index }}"
                    class="group flex flex-col items-center text-center focus:outline-none"
                    title="Lihat butiran {{ $step['label'] }}"
                >
                    <span @class([
                        'relative z-10 inline-flex rounded-full p-1 transition-transform group-hover:scale-105',
                        'bg-white shadow-sm' => $isCompleted || $isCurrent,
                        'bg-transparent' => ! $isCompleted && ! $isCurrent,
                    ])>
                        <span
                            data-step-circle
                            @class([
                                'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                                'bg-indigo-600 text-white' => $isCompleted || $isCurrent,
                                'border border-slate-300 bg-white text-slate-400 group-hover:border-indigo-400 group-hover:text-indigo-500' => ! $isCompleted && ! $isCurrent,
                            ])
                        >
                            @if($isCompleted)
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                    </span>

                    <span @class([
                        'mt-2 text-xs font-semibold underline-offset-4 group-hover:underline',
                        'text-indigo-700' => $isCurrent,
                        'text-slate-700' => $isCompleted,
                        'text-slate-400' => ! $isCompleted && ! $isCurrent,
                    ])>{{ $step['label'] }}</span>
                    <span class="mt-0.5 hidden text-[11px] text-slate-400 sm:block">{{ $step['description'] }}</span>
                </button>
            </li>
        @endforeach
    </ol>
</div>
