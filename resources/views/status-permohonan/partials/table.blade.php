@php
    $search = $search ?? '';
@endphp
<p id="status-permohonan-count" class="text-sm text-slate-600">
    Menunjukkan <strong class="text-slate-800">{{ $contracts->count() }}</strong> daripada <strong class="text-slate-800">{{ $contracts->total() }}</strong> permohonan
</p>

<div class="glass-card glass-table overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full min-w-[48rem] text-sm">
        <thead>
            <tr class="glass-divider border-b">
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Negeri</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Kategori Permohonan</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Negeri</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Tarikh Permohonan</th>
                <th class="min-w-[8rem] px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Kemajuan</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Tindakan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($contracts as $c)
                @php
                    $adminNegeriName = $c->displayAdminNegeriName();
                    $confirmWithdrawalReason = filled($c->withdrawal_reason) ? $c->withdrawal_reason : '';
                @endphp
                <tr class="{{ $c->adminListRowClasses(auth()->user()) }} transition-colors">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $adminNegeriName }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                        {{ \App\Support\ApplicationCategories::label($c->kategori_permohonan) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $c->premise?->negeri ?? '–' }}</td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->isAdminHq() && $c->hasPendingWithdrawalRequest())
                                <!-- <span
                                    class="inline-flex shrink-0 items-center justify-center rounded-full bg-amber-100 p-1 text-amber-600"
                                    title="Permohonan tarik semula menunggu kelulusan HQ"
                                >
                                    <span class="sr-only">Permohonan tarik semula menunggu kelulusan</span>
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </span> -->
                            @endif
                            <span class="{{ $c->applicationStatusBadgeClass() }}">
                                {{ $c->applicationStatusLabel(auth()->user()) }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                        {{ $c->created_at?->format('d/m/Y') ?? '–' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                        @php $progressPercent = $c->proceedProgressPercent(); @endphp
                        <div class="min-w-[6rem]">
                            <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                                @if($c->isReadyToSendToHq() || $c->isPendingHqReview())
                                    <span class="{{ $c->adminListProgressPercentClass() }}">100%</span>
                                @else
                                    <span class="{{ $c->adminListProgressPercentClass() }}">{{ $progressPercent }}%</span>
                                @endif
                            </div>
                            <div class="glass-progress-track h-1.5 overflow-hidden rounded-full">
                                <div
                                    class="glass-progress-fill h-full rounded-full transition-all duration-300 {{ $c->adminListProgressBarFillClass() }}"
                                    style="width: {{ $c->adminListProgressWidthPercent() }}%"
                                ></div>
                            </div>
                        </div>
                    </td>
                    <td @class([
                        'px-4 py-3',
                        'text-center' => auth()->user()->isAdminHq(),
                        'text-right' => ! auth()->user()->isAdminHq(),
                    ])>
                        <div @class([
                            'inline-flex items-center gap-1',
                            'justify-center' => auth()->user()->isAdminHq(),
                            'justify-end' => ! auth()->user()->isAdminHq(),
                        ])>
                            @if(auth()->user()->isAdminNegeri())
                                @if($c->showsSubmitToAdminHqButton())
                                    <form
                                        id="submit-hq-form-{{ $c->id }}"
                                        method="POST"
                                        action="{{ route('status-permohonan.submit-hq', $c) }}"
                                        class="inline"
                                    >
                                        @csrf
                                        <button
                                            type="button"
                                            title="Hantar ke Admin"
                                            class="status-confirm-trigger rounded-lg p-2 text-slate-500 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                            data-confirm-title="Hantar Permohonan ke Admin"
                                            data-confirm-message="Anda pasti mahu menghantar permohonan ini kepada Admin untuk semakan? Sila semak maklumat premis sebelum meneruskan."
                                            data-confirm-form="submit-hq-form-{{ $c->id }}"
                                            data-confirm-button="Ya, Hantar"
                                            data-confirm-tone="success"
                                        >
                                            <span class="sr-only">Hantar ke Admin</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                @if($c->isPendingProceed())
                                    <a
                                        href="{{ route('application.edit', $c) }}"
                                        title="Edit"
                                        class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800"
                                    >
                                        <span class="sr-only">Edit</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </a>
                                @endif
                                @if($c->isPendingProceed() && $c->isFollowUpApplication())
                                    <form
                                        id="revert-follow-up-form-{{ $c->id }}"
                                        method="POST"
                                        action="{{ route('kontrak-sewaan.follow-up.cancel', $c) }}"
                                        class="inline"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            title="Batal & Kembali ke Kontrak Asal"
                                            class="status-confirm-trigger rounded-lg p-2 text-slate-500 transition-colors hover:bg-amber-50 hover:text-amber-600"
                                            data-confirm-title="Batal Permohonan Susulan"
                                            data-confirm-message="Permohonan {{ \App\Support\ApplicationCategories::label($c->kategori_permohonan) }} ini akan dibatalkan dan kontrak asal dikembalikan ke senarai kontrak sewaan. Teruskan?"
                                            data-confirm-form="revert-follow-up-form-{{ $c->id }}"
                                            data-confirm-button="Ya, Batalkan"
                                            data-confirm-tone="danger"
                                        >
                                            <span class="sr-only">Batal & Kembali ke Kontrak Asal</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                @if($c->canRequestWithdrawal())
                                    <form
                                        id="withdrawal-form-{{ $c->id }}"
                                        method="POST"
                                        action="{{ route('status-permohonan.request-withdrawal', $c) }}"
                                        class="inline"
                                    >
                                        @csrf
                                        <input type="hidden" name="withdrawal_reason" value="">
                                        <button
                                            type="button"
                                            title="Mohon Tarik Semula"
                                            class="status-confirm-trigger rounded-lg p-2 text-slate-500 transition-colors hover:bg-amber-50 hover:text-amber-600"
                                            data-confirm-title="Mohon Tarik Semula"
                                            data-confirm-message="Permohonan ini akan dihantar kepada HQ untuk kelulusan tarik semula. Sila nyatakan alasan di bawah."
                                            data-confirm-form="withdrawal-form-{{ $c->id }}"
                                            data-confirm-button="Hantar Permohonan"
                                            data-confirm-tone="danger"
                                            data-confirm-require-reason="true"
                                            data-confirm-reason-field="withdrawal_reason"
                                            data-confirm-reason-label="Alasan Permohonan Tarik Semula *"
                                        >
                                            <span class="sr-only">Mohon Tarik Semula</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            @elseif(auth()->user()->isAdminHq())
                                @if($c->hasPendingWithdrawalRequest())
                                    <a
                                        href="{{ route('status-permohonan.review', $c) }}"
                                        title="Lihat Permohonan"
                                        class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800"
                                    >
                                        <span class="sr-only">Lihat Permohonan</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <form
                                        id="approve-withdrawal-form-{{ $c->id }}"
                                        method="POST"
                                        action="{{ route('status-permohonan.resolve-withdrawal', $c) }}"
                                        class="inline"
                                    >
                                        @csrf
                                        <input type="hidden" name="decision" value="approve">
                                        <button
                                            type="button"
                                            title="Luluskan Permohonan Tarik Semula"
                                            class="status-confirm-trigger rounded-lg p-2 text-slate-500 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                            data-confirm-title="Luluskan Permohonan Tarik Semula"
                                            data-confirm-message="Permohonan akan dikembalikan kepada pentadbir negeri untuk dikemaskini semula. Anda pasti mahu meluluskan permohonan tarik semula ini?"
                                            data-confirm-withdrawal-reason="{{ $confirmWithdrawalReason }}"
                                            data-confirm-form="approve-withdrawal-form-{{ $c->id }}"
                                            data-confirm-button="Ya, Luluskan"
                                            data-confirm-tone="success"
                                        >
                                            <span class="sr-only">Luluskan Permohonan Tarik Semula</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 22 22" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>

                                        </button>
                                    </form>
                                    <form
                                        id="reject-withdrawal-form-{{ $c->id }}"
                                        method="POST"
                                        action="{{ route('status-permohonan.resolve-withdrawal', $c) }}"
                                        class="inline"
                                    >
                                        @csrf
                                        <input type="hidden" name="decision" value="reject">
                                        <button
                                            type="button"
                                            title="Tolak Permohonan Tarik Semula"
                                            class="status-confirm-trigger rounded-lg p-2 text-slate-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                            data-confirm-title="Tolak Permohonan Tarik Semula"
                                            data-confirm-message="Permohonan akan kekal dalam senarai semakan HQ. Anda pasti mahu menolak permohonan tarik semula ini?"
                                            data-confirm-withdrawal-reason="{{ $confirmWithdrawalReason }}"
                                            data-confirm-form="reject-withdrawal-form-{{ $c->id }}"
                                            data-confirm-button="Ya, Tolak"
                                            data-confirm-tone="danger"
                                        >
                                            <span class="sr-only">Tolak Permohonan Tarik Semula</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                <a
                                    href="{{ route('status-permohonan.review', $c) }}"
                                    title="Semak & Sahkan Permohonan"
                                    class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                >
                                    <span class="sr-only">Semak & Sahkan Permohonan</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </a>
                                @endif
                            @endif

                            @if(auth()->user()->isAdminNegeri() && $c->canBeDeletedByAdminNegeri() && ! ($c->isPendingProceed() && $c->isFollowUpApplication()))
                            <form
                                id="delete-form-{{ $c->id }}"
                                method="POST"
                                action="{{ route('status-permohonan.destroy', $c) }}"
                                class="inline"
                            >
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="delete_reason" value="">
                                <button
                                    type="button"
                                    title="Padam"
                                    class="status-confirm-trigger rounded-lg p-2 text-slate-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                    data-confirm-title="Padam Permohonan"
                                    data-confirm-message="Permohonan akan dipindahkan ke tab Sejarah HQ. Sila nyatakan alasan padam di bawah."
                                    data-confirm-form="delete-form-{{ $c->id }}"
                                    data-confirm-button="Ya, Padam"
                                    data-confirm-tone="danger"
                                    data-confirm-require-reason="true"
                                >
                                    <span class="sr-only">Padam</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                        Tiada permohonan dijumpai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div id="status-permohonan-pagination" class="mt-4">
    {{ $contracts->withQueryString()->links() }}
</div>
