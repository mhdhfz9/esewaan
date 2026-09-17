@push('scripts')
<script>
(function () {
    const form = document.getElementById(@json($formId));
    if (!form?.dataset.autosaveUrl) {
        return;
    }

    const autosaveUrl = form.dataset.autosaveUrl;
    const saveUrl = @json($saveUrl);
    const redirectWhenComplete = @json($redirectWhenComplete);
    const useFullFormAutosave = @json($useFullFormAutosave ?? false);
    const activeSteps = JSON.parse(form.dataset.activeSteps || '[]');
    const saveButton = document.getElementById('form-save-button');
    const submitHqWrap = document.getElementById('submit-hq-wrap');
    const currentStepInput = document.getElementById('current-step-input');
    const formSaveStatus = document.getElementById('form-save-status');
    const proceedSaveStatus = document.getElementById('proceed-save-status');
    const progressBar = document.getElementById('proceed-progress-bar');
    const progressCount = document.getElementById('proceed-progress-count');
    const remarkRequired = form.dataset.remarkRequired === '1';
    const remarkField = document.getElementById('remark');
    let currentStep = Number(form.dataset.initialStep || 1);
    let autosaveTimer = null;
    let stepAutosaveTimer = null;
    let autosaveInFlight = null;
    let autosavePending = false;
    let lastAutosaveAt = 0;
    const AUTOSAVE_DEBOUNCE_MS = 50;
    const serverStepCompletion = {};

    function showStatus(element, message) {
        if (!element) return;
        element.textContent = message;
        element.classList.remove('hidden');
        window.setTimeout(() => element.classList.add('hidden'), 2500);
    }

    function isLastStep(stepNumber) {
        return activeSteps.length > 0 && stepNumber === activeSteps[activeSteps.length - 1];
    }

    function hasRequiredRemark() {
        if (!remarkRequired) {
            return true;
        }

        return (remarkField?.value || '').trim() !== '';
    }

    function updateSaveButtonLabel() {
        if (!saveButton) return;
        saveButton.textContent = 'Seterusnya';
    }

    function updateSubmitHqVisibility(progress = null) {
        if (!submitHqWrap) {
            return;
        }

        const resolvedProgress = progress ?? calculateLocalProgress();
        const showSubmit = resolvedProgress.allCompleted && hasRequiredRemark();

        submitHqWrap.classList.toggle('hidden', !showSubmit);
        submitHqWrap.classList.toggle('contents', showSubmit);
    }

    function syncCurrentStepInput() {
        if (currentStepInput) {
            currentStepInput.value = String(currentStep);
        }
    }

    function todayDateValue() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }

    function syncAgencyDateVisibility(checkbox, autoSetToday = false) {
        if (!checkbox?.dataset.requiresDate) {
            return;
        }

        const wrap = checkbox.closest('.proceed-step-panel')
            ?.querySelector(`.proceed-agency-date-wrap[data-agency-date-for="${checkbox.dataset.agencyKey}"]`);
        const dateInput = wrap?.querySelector('.proceed-agency-date');

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

    function agenciesReady(panel) {
        const agencyBoxes = [...panel.querySelectorAll('.proceed-agency-checkbox')];

        if (agencyBoxes.length === 0) {
            return false;
        }

        return agencyBoxes.every((checkbox) => {
            if (!checkbox.checked) {
                return false;
            }

            if (checkbox.dataset.requiresDate !== '1') {
                return true;
            }

            const dateInput = panel.querySelector(
                `.proceed-agency-date-wrap[data-agency-date-for="${checkbox.dataset.agencyKey}"] .proceed-agency-date`
            );

            return (dateInput?.value || '').trim() !== '';
        });
    }

    function updateAgencyConfirmation(panel) {
        if (!panel?.dataset.requiresAgencies) {
            return;
        }

        const hint = panel.querySelector('.proceed-agency-hint');

        if (hint) {
            hint.classList.toggle('hidden', agenciesReady(panel));
        }
    }

    function updateAllAgencyConfirmations() {
        form.querySelectorAll('.proceed-step-panel[data-requires-agencies]').forEach(updateAgencyConfirmation);
    }

    function collectProceedSteps(stepNumber = null) {
        const steps = {};
        form.querySelectorAll('.proceed-step-panel').forEach((panel) => {
            if (stepNumber !== null && Number(panel.dataset.step) !== Number(stepNumber)) {
                return;
            }

            const key = panel.dataset.stepKey;
            const checkbox = panel.querySelector('.proceed-step-completed');
            const accurateCheckbox = panel.querySelector('.proceed-step-confirmed-accurate');
            const promisCheckbox = panel.querySelector('.proceed-step-confirmed-promis');
            const accurateChecked = accurateCheckbox?.checked ?? false;
            const promisChecked = promisCheckbox?.checked ?? false;
            const completedChecked = panel.dataset.confirmationsCompleteStep === '1'
                ? accurateChecked && promisChecked
                : (checkbox?.checked ?? false);
            const notes = panel.querySelector('.proceed-step-notes');
            const noRujukan = panel.querySelector('.proceed-step-no-rujukan');
            const tarikhSurat = panel.querySelector('.proceed-step-tarikh-surat');
            const agencies = {};
            const agencyDates = {};

            panel.querySelectorAll('.proceed-agency-checkbox').forEach((agencyCheckbox) => {
                agencies[agencyCheckbox.dataset.agencyKey] = agencyCheckbox.checked ? '1' : '0';
            });

            panel.querySelectorAll('.proceed-agency-date').forEach((dateInput) => {
                if (dateInput.disabled) {
                    return;
                }

                agencyDates[dateInput.dataset.agencyKey] = dateInput.value || '';
            });

            steps[key] = {
                completed: completedChecked ? '1' : '0',
                confirmed_accurate: accurateChecked ? '1' : '0',
                confirmed_promis: promisChecked ? '1' : '0',
                notes: notes?.value ?? '',
                no_rujukan: noRujukan?.value ?? '',
                tarikh_surat: tarikhSurat?.value ?? '',
                agencies,
                agency_dates: agencyDates,
            };
        });
        return steps;
    }

    function appendProceedSteps(formData, stepNumber = null) {
        const steps = collectProceedSteps(stepNumber);
        Object.entries(steps).forEach(([key, value]) => {
            formData.append(`proceed_steps[${key}][completed]`, value.completed);
            formData.append(`proceed_steps[${key}][confirmed_accurate]`, value.confirmed_accurate);
            formData.append(`proceed_steps[${key}][confirmed_promis]`, value.confirmed_promis);
            formData.append(`proceed_steps[${key}][notes]`, value.notes);
            formData.append(`proceed_steps[${key}][no_rujukan]`, value.no_rujukan);
            formData.append(`proceed_steps[${key}][tarikh_surat]`, value.tarikh_surat);
            Object.entries(value.agencies).forEach(([agencyKey, checked]) => {
                formData.append(`proceed_steps[${key}][agencies][${agencyKey}]`, checked);
            });
            Object.entries(value.agency_dates).forEach(([agencyKey, dateValue]) => {
                formData.append(`proceed_steps[${key}][agency_dates][${agencyKey}]`, dateValue);
            });
        });
    }

    function isPanelStepComplete(panel) {
        if (!panel) {
            return false;
        }

        const completed = panel.dataset.confirmationsCompleteStep === '1'
            ? true
            : (panel.querySelector('.proceed-step-completed')?.checked ?? false);
        const accurate = panel.querySelector('.proceed-step-confirmed-accurate')?.checked ?? false;
        const promis = panel.querySelector('.proceed-step-confirmed-promis')?.checked ?? false;
        const noRujukanInput = panel.querySelector('.proceed-step-no-rujukan');
        const tarikhSuratInput = panel.querySelector('.proceed-step-tarikh-surat');
        const noRujukan = (noRujukanInput?.value ?? '').trim();
        const tarikhSurat = (tarikhSuratInput?.value ?? '').trim();
        const referenceOk = (!noRujukanInput || Boolean(noRujukan))
            && (!tarikhSuratInput || Boolean(tarikhSurat));

        if (!completed || !accurate || !promis || !referenceOk) {
            return false;
        }

        if (panel.dataset.requiresAgencies === '1') {
            return agenciesReady(panel);
        }

        return true;
    }

    const ERROR_CLASS = 'glass-input-required-error';
    const CHECKBOX_ERROR_CLASS = 'proceed-required-error';
    const INCOMPLETE_REQUIRED_MESSAGE = 'Sila lengkapkan semua ruangan yang wajib diisi.';

    function findFirstIncompleteStepNumber() {
        for (const stepNumber of activeSteps) {
            const panel = form.querySelector(`.proceed-step-panel[data-step="${stepNumber}"]`);

            if (!isPanelStepComplete(panel)) {
                return Number(stepNumber);
            }
        }

        return null;
    }

    function clearProceedRequiredHighlights(root = form) {
        root.querySelectorAll(`.${ERROR_CLASS}`).forEach((field) => {
            if (field.matches('.proceed-step-no-rujukan, .proceed-step-tarikh-surat, .proceed-agency-date')) {
                field.classList.remove(ERROR_CLASS);
            }
        });
        root.querySelectorAll(`.${CHECKBOX_ERROR_CLASS}`).forEach((element) => {
            element.classList.remove(CHECKBOX_ERROR_CLASS);
        });
    }

    function highlightIncompletePanel(panel) {
        if (!panel) {
            return;
        }

        clearProceedRequiredHighlights(panel);

        const noRujukan = panel.querySelector('.proceed-step-no-rujukan');
        if (noRujukan && !(noRujukan.value || '').trim()) {
            noRujukan.classList.add(ERROR_CLASS);
        }

        const tarikhSurat = panel.querySelector('.proceed-step-tarikh-surat');
        if (tarikhSurat && !(tarikhSurat.value || '').trim()) {
            tarikhSurat.classList.add(ERROR_CLASS);
        }

        [
            '.proceed-step-confirmed-accurate',
            '.proceed-step-confirmed-promis',
            '.proceed-step-completed',
        ].forEach((selector) => {
            const checkbox = panel.querySelector(selector);
            if (checkbox && !checkbox.checked) {
                checkbox.closest('.proceed-step-checkbox-label')?.classList.add(CHECKBOX_ERROR_CLASS);
            }
        });

        if (panel.dataset.requiresAgencies === '1' && !agenciesReady(panel)) {
            panel.querySelector('.proceed-agency-checklist')?.classList.add(CHECKBOX_ERROR_CLASS);

            panel.querySelectorAll('.proceed-agency-checkbox').forEach((checkbox) => {
                if (!checkbox.checked) {
                    return;
                }

                if (checkbox.dataset.requiresDate !== '1') {
                    return;
                }

                const dateInput = panel.querySelector(
                    `.proceed-agency-date-wrap[data-agency-date-for="${checkbox.dataset.agencyKey}"] .proceed-agency-date`
                );

                if (dateInput && !(dateInput.value || '').trim()) {
                    dateInput.classList.add(ERROR_CLASS);
                }
            });

            updateAgencyConfirmation(panel);
        }

        const focusTarget = panel.querySelector(`.${ERROR_CLASS}`)
            || panel.querySelector(`.${CHECKBOX_ERROR_CLASS} input`)
            || panel.querySelector(`.${CHECKBOX_ERROR_CLASS}`);

        focusTarget?.scrollIntoView({ behavior: 'smooth', block: 'center' });

        if (focusTarget?.matches('input, textarea, select')) {
            focusTarget.focus({ preventScroll: true });
        }
    }

    function redirectToFirstIncompleteRequiredStep() {
        const incompleteStep = findFirstIncompleteStepNumber();

        if (incompleteStep === null) {
            return false;
        }

        currentStep = incompleteStep;
        updateStepperUi(currentStep, calculateLocalProgress());
        highlightIncompletePanel(form.querySelector(`.proceed-step-panel[data-step="${incompleteStep}"]`));
        showStatus(formSaveStatus, INCOMPLETE_REQUIRED_MESSAGE);

        return true;
    }

    function calculateLocalProgress() {
        let completedCount = 0;

        activeSteps.forEach((stepNumber) => {
            const panel = form.querySelector(`.proceed-step-panel[data-step="${stepNumber}"]`);
            if (isStepBadgeComplete(stepNumber, panel)) {
                completedCount++;
            }
        });

        const totalSteps = activeSteps.length;

        return {
            completedCount,
            totalSteps,
            progressPercent: totalSteps > 0 ? Math.round((completedCount / totalSteps) * 100) : 0,
            allCompleted: totalSteps > 0 && completedCount === totalSteps,
        };
    }

    function isStepBadgeComplete(stepNumber, panel = null) {
        panel ??= form.querySelector(`.proceed-step-panel[data-step="${stepNumber}"]`);
        const stepKey = panel?.dataset.stepKey;

        if (stepNumber !== currentStep && stepKey && Object.prototype.hasOwnProperty.call(serverStepCompletion, stepKey)) {
            return serverStepCompletion[stepKey] === true;
        }

        return isPanelStepComplete(panel);
    }

    function syncServerStepCompletion(steps, onlyKey = null) {
        if (!steps) {
            return;
        }

        Object.entries(steps).forEach(([key, state]) => {
            if (onlyKey !== null && key !== onlyKey) {
                return;
            }

            serverStepCompletion[key] = state.completed === true;
        });
    }

    function applyStepState(key, state) {
        const panel = form.querySelector(`.proceed-step-panel[data-step-key="${key}"]`);
        if (!panel) return;

        const checkbox = panel.querySelector('.proceed-step-completed');
        const accurateCheckbox = panel.querySelector('.proceed-step-confirmed-accurate');
        const promisCheckbox = panel.querySelector('.proceed-step-confirmed-promis');
        const notes = panel.querySelector('.proceed-step-notes');
        const noRujukan = panel.querySelector('.proceed-step-no-rujukan');
        const tarikhSurat = panel.querySelector('.proceed-step-tarikh-surat');

        if (checkbox) {
            checkbox.checked = state.marked_complete !== undefined
                ? !!state.marked_complete
                : !!state.completed;
        }

        if (accurateCheckbox && state.confirmed_accurate !== undefined) {
            accurateCheckbox.checked = !!state.confirmed_accurate;
        }

        if (promisCheckbox && state.confirmed_promis !== undefined) {
            promisCheckbox.checked = !!state.confirmed_promis;
        }

        if (notes && state.notes !== undefined) {
            notes.value = state.notes ?? '';
        }

        if (noRujukan && state.no_rujukan !== undefined) {
            noRujukan.value = state.no_rujukan ?? '';
        }

        if (tarikhSurat && state.tarikh_surat !== undefined) {
            tarikhSurat.value = state.tarikh_surat ?? '';
        }

        if (state.agencies) {
            Object.entries(state.agencies).forEach(([agencyKey, checked]) => {
                const agencyCheckbox = panel.querySelector(`.proceed-agency-checkbox[data-agency-key="${agencyKey}"]`);
                if (agencyCheckbox) {
                    agencyCheckbox.checked = !!checked;
                    syncAgencyDateVisibility(agencyCheckbox);
                }
            });
        }

        if (state.agency_dates) {
            Object.entries(state.agency_dates).forEach(([agencyKey, dateValue]) => {
                const dateInput = panel.querySelector(
                    `.proceed-agency-date-wrap[data-agency-date-for="${agencyKey}"] .proceed-agency-date`
                );

                if (dateInput && !dateInput.disabled) {
                    dateInput.value = dateValue ?? '';
                }
            });
        }

        updateAgencyConfirmation(panel);
    }

    function updateStepperUi(activeStep, progress, steps, options = {}) {
        const onlyApplyStepKey = options.onlyApplyStepKey ?? null;

        document.querySelectorAll('.proceed-step-panel').forEach((panel) => {
            panel.classList.toggle('hidden', Number(panel.dataset.step) !== activeStep);
        });

        if (steps) {
            syncServerStepCompletion(steps, onlyApplyStepKey);

            if (onlyApplyStepKey && steps[onlyApplyStepKey]) {
                applyStepState(onlyApplyStepKey, steps[onlyApplyStepKey]);
            }
        }

        const resolvedProgress = progress ?? calculateLocalProgress();
        const checkSvg = '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>';
        const badgeActive = ['glass-step-active', 'ring-4', 'ring-blue-200/80'];
        const badgeCompleted = ['glass-step-done'];
        const badgeIncomplete = ['glass-step-pending'];
        const badgeShared = ['relative', 'z-10', 'flex', 'h-8', 'w-8', 'shrink-0', 'items-center', 'justify-center', 'rounded-full', 'text-xs', 'font-semibold', 'transition-all', 'focus:outline-none', 'focus-visible:ring-2', 'focus-visible:ring-offset-2', 'focus-visible:ring-blue-500'];
        const cardActive = ['glass-step-card-active'];
        const cardIncomplete = ['glass-step-card-pending'];
        const cardCompleted = ['glass-step-card-done'];

        document.querySelectorAll('[data-step-item]').forEach((item) => {
            const stepNumber = Number(item.dataset.stepItem);
            const displayNumber = item.dataset.displayNumber || String(stepNumber);
            const panel = form.querySelector(`.proceed-step-panel[data-step="${stepNumber}"]`);
            const isCompleted = isStepBadgeComplete(stepNumber, panel);
            const isCurrent = stepNumber === activeStep;

            const badge = item.querySelector('.proceed-step-badge');
            if (badge) {
                badge.className = 'proceed-step-trigger proceed-step-badge ' + badgeShared.join(' ');
                badge.setAttribute('aria-current', isCurrent ? 'step' : 'false');

                if (isCurrent) {
                    badge.classList.add(...badgeActive);
                    badge.innerHTML = displayNumber;
                } else if (isCompleted) {
                    badge.classList.add(...badgeCompleted);
                    badge.innerHTML = checkSvg;
                } else {
                    badge.classList.add(...badgeIncomplete);
                    badge.innerHTML = displayNumber;
                }
            }

            const card = item.querySelector('.proceed-step-card');
            if (card) {
                card.className = 'proceed-step-trigger proceed-step-card group block w-full rounded-2xl border px-3.5 py-3 text-left transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500';
                if (isCurrent) {
                    card.classList.add(...cardActive);
                } else if (isCompleted) {
                    card.classList.add(...cardCompleted);
                } else {
                    card.classList.add(...cardIncomplete);
                }
                card.setAttribute('aria-current', isCurrent ? 'step' : 'false');
            }

            const status = item.querySelector('[data-step-status]');
            if (status) {
                status.className = 'text-[11px] font-medium uppercase tracking-wide';
                if (isCurrent) {
                    status.classList.add('text-blue-700');
                    status.textContent = 'Sedang dijalankan';
                } else if (isCompleted) {
                    status.classList.add('text-emerald-600');
                    status.textContent = 'Selesai';
                } else {
                    status.classList.add('text-amber-600');
                    status.textContent = 'Belum selesai';
                }
            }

            const title = item.querySelector('[data-step-title]');
            if (title) {
                title.className = 'mt-0.5 text-sm font-medium leading-snug';
                if (isCurrent) {
                    title.classList.add('text-slate-900', 'group-hover:text-slate-900');
                } else if (isCompleted) {
                    title.classList.add('text-emerald-800', 'group-hover:text-emerald-900');
                } else {
                    title.classList.add('text-slate-700', 'group-hover:text-slate-900');
                }
            }

            const connector = item.querySelector('.proceed-step-connector');
            if (connector) {
                connector.className = 'proceed-step-connector my-1 w-0.5 flex-1 min-h-[1.75rem] rounded-full';
                connector.classList.add(isCompleted ? 'bg-emerald-300' : 'bg-amber-300');
            }
        });

        if (progressBar && progressCount) {
            const isComplete = resolvedProgress.progressPercent >= 100;
            progressBar.style.width = `${resolvedProgress.progressPercent}%`;
            progressBar.classList.remove('glass-progress-fill', 'glass-progress-fill-amber', 'glass-progress-fill-emerald');
            if (isComplete) {
                progressBar.classList.add('glass-progress-fill-emerald');
                progressCount.className = 'font-semibold text-emerald-700';
            } else if (resolvedProgress.progressPercent > 0) {
                progressBar.classList.add('glass-progress-fill-amber');
                progressCount.className = 'text-slate-500';
            } else {
                progressBar.classList.add('glass-progress-fill');
                progressCount.className = 'text-slate-500';
            }
            progressCount.textContent = `${resolvedProgress.progressPercent}%`;

            const readyStatus = document.getElementById('proceed-ready-status');
            if (readyStatus) {
                readyStatus.textContent = 'Permohonan sedia untuk dihantar ke Ibu Pejabat';
                readyStatus.classList.toggle('hidden', !isComplete);
            }
        }

        updateSaveButtonLabel();
        updateSubmitHqVisibility(resolvedProgress);
        syncCurrentStepInput();
    }

    async function autosaveDraft(showMessage = true, stepNumber = currentStep) {
        if (autosaveInFlight) {
            autosavePending = true;
            return autosaveInFlight;
        }

        let formData;

        if (useFullFormAutosave) {
            formData = new FormData(form);
            [...formData.keys()].forEach((key) => {
                if (key.startsWith('proceed_steps')) {
                    formData.delete(key);
                }
            });
            appendProceedSteps(formData, stepNumber);
        } else {
            formData = new FormData();
            appendProceedSteps(formData, stepNumber);
        }

        formData.set('_method', 'PATCH');
        formData.set('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

        autosaveInFlight = fetch(autosaveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: formData,
        })
            .then(async (response) => {
                if (response.status === 429) {
                    throw new Error('Terlalu banyak permintaan. Sila tunggu sebentar dan cuba lagi.');
                }

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal menyimpan draf.');
                }

                lastAutosaveAt = Date.now();
                const savedKey = form.querySelector(`.proceed-step-panel[data-step="${stepNumber}"]`)?.dataset.stepKey;
                if (data.steps && savedKey) {
                    syncServerStepCompletion(data.steps, savedKey);
                }
                updateStepperUi(currentStep, data.progress ?? calculateLocalProgress());
                if (showMessage) {
                    showStatus(proceedSaveStatus ?? formSaveStatus, data.message || 'Draf disimpan.');
                }
                return data;
            })
            .catch((error) => {
                if (showMessage) {
                    showStatus(proceedSaveStatus ?? formSaveStatus, error.message || 'Gagal menyimpan draf.');
                }
                throw error;
            })
            .finally(() => {
                autosaveInFlight = null;

                if (autosavePending) {
                    autosavePending = false;
                    autosaveDraft(false, currentStep);
                }
            });

        return autosaveInFlight;
    }

    function scheduleAutosave(immediate = false) {
        clearTimeout(autosaveTimer);

        if (immediate) {
            autosaveDraft(false);
            return;
        }

        autosaveTimer = window.setTimeout(() => autosaveDraft(false), AUTOSAVE_DEBOUNCE_MS);
    }

    function handleProceedFieldChange(immediateAutosave = false) {
        updateStepperUi(currentStep, calculateLocalProgress());
        scheduleAutosave(immediateAutosave);
    }

    function isAutosaveFormField(field) {
        if (!field || field.disabled || field.readOnly) {
            return false;
        }

        if (field.type === 'hidden' || field.type === 'submit' || field.type === 'button') {
            return false;
        }

        if (!field.name) {
            return false;
        }

        if (field.name === '_token' || field.name === '_method' || field.name === 'current_step') {
            return false;
        }

        if (field.name.startsWith('proceed_steps')) {
            return false;
        }

        return true;
    }

    function bindFormFieldAutosave() {
        if (!useFullFormAutosave) {
            return;
        }

        form.querySelectorAll('input, textarea, select').forEach((field) => {
            if (!isAutosaveFormField(field) || field.classList.contains('proceed-step-notes')) {
                return;
            }

            if (field.matches('textarea, input[type="text"], input[type="number"], input[type="email"], input[type="search"], input:not([type])')) {
                field.addEventListener('input', () => scheduleAutosave(false));
                field.addEventListener('blur', () => scheduleAutosave(true));
                return;
            }

            field.addEventListener('change', () => scheduleAutosave(true));
        });
    }

    async function switchStep(stepNumber) {
        if (stepNumber === currentStep) {
            return;
        }

        const leavingStep = currentStep;
        clearTimeout(autosaveTimer);
        clearTimeout(stepAutosaveTimer);

        try {
            await autosaveDraft(false, leavingStep);
        } catch (error) {
            // Keep navigation usable even if draft sync fails.
        }

        currentStep = stepNumber;
        updateStepperUi(currentStep, calculateLocalProgress());
    }

    document.querySelectorAll('[data-proceed-step]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            switchStep(Number(trigger.dataset.proceedStep));
        });
    });

    form.querySelectorAll('.proceed-agency-checkbox').forEach((field) => {
        syncAgencyDateVisibility(field);
        field.addEventListener('change', () => {
            syncAgencyDateVisibility(field, field.checked);
            clearProceedRequiredHighlights(field.closest('.proceed-step-panel') ?? form);
            updateAllAgencyConfirmations();
            handleProceedFieldChange(true);
        });
    });

    form.querySelectorAll('.proceed-agency-date').forEach((field) => {
        field.addEventListener('change', () => {
            field.classList.remove(ERROR_CLASS);
            updateAllAgencyConfirmations();
            handleProceedFieldChange(true);
        });
        field.addEventListener('input', () => {
            field.classList.remove(ERROR_CLASS);
            updateAllAgencyConfirmations();
            updateStepperUi(currentStep, calculateLocalProgress());
            scheduleAutosave(false);
        });
    });

    form.querySelectorAll('.proceed-step-completed, .proceed-step-confirmed-accurate, .proceed-step-confirmed-promis').forEach((field) => {
        field.addEventListener('change', () => {
            clearProceedRequiredHighlights(field.closest('.proceed-step-panel') ?? form);
            handleProceedFieldChange(true);
        });
    });

    form.querySelectorAll('.proceed-step-notes, .proceed-step-no-rujukan, .proceed-step-tarikh-surat').forEach((field) => {
        field.addEventListener('change', () => {
            clearProceedRequiredHighlights(field.closest('.proceed-step-panel') ?? form);
            handleProceedFieldChange(true);
        });
        field.addEventListener('input', () => {
            field.classList.remove(ERROR_CLASS);
            updateStepperUi(currentStep, calculateLocalProgress());
            scheduleAutosave(false);
        });
        field.addEventListener('blur', () => scheduleAutosave(true));
    });

    bindFormFieldAutosave();

    async function saveCurrentStep() {
        const currentPanel = form.querySelector(`.proceed-step-panel[data-step="${currentStep}"]`);

        if (saveButton) {
            saveButton.disabled = true;
        }

        syncCurrentStepInput();

        const formData = new FormData();
        appendProceedSteps(formData, currentStep);
        formData.append('current_step', String(currentStep));
        formData.append('_method', 'PUT');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

        try {
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: formData,
            });

            if (response.status === 429) {
                throw new Error('Terlalu banyak permintaan. Sila tunggu sebentar dan cuba lagi.');
            }

            const data = await response.json();

            if (!response.ok || !data.success) {
                const errorMessage = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : (data.message || 'Gagal menyimpan langkah tindakan.');
                throw new Error(errorMessage);
            }

            const savedStep = currentStep;

            if (data.allCompleted) {
                updateSubmitHqVisibility(data.progress ?? calculateLocalProgress());
                showStatus(formSaveStatus, data.message || 'Semua langkah tindakan selesai.');
                return;
            }

            if (data.nextStep && !data.allCompleted) {
                currentStep = Number(data.nextStep);
            }

            const savedKey = form.querySelector(`.proceed-step-panel[data-step="${savedStep}"]`)?.dataset.stepKey;

            if (data.steps && savedKey) {
                syncServerStepCompletion(data.steps, savedKey);
            }

            updateStepperUi(currentStep, data.progress, data.steps, {
                onlyApplyStepKey: savedKey ?? null,
            });
            showStatus(formSaveStatus, data.message || 'Langkah disimpan.');
        } catch (error) {
            showStatus(formSaveStatus, error.message || 'Gagal menyimpan langkah tindakan.');
        } finally {
            if (saveButton) {
                saveButton.disabled = false;
            }
        }
    }

    remarkField?.addEventListener('input', () => {
        updateSubmitHqVisibility();
    });

    if (saveButton) {
        saveButton.addEventListener('click', (event) => {
            event.preventDefault();

            if (isLastStep(currentStep) && redirectToFirstIncompleteRequiredStep()) {
                return;
            }

            if (window.ApplicationFormValidation && !window.ApplicationFormValidation.validate(form)) {
                showStatus(formSaveStatus, INCOMPLETE_REQUIRED_MESSAGE);
                return;
            }

            saveCurrentStep();
        });
    }

    window.ApplicationFormValidation?.bind(form);

    form.querySelectorAll('.proceed-step-panel').forEach((panel) => {
        if (panel.dataset.stepKey) {
            serverStepCompletion[panel.dataset.stepKey] = isPanelStepComplete(panel);
        }
    });

    updateAllAgencyConfirmations();
    updateStepperUi(currentStep, calculateLocalProgress());
})();
</script>
@endpush
