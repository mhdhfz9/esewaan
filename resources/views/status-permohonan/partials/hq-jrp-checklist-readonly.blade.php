@php
    use App\Support\HqJrpChecklist;
    use Illuminate\Support\Carbon;

    $checklist = is_array($contract->hq_jrp_checklist) ? $contract->hq_jrp_checklist : [];
    $dates = is_array($checklist['dates'] ?? null) ? $checklist['dates'] : [];
    $definitions = HqJrpChecklist::definitions();
    $compact = $compact ?? false;
@endphp

@if($checklist !== [])
<section @class([
    'glass-subtle rounded-xl border border-slate-200/60',
    'p-4' => ! $compact,
    'p-3' => $compact,
])>
    <h3 @class([
        'font-semibold leading-snug text-slate-900',
        'text-sm' => ! $compact,
        'text-xs' => $compact,
    ])>
        Mengemukakan Borang JRP kepada KDN untuk mendapatkan ulasan/kelulusan daripada agensi berikut
    </h3>

    <div @class(['mt-3 space-y-2' => ! $compact, 'mt-2 space-y-1.5' => $compact])>
        @foreach(HqJrpChecklist::allKeys() as $key)
            @php
                $isChecked = filter_var($checklist[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
                $requiresDate = HqJrpChecklist::requiresDate($key);
                $itemDate = $dates[$key] ?? null;
            @endphp
            <div class="flex flex-col gap-2 rounded-lg border border-slate-300 bg-white/50 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                <label class="flex min-w-0 items-start gap-2.5">
                    <input
                        type="checkbox"
                        disabled
                        @checked($isChecked)
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-slate-800"
                    >
                    <span @class([
                        'leading-relaxed',
                        'text-xs' => ! $compact,
                        'text-[11px]' => $compact,
                        'text-slate-800' => $isChecked,
                        'text-slate-500' => ! $isChecked,
                    ])>
                        {{ $definitions[$key] }}
                    </span>
                </label>
                @if($requiresDate && $isChecked)
                    <span @class([
                        'ml-6 sm:ml-0',
                        'text-xs' => ! $compact,
                        'text-[11px]' => $compact,
                        'font-medium text-slate-800' => filled($itemDate),
                        'text-slate-500' => ! filled($itemDate),
                    ])>
                        {{ filled($itemDate) ? Carbon::parse($itemDate)->format('d/m/Y') : 'Tiada tarikh' }}
                    </span>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif
