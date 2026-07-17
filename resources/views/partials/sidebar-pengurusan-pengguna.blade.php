@php
    $userMenuActive = request()->routeIs('users.*');
@endphp
<details class="group/user-mgmt [&::-webkit-details-marker]:hidden" {{ $userMenuActive ? 'open' : '' }}>
    <summary class="sidebar-nav-summary {{ $userMenuActive ? 'is-active' : '' }} [&::-webkit-details-marker]:hidden">
        <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
        <span class="flex-1 text-left">Pengurusan Pengguna</span>
        <svg class="h-4 w-4 shrink-0 text-white/40 transition-transform group-open/user-mgmt:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
    </summary>
    <div class="ml-4 mt-1 space-y-0.5 border-l border-white/10 pl-3">
        <a href="{{ route('users.create') }}" class="sidebar-nav-sublink {{ request()->routeIs('users.create') ? 'is-active' : '' }}">
            Pendaftaran Pengguna
        </a>
        <a href="{{ route('users.index') }}" class="sidebar-nav-sublink {{ request()->routeIs('users.index') ? 'is-active' : '' }}">
            Senarai Pengguna
        </a>
    </div>
</details>
