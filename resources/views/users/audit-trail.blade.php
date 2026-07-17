@extends('layouts.app')

@section('title', 'Audit Trail')
@section('header_title', 'Audit Trail')
@section('header_subtitle', $user->name.' — '.$user->email)

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            Jejak aktiviti pengguna dalam sistem. <strong class="text-slate-800">{{ $activities->count() }}</strong> rekod dijumpai.
        </p>
        <!-- <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-800 hover:text-slate-600">
            ← Kembali ke Senarai Pengguna
        </a> -->
    </div>

    <div class="glass-card glass-table overflow-hidden">
        @forelse($activities as $activity)
        <div class="glass-row-hover flex gap-4 border-b border-slate-200/40 px-4 py-4 last:border-b-0">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200/80 text-slate-600 ring-1 ring-white/80">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-medium text-slate-800">{{ $activity['title'] }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $activity['description'] }}</p>
                    </div>
                    <div class="shrink-0 text-right text-xs text-slate-500">
                        <p class="font-medium text-slate-700">{{ $activity['occurred_at']->format('d/m/Y') }}</p>
                        <p>{{ $activity['occurred_at']->format('H:i:s') }}</p>
                    </div>
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">{{ $activity['source'] }}</span>
                    @if($activity['performed_by'])
                    <span class="rounded-full bg-violet-100 px-2 py-0.5 text-violet-700">Oleh: {{ $activity['performed_by'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-16 text-center text-slate-500">
            Tiada aktiviti direkodkan untuk pengguna ini.
        </div>
        @endforelse
    </div>
</div>
@endsection
