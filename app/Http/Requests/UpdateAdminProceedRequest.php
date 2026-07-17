<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Support\AdminProceedSteps;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAdminProceedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $contract = $this->route('contract');

        if (! $user?->isAdminNegeri() || ! filled($user->negeri)) {
            return false;
        }

        if (! $contract instanceof RentalContract) {
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
        /** @var RentalContract $contract */
        $contract = $this->route('contract');
        $step = AdminProceedSteps::normalizeStep((int) $this->input('step', 1));
        $definition = AdminProceedSteps::definitionForStep($step);

        $rules = [
            'step' => ['required', 'integer', 'between:1,5'],
            'completed' => ['accepted'],
            'action' => ['required', 'in:next,finish,save'],
        ];

        if ($definition['has_notes'] ?? false) {
            $rules['notes'] = ['required', 'string', 'max:2000'];
        } else {
            $rules['notes'] = ['nullable', 'string', 'max:2000'];
        }

        if (! AdminProceedSteps::isStepActive($contract, $step)) {
            $rules['notes'] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'completed.accepted' => 'Sila tandakan pengesahan bahawa langkah ini telah selesai dilaksanakan.',
            'notes.required' => 'Sila nyatakan senarai agensi yang menerima surat.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var RentalContract $contract */
            $contract = $this->route('contract');
            $step = AdminProceedSteps::normalizeStep((int) $this->input('step', 1));

            if (! AdminProceedSteps::isStepActive($contract, $step)) {
                $validator->errors()->add('step', 'Langkah ini tidak terpakai untuk kategori permohonan dipilih.');

                return;
            }

            $activeSteps = AdminProceedSteps::activeStepsFor($contract);
            $stepIndex = array_search($step, $activeSteps, true);

            if ($stepIndex !== false && $stepIndex > 0) {
                for ($index = 0; $index < $stepIndex; $index++) {
                    if (! AdminProceedSteps::isStepCompleted($contract, $activeSteps[$index])) {
                        $validator->errors()->add('step', 'Sila lengkapkan langkah sebelumnya terlebih dahulu.');

                        break;
                    }
                }
            }
        });
    }
}
