@php
    $variant = $variant ?? 'baru';
@endphp
@if($variant === 'baru')
    <span class="inline-flex shrink-0 items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800 ring-1 ring-amber-200">Baru</span>
@elseif($variant === 'selesai')
    <span class="inline-flex shrink-0 items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200">Selesai</span>
@elseif($variant === 'semakan')
    <span class="inline-flex shrink-0 items-center rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-800 ring-1 ring-sky-200">Semakan</span>
@endif
