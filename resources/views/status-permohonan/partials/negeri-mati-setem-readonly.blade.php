@php
    use App\Support\NegeriMatiSetemAcknowledgements;

    $savedAcknowledgements = is_array($contract->negeri_mati_setem_acknowledgements)
        ? $contract->negeri_mati_setem_acknowledgements
        : [];
    $acknowledgementsCompleted = $acknowledgementsCompleted ?? $contract->isHqApproved();
@endphp

<div class="space-y-3">
    @foreach(NegeriMatiSetemAcknowledgements::labels() as $acknowledgementKey => $acknowledgementLabel)
        @php
            $isChecked = array_key_exists($acknowledgementKey, $savedAcknowledgements)
                ? filter_var($savedAcknowledgements[$acknowledgementKey], FILTER_VALIDATE_BOOLEAN)
                : $acknowledgementsCompleted;
        @endphp
        <label class="flex items-start gap-3 text-sm text-slate-700">
            <input
                type="checkbox"
                disabled
                @checked($isChecked)
                class="mt-0.5 h-4 w-4 shrink-0 cursor-default rounded border-slate-300 text-emerald-600"
            >
            <span @class([
                'text-slate-800' => $isChecked,
                'text-slate-500' => ! $isChecked,
            ])>{{ $acknowledgementLabel }}</span>
        </label>
    @endforeach
</div>
