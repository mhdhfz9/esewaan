@php
    $showNegeriColumn = $showNegeriColumn ?? true;
@endphp
<tr class="glass-row-hover {{ ! $user->isActive() ? 'bg-slate-50/80' : '' }}">
    <td class="px-4 py-3 font-medium text-slate-800">{{ $user->name }}</td>
    <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
    @if($showNegeriColumn)
    <td class="px-4 py-3 text-slate-600">{{ $user->negeri ?? '–' }}</td>
    @endif
    <td class="px-4 py-3 text-center">
        <div class="mx-auto flex w-fit flex-col items-center gap-1">
            @if($canManageUserStatus && ! $user->is(auth()->user()))
            <form method="POST" action="{{ route('users.toggle-status', $user) }}"
                class="flex justify-center"
                data-user-status-toggle
                onsubmit="return confirm('{{ $user->isActive() ? 'Nyahaktifkan akaun ' . $user->name . '?' : 'Aktifkan semula akaun ' . $user->name . '?' }}');">
                @csrf
                @method('PATCH')
                <button type="submit"
                    role="switch"
                    aria-checked="{{ $user->isActive() ? 'true' : 'false' }}"
                    title="{{ $user->isActive() ? 'Nyahaktifkan pengguna' : 'Aktifkan pengguna' }}"
                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent p-0.5 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-1
                        {{ $user->isActive() ? 'bg-emerald-500 focus:ring-emerald-400' : 'bg-red-500 focus:ring-red-400' }}">
                    <span class="sr-only">{{ $user->statusLabel() }}</span>
                    <span aria-hidden="true"
                        class="pointer-events-none inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out
                        {{ $user->isActive() ? 'translate-x-4' : 'translate-x-0' }}"></span>
                </button>
            </form>
            <span class="text-[11px] font-medium leading-none {{ $user->isActive() ? 'text-emerald-700' : 'text-red-700' }}">
                {{ $user->statusLabel() }}
            </span>
            @else
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                {{ $user->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}
            ">{{ $user->statusLabel() }}</span>
            @endif
        </div>
    </td>
    <td class="px-4 py-3 text-slate-600">{{ $user->created_at?->format('d/m/Y') ?? '–' }}</td>
    <td class="px-4 py-3 text-right">
        <div class="inline-flex items-center justify-end gap-1">
            <a href="{{ route('users.edit', $user) }}"
               title="Urus profil"
               class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800">
                <span class="sr-only">Urus profil</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </a>
            <a href="{{ route('users.audit-trail', $user) }}"
               title="Audit trail"
               class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-violet-50 hover:text-violet-600">
                <span class="sr-only">Audit trail</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </a>
        </div>
    </td>
</tr>
