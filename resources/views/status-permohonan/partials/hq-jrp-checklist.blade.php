@php
    use App\Support\ApplicationCategories;
    use App\Support\HqJrpChecklist;

    $applicableKeys = HqJrpChecklist::applicableKeys($contract);
    $definitions = HqJrpChecklist::definitions();
    $savedChecklist = is_array($contract->hq_jrp_checklist) ? $contract->hq_jrp_checklist : [];
    $hasSavedChecklist = collect(HqJrpChecklist::allKeys())->contains(
        fn (string $key): bool => array_key_exists($key, $savedChecklist)
    );

    $oldChecklist = old('jrp_checklist');
    $hasOldChecklist = is_array($oldChecklist);
    $sourceChecklist = $hasOldChecklist
        ? $oldChecklist
        : ($hasSavedChecklist ? $savedChecklist : null);
    $sourceDates = is_array($sourceChecklist['dates'] ?? null) ? $sourceChecklist['dates'] : [];

    $epuChecked = $sourceChecklist !== null
        ? filter_var(($sourceChecklist[HqJrpChecklist::EPU] ?? false), FILTER_VALIDATE_BOOLEAN)
        : HqJrpChecklist::requiresEpuCheckbox($contract);

    $kpChecked = $sourceChecklist !== null
        ? filter_var(($sourceChecklist[HqJrpChecklist::KP] ?? false), FILTER_VALIDATE_BOOLEAN)
        : $contract->kategori_permohonan === ApplicationCategories::BARU;
@endphp

<section
    class="glass-subtle rounded-xl border border-slate-200/60 p-4"
    id="hq-jrp-checklist"
    data-autosave-url="{{ route('status-permohonan.checklist-autosave', $contract) }}"
>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <h3 class="text-sm font-semibold leading-snug text-slate-900">
            Mengemukakan Borang JRP kepada KDN untuk mendapatkan ulasan/kelulusan daripada agensi berikut
        </h3>
        <p id="hq-jrp-autosave-status" class="hidden text-xs text-slate-500" aria-live="polite"></p>
    </div>

    <div class="mt-3 space-y-2">
        @foreach($applicableKeys as $key)
            @php
                $isChecked = match ($key) {
                    HqJrpChecklist::KP => $kpChecked,
                    HqJrpChecklist::EPU => $epuChecked,
                    default => filter_var(($sourceChecklist[$key] ?? false), FILTER_VALIDATE_BOOLEAN),
                };
                $requiresDate = HqJrpChecklist::requiresDate($key);
                $itemDate = $sourceDates[$key] ?? null;
            @endphp
            <div class="flex flex-col gap-2 rounded-lg border border-slate-300 bg-white/50 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                <label class="flex min-w-0 cursor-pointer items-start gap-2.5">
                    <input
                        type="checkbox"
                        name="jrp_checklist[{{ $key }}]"
                        value="1"
                        data-jrp-key="{{ $key }}"
                        @checked($isChecked)
                        @if($requiresDate) data-requires-date="1" @endif
                        class="jrp-checklist-item mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-slate-800 focus:ring-slate-500"
                    >
                    <span class="text-xs leading-relaxed text-slate-700">{{ $definitions[$key] }}</span>
                </label>
                @if($requiresDate)
                    <div
                        class="jrp-checklist-date-wrap ml-6 sm:ml-0 {{ $isChecked ? '' : 'hidden' }}"
                        data-jrp-date-for="{{ $key }}"
                    >
                        <label class="sr-only" for="jrp_checklist_date_{{ $key }}">Tarikh {{ $definitions[$key] }}</label>
                        <input
                            type="date"
                            id="jrp_checklist_date_{{ $key }}"
                            name="jrp_checklist[dates][{{ $key }}]"
                            value="{{ $itemDate }}"
                            data-jrp-key="{{ $key }}"
                            class="jrp-checklist-date glass-input w-full rounded-lg px-2.5 py-1.5 text-sm sm:w-40"
                            @disabled(! $isChecked)
                        >
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @error('jrp_checklist')
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror
    @foreach($applicableKeys as $key)
        @error("jrp_checklist.{$key}")
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
        @error("jrp_checklist.dates.{$key}")
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endforeach
</section>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('hq-jrp-checklist');
    if (!root) {
        return;
    }

    const autosaveUrl = root.dataset.autosaveUrl;
    const statusEl = document.getElementById('hq-jrp-autosave-status');
    let autosaveTimer = null;
    let autosaveInFlight = null;
    let autosavePending = false;
    const AUTOSAVE_DEBOUNCE_MS = 250;

    function todayDateValue() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }

    function showAutosaveStatus(message) {
        if (!statusEl) {
            return;
        }

        statusEl.textContent = message;
        statusEl.classList.remove('hidden');
        window.setTimeout(() => statusEl.classList.add('hidden'), 2000);
    }

    function syncJrpDateVisibility(checkbox, autoSetToday = false) {
        if (!checkbox?.dataset.requiresDate) {
            return;
        }

        const wrap = root.querySelector(`.jrp-checklist-date-wrap[data-jrp-date-for="${checkbox.dataset.jrpKey}"]`);
        const dateInput = wrap?.querySelector('.jrp-checklist-date');

        if (!wrap || !dateInput) {
            return;
        }

        wrap.classList.toggle('hidden', !checkbox.checked);
        dateInput.disabled = !checkbox.checked;

        if (!checkbox.checked) {
            dateInput.value = '';
            return;
        }

        if (autoSetToday || !dateInput.value) {
            dateInput.value = todayDateValue();
        }
    }

    function collectChecklistFormData() {
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

        root.querySelectorAll('.jrp-checklist-item').forEach((checkbox) => {
            formData.append(`jrp_checklist[${checkbox.dataset.jrpKey}]`, checkbox.checked ? '1' : '0');
        });

        root.querySelectorAll('.jrp-checklist-date').forEach((dateInput) => {
            if (dateInput.disabled) {
                return;
            }

            formData.append(`jrp_checklist[dates][${dateInput.dataset.jrpKey}]`, dateInput.value || '');
        });

        return formData;
    }

    async function autosaveChecklist() {
        if (!autosaveUrl) {
            return;
        }

        if (autosaveInFlight) {
            autosavePending = true;
            return autosaveInFlight;
        }

        autosaveInFlight = fetch(autosaveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: collectChecklistFormData(),
        })
            .then(async (response) => {
                if (response.status === 429) {
                    throw new Error('Terlalu banyak permintaan. Sila tunggu sebentar.');
                }

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal menyimpan checklist.');
                }

                showAutosaveStatus(data.message || 'Checklist disimpan.');
                return data;
            })
            .catch((error) => {
                showAutosaveStatus(error.message || 'Gagal menyimpan checklist.');
                throw error;
            })
            .finally(() => {
                autosaveInFlight = null;

                if (autosavePending) {
                    autosavePending = false;
                    autosaveChecklist();
                }
            });

        return autosaveInFlight;
    }

    function scheduleAutosave() {
        clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(() => {
            autosaveChecklist();
        }, AUTOSAVE_DEBOUNCE_MS);
    }

    root.querySelectorAll('.jrp-checklist-item').forEach((checkbox) => {
        syncJrpDateVisibility(checkbox);
        checkbox.addEventListener('change', () => {
            syncJrpDateVisibility(checkbox, checkbox.checked);
            scheduleAutosave();
        });
    });

    root.querySelectorAll('.jrp-checklist-date').forEach((dateInput) => {
        dateInput.addEventListener('change', scheduleAutosave);
        dateInput.addEventListener('input', scheduleAutosave);
    });
})();
</script>
@endpush
