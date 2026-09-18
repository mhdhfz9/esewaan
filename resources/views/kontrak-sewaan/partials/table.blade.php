<p id="kontrak-sewaan-count" class="text-sm text-slate-600">
    Menunjukkan <strong class="text-slate-800">{{ $contracts->count() }}</strong> daripada <strong class="text-slate-800">{{ $contracts->total() }}</strong> kontrak aktif
</p>

<div class="glass-card glass-table overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[56rem] text-center text-sm">
            <thead>
                <tr class="glass-divider border-b">
                    <th class="min-w-[9rem] px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Nama Pemohon</th>
                    <th class="min-w-[12rem] px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Nama Premis</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Kategori</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Negeri</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Tarikh Disahkan</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Tempoh Kontrak</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Baki Tempoh</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($contracts as $c)
                    @php
                        $bakiLabel = $c->daysUntilContractEndLabel();
                        $isCritical = $c->isContractEndWithinThreeMonths();
                        $isWarning = ! $isCritical && $c->isContractEndWithinEightMonths();
                        $isUnseen = $c->isUnseenBy(auth()->user());
                    @endphp
                    <tr @class([
                        'glass-row-hover',
                        'glass-row-unseen' => $isUnseen,
                        'bg-red-50/80' => $isCritical,
                        'bg-amber-50/80' => $isWarning,
                    ])>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $c->displayAdminNegeriName() }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $c->displayPremisePtjName() }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ \App\Support\ApplicationCategories::label($c->kategori_permohonan) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $c->premise?->negeri ?? '–' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $c->hq_approved_at?->format('d/m/Y') ?? '–' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $c->contractPeriodLabel() }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span @class([
                                'inline-flex rounded-md px-2 py-1 text-xs font-semibold',
                                'bg-red-100 text-red-700' => $isCritical,
                                'bg-amber-100 text-amber-800' => $isWarning,
                                'text-slate-600' => ! $isCritical && ! $isWarning,
                            ])>
                                {{ $bakiLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                @if(auth()->user()->isAdminNegeri())
                                    @if($c->hasPendingFollowUp())
                                        @php
                                            $followUp = $c->pendingFollowUp();
                                            $susulanLabel = $followUp?->followUpInProgressStatusLabel() ?? 'Dalam Tindakan Lanjutan/Pindah';
                                        @endphp
                                        <a
                                            href="{{ $followUp?->followUpWorkspaceUrl() }}"
                                            class="rounded-lg px-2.5 py-1 text-xs font-medium text-amber-700 underline decoration-amber-400/80 underline-offset-2 transition-colors hover:bg-amber-50 hover:text-amber-800"
                                            title="Buka borang {{ $susulanLabel }}"
                                        >{{ $susulanLabel }}</a>
                                    @else
                                        <form
                                            id="pindah-follow-up-form-{{ $c->id }}"
                                            method="POST"
                                            action="{{ route('kontrak-sewaan.follow-up', $c) }}"
                                        >
                                            @csrf
                                            <input type="hidden" name="kategori_permohonan" value="pindah">
                                            <button
                                                type="button"
                                                class="kontrak-confirm-trigger rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-800 transition-colors hover:bg-slate-100"
                                                data-confirm-title="Mulakan Permohonan Pindah"
                                                data-confirm-message="Anda pasti mahu memulakan permohonan Pindah untuk premis {{ $c->displayPremisePtjName() }}? Kontrak ini akan dikeluarkan dari senarai aktif sementara permohonan diproses."
                                                data-confirm-form="pindah-follow-up-form-{{ $c->id }}"
                                                data-confirm-button="Ya, Pindah"
                                                data-confirm-tone="primary"
                                            >Pindah</button>
                                        </form>
                                        <form
                                            id="lanjutan-follow-up-form-{{ $c->id }}"
                                            method="POST"
                                            action="{{ route('kontrak-sewaan.follow-up', $c) }}"
                                        >
                                            @csrf
                                            <input type="hidden" name="kategori_permohonan" value="lanjutan">
                                            <button
                                                type="button"
                                                class="kontrak-confirm-trigger rounded-lg border border-emerald-200 px-2.5 py-1 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-50"
                                                data-confirm-title="Mulakan Permohonan Lanjutan"
                                                data-confirm-message="Anda pasti mahu memulakan permohonan Lanjutan untuk premis {{ $c->displayPremisePtjName() }}? Kontrak ini akan dikeluarkan dari senarai aktif sementara permohonan diproses."
                                                data-confirm-form="lanjutan-follow-up-form-{{ $c->id }}"
                                                data-confirm-button="Ya, Lanjutan"
                                                data-confirm-tone="success"
                                            >Lanjutan</button>
                                        </form>
                                    @endif
                                @endif
                                <a
                                    href="{{ route('kontrak-sewaan.show', $c) }}"
                                    title="Lihat Kontrak"
                                    class="inline-flex rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800"
                                >
                                    <span class="sr-only">Lihat Kontrak</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                            Tiada kontrak aktif dijumpai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="kontrak-sewaan-pagination" class="mt-4">
    {{ $contracts->withQueryString()->links() }}
</div>
