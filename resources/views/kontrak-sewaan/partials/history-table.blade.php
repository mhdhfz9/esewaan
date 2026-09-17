@php
    $search = $search ?? '';
@endphp
<p id="kontrak-sewaan-count" class="text-sm text-slate-600">
    Menunjukkan <strong class="text-slate-800">{{ $contracts->count() }}</strong> daripada <strong class="text-slate-800">{{ $contracts->total() }}</strong> kontrak tamat tempoh
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
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Tarikh Tamat</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($contracts as $c)
                    @php
                        $endDate = $c->contractEndDate();
                    @endphp
                    <tr class="glass-row-neutral">
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
                            <span class="font-medium text-red-600">
                                {{ $endDate?->format('d/m/Y') ?? '–' }}
                            </span>
                            <p class="mt-0.5 text-xs text-red-500">{{ $c->daysUntilContractEndLabel() }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center">
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
                            Tiada kontrak tamat tempoh dijumpai.
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
