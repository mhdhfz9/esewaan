@php
    $kontrakSewaanMenuActive = request()->routeIs('kontrak-sewaan.*');
    $kontrakSewaanCount = $sidebarNotifications['kontrak_sewaan'] ?? 0;
@endphp
<details class="group/kontrak-sewaan-mgmt [&::-webkit-details-marker]:hidden" {{ $kontrakSewaanMenuActive ? 'open' : '' }}>
    <summary class="sidebar-nav-summary {{ $kontrakSewaanMenuActive ? 'is-active' : '' }} [&::-webkit-details-marker]:hidden">
        <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span class="flex-1 text-left">Kontrak Sewaan</span>
        @include('partials.sidebar-notification-badge', ['count' => $kontrakSewaanCount, 'label' => 'Kontrak baharu dalam senarai'])
        <svg class="h-4 w-4 shrink-0 text-white/40 transition-transform group-open/kontrak-sewaan-mgmt:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
    </summary>
    <div class="ml-4 mt-1 space-y-0.5 border-l border-white/10 pl-3">
        <a href="{{ route('kontrak-sewaan.index') }}" class="sidebar-nav-sublink flex items-center justify-between gap-2 {{ request()->routeIs('kontrak-sewaan.*') ? 'is-active' : '' }}">
            <span>Senarai Kontrak Sewaan</span>
            @include('partials.sidebar-notification-badge', ['count' => $kontrakSewaanCount, 'label' => 'Kontrak baharu dalam senarai'])
        </a>
    </div>
</details>
