<div class="flex flex-wrap items-center gap-3 text-xs text-slate-600">
    <span class="font-medium text-slate-700">Petunjuk:</span>
    <span class="inline-flex items-center gap-1.5">
        @if($useHighlightOnly ?? false)
            <span class="inline-block h-4 w-6 rounded bg-amber-50/90 ring-1 ring-amber-200/80" aria-hidden="true"></span>
        @else
            @include('partials.application-list-status-badge', ['variant' => 'baru'])
        @endif
        <span>{{ $baruLabel ?? 'Perlu tindakan anda' }}</span>
    </span>
    @if (! ($hideSelesai ?? false))
    <span class="inline-flex items-center gap-1.5">
        @include('partials.application-list-status-badge', ['variant' => 'selesai'])
        <span>{{ $selesaiLabel ?? 'Anda telah selesai memproses' }}</span>
    </span>
    @endif
    @if($showSemakan ?? false)
    <span class="inline-flex items-center gap-1.5">
        @include('partials.application-list-status-badge', ['variant' => 'semakan'])
        <span>Perlu disemak</span>
    </span>
    @endif
</div>
