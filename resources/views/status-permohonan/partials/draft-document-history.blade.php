@php
    $draftDocuments = $contract->documents
        ->where('jenis', \App\Models\ContractDocument::JENIS_DRAF_PERJANJIAN)
        ->sortByDesc('semakan_round');
@endphp

@if($draftDocuments->isNotEmpty())
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Sejarah Muat Naik Draf</p>
        <ul class="mt-2 space-y-2">
            @foreach($draftDocuments as $document)
                <li class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="shrink-0 font-medium text-indigo-700">{{ $document->semakanLabel() ?? 'Draf Perjanjian' }}</span>
                        <span class="{{ $document->semakanStatusBadgeClass() }} text-[11px]">
                            {{ $document->semakanStatusLabel() }}
                        </span>
                    </div>
                    <a
                        href="{{ $document->downloadUrl() }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="truncate text-slate-600 underline-offset-2 hover:text-indigo-600 hover:underline"
                    >
                        {{ $document->nama_fail }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
