@php
    $empty = $empty ?? 'Tiada maklumat direkodkan.';
    $multiline = $multiline ?? false;
    $displayValue = filled($value ?? null) ? trim((string) $value) : null;
@endphp

<div @isset($wrapperClass) class="{{ $wrapperClass }}" @endisset>
    <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
    <div @class([
        'glass-field-static text-sm text-slate-800',
        'min-h-[2.75rem] px-4 py-2.5 leading-relaxed' => ! $multiline,
        'min-h-[4.5rem] px-4 py-3.5 leading-6 whitespace-pre-wrap' => $multiline,
    ])>@if($displayValue !== null){{ $displayValue }}@else<span class="italic text-slate-400">{{ $empty }}</span>@endif</div>
</div>
