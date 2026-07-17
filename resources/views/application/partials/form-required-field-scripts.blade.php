@push('scripts')
<script>
window.ApplicationFormValidation = window.ApplicationFormValidation || (function () {
    const EMPTY_CLASS = 'glass-input-required-empty';
    const ERROR_CLASS = 'glass-input-required-error';
    const FORM_ERROR_CLASS = 'form-showing-required-errors';

    function isEditableRequiredField(field) {
        if (!field || field.disabled || field.readOnly) {
            return false;
        }

        if (!field.required) {
            return false;
        }

        if (field.type === 'hidden' || field.type === 'submit' || field.type === 'button' || field.type === 'checkbox' || field.type === 'radio') {
            return false;
        }

        return true;
    }

    function isFieldEmpty(field) {
        if (field.tagName === 'SELECT') {
            return (field.value || '').trim() === '';
        }

        return (field.value || '').trim() === '';
    }

    function requiredFields(form) {
        if (!form) {
            return [];
        }

        return Array.from(form.querySelectorAll('input, textarea, select')).filter(isEditableRequiredField);
    }

    function clearFieldState(field) {
        field.classList.remove(EMPTY_CLASS, ERROR_CLASS);
    }

    function syncFieldState(form, field) {
        clearFieldState(field);

        if (!isFieldEmpty(field)) {
            return;
        }

        if (form?.classList.contains(FORM_ERROR_CLASS)) {
            field.classList.add(ERROR_CLASS);
        } else {
            field.classList.add(EMPTY_CLASS);
        }
    }

    function refresh(form) {
        requiredFields(form).forEach((field) => syncFieldState(form, field));
    }

    function validate(form, options = {}) {
        const showErrors = options.showErrors !== false;
        const emptyFields = requiredFields(form).filter(isFieldEmpty);

        if (showErrors) {
            form?.classList.toggle(FORM_ERROR_CLASS, emptyFields.length > 0);
        }

        requiredFields(form).forEach((field) => {
            clearFieldState(field);

            if (!isFieldEmpty(field)) {
                return;
            }

            if (showErrors) {
                field.classList.add(ERROR_CLASS);
            } else {
                field.classList.add(EMPTY_CLASS);
            }
        });

        if (emptyFields.length > 0 && showErrors) {
            emptyFields[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            emptyFields[0].focus({ preventScroll: true });
        }

        if (emptyFields.length === 0) {
            form?.classList.remove(FORM_ERROR_CLASS);
        }

        return emptyFields.length === 0;
    }

    function bind(form) {
        if (!form || form.dataset.requiredHighlightBound === '1') {
            return;
        }

        form.dataset.requiredHighlightBound = '1';

        requiredFields(form).forEach((field) => {
            const sync = () => {
                syncFieldState(form, field);

                if (form.classList.contains(FORM_ERROR_CLASS) && requiredFields(form).every((item) => !isFieldEmpty(item))) {
                    form.classList.remove(FORM_ERROR_CLASS);
                }
            };

            field.addEventListener('input', sync);
            field.addEventListener('change', sync);
            field.addEventListener('blur', sync);
        });

        refresh(form);
    }

    return {
        bind,
        refresh,
        validate,
        requiredFields,
        isFieldEmpty,
    };
})();
</script>
@endpush
