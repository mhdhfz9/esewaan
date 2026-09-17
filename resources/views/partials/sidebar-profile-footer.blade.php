<div class="flex-shrink-0 space-y-1 border-t border-white/10 px-3 py-4">
    <div class="flex items-center gap-1">
        <a href="{{ route('profile.show') }}"
            class="sidebar-nav-link min-w-0 flex-1 {{ request()->routeIs('profile.*') ? 'is-active' : '' }}"
            title="Profil saya">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-semibold text-slate-200 ring-1 ring-white/20">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-white/50">{{ auth()->user()->email }}</p>
            </div>
        </a>
        <a href="{{ route('penampilan.show') }}"
            class="sidebar-appearance-btn {{ request()->routeIs('penampilan.*') ? 'is-active' : '' }}"
            title="Penampilan"
            aria-label="Penampilan">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </a>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="px-0 pt-1" data-no-global-loader>
        @csrf
        <button type="submit" class="sidebar-logout-btn w-full rounded-xl px-3 py-2.5 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 focus:ring-offset-slate-900">
            Log Keluar
        </button>
    </form>
</div>
