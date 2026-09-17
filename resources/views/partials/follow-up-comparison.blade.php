@php
    $parent = $contract->parentContract;
    $parentPremise = $parent?->premise;
    $newPremise = $contract->premise;
    $parentRent = $parentPremise?->kadar_sewa ?? $parent?->kadar_sewa_bulanan;
    $newRent = $newPremise?->kadar_sewa ?? $contract->kadar_sewa_bulanan;
    $band = $contract->rentDifferenceBand();
    $difference = $contract->rentDifferenceFromParent();
@endphp

@if($parent)
    <div class="glass-panel-muted p-5">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                Perbandingan {{ \App\Support\ApplicationCategories::label($contract->kategori_permohonan) }}
            </span>
            @if($band)
                <span @class([
                    'rounded-full px-3 py-1 text-xs font-semibold',
                    'bg-emerald-100 text-emerald-700' => $band === 'below',
                    'bg-amber-100 text-amber-700' => $band === 'above',
                ])>
                    {{ $contract->rentDifferenceBandLabel() }}
                    @if($difference !== null)
                        ({{ $difference >= 0 ? '+' : '−' }}RM{{ number_format(abs($difference), 2) }})
                    @endif
                </span>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-white/70 bg-white/60">
            <table class="w-full text-sm">
                <thead>
                    <tr class="glass-divider border-b text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-2.5">Maklumat</th>
                        <th class="px-4 py-2.5">Kontrak Lama</th>
                        <th class="px-4 py-2.5">Permohonan Baharu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="px-4 py-2.5 font-medium text-slate-600">Nama Premis</td>
                        <td class="px-4 py-2.5 text-slate-700">{{ $parentPremise?->nama_ptj ?? '–' }}</td>
                        <td class="px-4 py-2.5 text-slate-900">{{ $newPremise?->nama_ptj ?? '–' }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium text-slate-600">Kadar Sewa (RM)</td>
                        <td class="px-4 py-2.5 text-slate-700">{{ $parentRent !== null ? number_format((float) $parentRent, 2) : '–' }}</td>
                        <td class="px-4 py-2.5 font-semibold text-slate-900">{{ $newRent !== null ? number_format((float) $newRent, 2) : '–' }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium text-slate-600">Tarikh Mula</td>
                        <td class="px-4 py-2.5 text-slate-700">{{ $parent?->tarikh_mula_tawaran?->format('d/m/Y') ?? '–' }}</td>
                        <td class="px-4 py-2.5 text-slate-900">{{ $contract->tarikh_mula_tawaran?->format('d/m/Y') ?? '–' }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium text-slate-600">Tarikh Akhir Tempoh Tawaran Penyewaan</td>
                        <td class="px-4 py-2.5 text-slate-700">{{ $parent?->sah_sehingga?->format('d/m/Y') ?? '–' }}</td>
                        <td class="px-4 py-2.5 text-slate-900">{{ $contract->sah_sehingga?->format('d/m/Y') ?? '–' }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium text-slate-600">Alamat Premis</td>
                        <td class="whitespace-pre-wrap px-4 py-2.5 text-slate-700">{{ $parentPremise?->alamat_penuh ?? '–' }}</td>
                        <td class="whitespace-pre-wrap px-4 py-2.5 text-slate-900">{{ $newPremise?->alamat_penuh ?? '–' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if(filled($contract->remark))
            <div class="mt-4">
                @include('partials.readonly-field', ['label' => 'Remark', 'value' => $contract->remark, 'multiline' => true])
            </div>
        @endif
    </div>
@endif
