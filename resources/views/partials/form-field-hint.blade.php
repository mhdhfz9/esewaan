@props(['text'])
<span class="group relative inline-flex shrink-0">
    <button
        type="button"
        tabindex="-1"
        class="inline-flex h-4 w-4 items-center justify-center rounded-full text-slate-400 transition-colors hover:text-slate-800 focus:outline-none focus-visible:text-slate-800 focus-visible:ring-2 focus-visible:ring-slate-500/30"
        aria-label="{{ $text }}"
    >
        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </button>
    <span
        role="tooltip"
        class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 w-56 -translate-x-1/2 rounded-xl glass-card px-3 py-2 text-left text-[11px] font-normal normal-case leading-relaxed tracking-normal text-slate-600 opacity-0 shadow-lg transition-opacity group-hover:opacity-100 group-focus-within:opacity-100"
    >
        {{ $text }}
        <span class="absolute left-1/2 top-full -mt-px -translate-x-1/2 border-4 border-transparent border-t-white"></span>
    </span>
</span>
