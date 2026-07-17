@php
    $sort = $sort ?? 'created_at';
    $direction = $direction ?? 'desc';
    $canManageUserStatus = $canManageUserStatus ?? false;
    $showAdminSection = $showAdminSection ?? false;
    $adminUsers = $adminUsers ?? collect();
    $negeriUsers = $negeriUsers ?? collect();
    $totalUsers = $adminUsers->count() + $negeriUsers->count();
@endphp
<p id="users-count" class="text-sm text-slate-600">
    Menunjukkan <strong class="text-slate-800">{{ $totalUsers }}</strong> pengguna
    @if($showAdminSection)
        (<strong class="text-slate-800">{{ $adminUsers->count() }}</strong> Admin,
        <strong class="text-slate-800">{{ $negeriUsers->count() }}</strong> Negeri)
    @endif
</p>

@if($showAdminSection)
<section class="glass-card glass-table overflow-hidden">
    <div class="glass-divider-soft border-b px-4 py-3">
        <h2 class="text-base font-semibold text-slate-900">Admin</h2>
        <p class="mt-0.5 text-xs text-slate-500">Pengguna peranan Admin.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="glass-divider-soft border-b bg-white/40">
                    @foreach([
                        'name' => 'Nama',
                        'email' => 'Emel',
                        'is_active' => 'Status',
                        'created_at' => 'Didaftar',
                    ] as $column => $label)
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 {{ $column === 'is_active' ? 'text-center' : 'text-left' }}">
                        <button
                            type="button"
                            data-users-sort="{{ $column }}"
                            class="group inline-flex items-center gap-1 hover:text-slate-800 {{ $sort === $column ? 'text-slate-800' : '' }} {{ $column === 'is_active' ? 'mx-auto' : '' }}"
                        >
                            <span>{{ $label }}</span>
                            <span class="text-[10px] leading-none opacity-70">
                                @if($sort === $column)
                                    {{ $direction === 'asc' ? '▲' : '▼' }}
                                @else
                                    <span class="text-gray-300 group-hover:text-slate-400">↕</span>
                                @endif
                            </span>
                        </button>
                    </th>
                    @endforeach
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($adminUsers as $user)
                    @include('users.partials.row', [
                        'user' => $user,
                        'canManageUserStatus' => $canManageUserStatus,
                        'showNegeriColumn' => false,
                    ])
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                        Tiada pengguna Admin dijumpai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif

<section class="glass-card glass-table overflow-hidden">
    <div class="glass-divider-soft border-b px-4 py-3">
        <h2 class="text-base font-semibold text-slate-900">Negeri</h2>
        <p class="mt-0.5 text-xs text-slate-500">Pengguna peranan Negeri.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="glass-divider-soft border-b bg-white/40">
                    @foreach([
                        'name' => 'Nama',
                        'email' => 'Emel',
                        'negeri' => 'Negeri',
                        'is_active' => 'Status',
                        'created_at' => 'Didaftar',
                    ] as $column => $label)
                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 {{ $column === 'is_active' ? 'text-center' : 'text-left' }}">
                        <button
                            type="button"
                            data-users-sort="{{ $column }}"
                            class="group inline-flex items-center gap-1 hover:text-slate-800 {{ $sort === $column ? 'text-slate-800' : '' }} {{ $column === 'is_active' ? 'mx-auto' : '' }}"
                        >
                            <span>{{ $label }}</span>
                            <span class="text-[10px] leading-none opacity-70">
                                @if($sort === $column)
                                    {{ $direction === 'asc' ? '▲' : '▼' }}
                                @else
                                    <span class="text-gray-300 group-hover:text-slate-400">↕</span>
                                @endif
                            </span>
                        </button>
                    </th>
                    @endforeach
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($negeriUsers as $user)
                    @include('users.partials.row', [
                        'user' => $user,
                        'canManageUserStatus' => $canManageUserStatus,
                        'showNegeriColumn' => true,
                    ])
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                        Tiada pengguna Negeri dijumpai.
                        @if(empty($search))
                        <a href="{{ route('users.create') }}" class="mt-2 block glass-link">Daftar pengguna pertama</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
