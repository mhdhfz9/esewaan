<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Support\HqJrpChecklist;
use Illuminate\Foundation\Http\FormRequest;

class ApproveHqApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var RentalContract|null $contract */
        $contract = $this->route('contract');

        return $user?->isAdminHq() === true
            && $contract?->isPendingHqReview() === true
            && $contract?->hasPendingWithdrawalRequest() === false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'jrp_checklist' => ['sometimes', 'array'],
            'jrp_checklist.dates' => ['sometimes', 'array'],
            'jrp_checklist.dates.*' => ['nullable', 'date'],
        ];

        foreach (HqJrpChecklist::allKeys() as $key) {
            $rules["jrp_checklist.{$key}"] = ['sometimes'];
        }

        return $rules;
    }
}
