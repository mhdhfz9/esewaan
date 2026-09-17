<span
    data-sidebar-notification-badge="{{ $badgeKey }}"
    @if(isset($label)) aria-label="{{ $label }}" @endif
    @class([
        'inline-flex min-w-[1.125rem] shrink-0 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white shadow-sm ring-2 ring-slate-900 transition-opacity',
        'hidden' => ($count ?? 0) <= 0,
    ])
>{{ ($count ?? 0) > 99 ? '99+' : ($count ?? 0) }}</span>
