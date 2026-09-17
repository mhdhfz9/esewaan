<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Support\AdminProceedSteps;
use Illuminate\Foundation\Http\FormRequest;

class SaveAdminProceedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var RentalContract|null $contract */
        $contract = $this->route('contract');

        if (! $user?->isAdminNegeri() || ! filled($user->negeri) || ! $contract) {
            return false;
        }

        $contract->loadMissing('premise');

        return $contract->isPendingProceed()
            && $contract->premise?->negeri === $user->negeri;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_step' => ['required', 'integer', 'between:1,5'],
            'proceed_steps' => ['required', 'array'],
            'proceed_steps.*.completed' => ['sometimes', 'boolean'],
            'proceed_steps.*.confirmed_accurate' => ['sometimes', 'boolean'],
            'proceed_steps.*.confirmed_promis' => ['sometimes', 'boolean'],
            'proceed_steps.*.notes' => ['nullable', 'string', 'max:2000'],
            'proceed_steps.*.no_rujukan' => ['nullable', 'string', 'max:100'],
            'proceed_steps.*.tarikh_surat' => ['nullable', 'date'],
            'proceed_steps.*.agencies' => ['sometimes', 'array'],
            'proceed_steps.*.agencies.*' => ['sometimes', 'boolean'],
            'proceed_steps.*.agency_dates' => ['sometimes', 'array'],
            'proceed_steps.*.agency_dates.*' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proceed_steps.required' => 'Tiada maklumat langkah tindakan untuk disimpan.',
            'current_step.required' => 'Langkah semasa diperlukan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var RentalContract|null $contract */
        $contract = $this->route('contract');

        if (! $contract) {
            return;
        }

        $currentStep = AdminProceedSteps::normalizeStep((int) $this->input('current_step'));

        if (! AdminProceedSteps::isStepActive($contract, $currentStep)) {
            return;
        }

        $this->merge([
            'current_step' => $currentStep,
        ]);
    }
}
