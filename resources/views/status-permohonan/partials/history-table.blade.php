@php
    $search = $search ?? '';
@endphp
<p id="status-permohonan-count" class="text-sm text-slate-600">
    Menunjukkan <strong class="text-slate-800">{{ $contracts->count() }}</strong> daripada <strong class="text-slate-800">{{ $contracts->total() }}</strong> permohonan dipadam
</p>

<div class="glass-card glass-table overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full min-w-[52rem] text-sm">
        <thead>
            <tr class="glass-divider border-b">
                <th class="min-w-[12rem] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nama Premis</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kategori</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Negeri</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status Semasa Dipadam</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Dipadam Oleh</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tarikh Padam</th>
                <th class="min-w-[14rem] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Alasan Padam</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($contracts as $c)
                @php
                    $namaPtj = $c->displayNamaPtj();
                @endphp
                <tr class="glass-row-neutral">
                    <td class="min-w-[12rem] px-4 py-3.5 text-sm text-slate-800">
                        <p class="font-medium text-slate-800" title="{{ $namaPtj }}">{{ $namaPtj }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                        {{ \App\Support\ApplicationCategories::label($c->kategori_permohonan) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $c->premise?->negeri ?? '–' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $c->workflowLabel() }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                        <div>
                            <p class="font-medium text-slate-800">{{ $c->deletedBy?->name ?? '–' }}</p>
                            @if($c->deletedBy?->negeri)
                                <p class="text-xs text-slate-500">{{ $c->deletedBy->negeri }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                        {{ $c->deleted_at?->format('d/m/Y H:i') ?? '–' }}
                    </td>
                    <td class="min-w-[14rem] px-4 py-3 text-sm text-slate-700">
                        <p class="whitespace-pre-wrap leading-relaxed">{{ $c->delete_reason ?? '–' }}</p>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                        Tiada rekod sejarah permohonan dipadam.
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
