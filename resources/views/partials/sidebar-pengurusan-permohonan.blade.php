@php
    $permohonanMenuActive = request()->routeIs('status-permohonan.*')
        || (auth()->user()->isAdminNegeri() && request()->routeIs(['application.form', 'application.edit', 'admin-proceed.show']));
    $statusPermohonanCount = $sidebarNotifications['status_permohonan'] ?? $sidebarNotifications['admin_pending_review'] ?? 0;
    $hqPendingWithdrawal = $sidebarNotifications['hq_pending_withdrawal'] ?? 0;
    $listMenuLabel = auth()->user()->isAdminHq() ? 'Senarai Permohonan' : 'Status Permohonan';
    $listMenuBadgeCount = auth()->user()->isAdminHq()
        ? max($statusPermohonanCount, $hqPendingWithdrawal)
        : $statusPermohonanCount;
    $listMenuBadgeLabel = auth()->user()->isAdminHq() && $hqPendingWithdrawal > 0
        ? 'Permohonan menunggu tindakan Ibu Pejabat termasuk tarik semula'
        : 'Permohonan baharu menunggu semakan';
@endphp
<details class="group/permohonan-mgmt [&::-webkit-details-marker]:hidden" data-sidebar-menu="permohonan" {{ $permohonanMenuActive ? 'open' : '' }}>
    <summary class="sidebar-nav-summary {{ $permohonanMenuActive ? 'is-active' : '' }} [&::-webkit-details-marker]:hidden">
        <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        <span class="flex-1 text-left">Pengurusan Permohonan</span>
        @include('partials.sidebar-notification-badge', ['count' => $listMenuBadgeCount, 'label' => $listMenuBadgeLabel, 'badgeKey' => 'list_menu'])
        <svg class="h-4 w-4 shrink-0 text-white/40 transition-transform group-open/permohonan-mgmt:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
    </summary>
    <div class="ml-4 mt-1 space-y-0.5 border-l border-white/10 pl-3">
        @if(auth()->user()->isAdminNegeri())
            <a href="{{ route('application.form') }}" class="sidebar-nav-sublink flex items-center justify-between gap-2 {{ request()->routeIs('application.form') ? 'is-active' : '' }}">
                <span>Permohonan Baharu</span>
            </a>
        @endif
        <a href="{{ route('status-permohonan.index') }}" class="sidebar-nav-sublink flex items-center justify-between gap-2 {{ request()->routeIs(['status-permohonan.*', 'application.edit', 'admin-proceed.show']) ? 'is-active' : '' }}">
            <span>{{ $listMenuLabel }}</span>
            @include('partials.sidebar-notification-badge', ['count' => $listMenuBadgeCount, 'label' => $listMenuBadgeLabel, 'badgeKey' => 'list_menu'])
        </a>
    </div>
</details>
