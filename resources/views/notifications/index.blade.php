@extends('layouts.app')

@section('title', 'Peti Masuk')
@section('header_title', 'Peti Masuk')
@section('header_subtitle', 'Notifikasi kemajuan permohonan, status dan kontrak')

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            Menunjukkan
            <strong class="text-slate-800">{{ $items->count() }}</strong>
            notifikasi belum dibaca
            @if($inboxTotal > 0)
                · jumlah badge: <strong class="text-slate-800">{{ $inboxTotal }}</strong>
            @endif
        </p>

        @if($items->isNotEmpty())
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="glass-btn-secondary rounded-lg px-4 py-2 text-sm font-medium">
                    Tanda semua sebagai dibaca
                </button>
            </form>
        @endif
    </div>

    <section class="glass-card overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($items as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="block px-4 py-3 transition-colors hover:bg-slate-50/80"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                {{ $item['category_label'] }}
                            </p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                            <p class="mt-0.5 text-sm text-slate-600">{{ $item['message'] }}</p>
                        </div>
                        <time class="shrink-0 text-xs text-slate-400" datetime="{{ $item['occurred_at']?->toIso8601String() }}">
                            {{ $item['occurred_at']?->format('d/m/Y H:i') ?? '–' }}
                        </time>
                    </div>
                </a>
            @empty
                <div class="px-4 py-12 text-center text-sm text-slate-500">
                    Tiada notifikasi baharu buat masa ini.
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
