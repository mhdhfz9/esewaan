<div class="flex-shrink-0 space-y-1 border-t border-white/10 px-3 py-4">
    <a href="{{ route('profile.show') }}"
        class="sidebar-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}"
        title="Profil saya">
        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-semibold text-slate-200 ring-1 ring-white/20">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-white/50">{{ auth()->user()->email }}</p>
        </div>
    </a>
    <form method="POST" action="{{ route('logout') }}" class="px-0 pt-1">
        @csrf
        <button type="submit" class="sidebar-logout-btn w-full rounded-xl px-3 py-2.5 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 focus:ring-offset-slate-900">
            Log Keluar
        </button>
    </form>
</div>
