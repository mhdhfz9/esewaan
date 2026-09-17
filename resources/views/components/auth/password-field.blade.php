@props([
    'label',
    'name',
    'inputId',
    'autocomplete' => 'new-password',
])

<div>
    <label for="{{ $inputId }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>
    <div class="relative">
        <input
            type="password"
            name="{{ $name }}"
            id="{{ $inputId }}"
            autocomplete="{{ $autocomplete }}"
            {{ $attributes->merge([
                'class' => 'auth-password-input glass-input w-full rounded-xl py-2.5 pl-3 pr-11 text-sm',
            ]) }}
        />
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-500 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            data-password-toggle="{{ $inputId }}"
            aria-label="Tunjuk atau sembunyikan kata laluan"
            tabindex="-1"
        >
            <svg data-pass-icon="password" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 5.25 12 5.25c4.638 0 8.573 2.26 9.963 6.5.093.206.093.439 0 .639C20.577 16.49 16.64 18.75 12 18.75c-4.638 0-8.573-2.26-9.963-6.5z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <svg data-pass-icon="text" class="hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        </button>
    </div>
</div>
