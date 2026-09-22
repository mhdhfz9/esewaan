<?php

namespace App\Services;

use App\Mail\ContractUnderThreeMonthsReminder;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class ContractUnderThreeMonthsReminderMailer
{
    public function send(): int
    {
        $contracts = $this->contractsNeedingReminder();

        if ($contracts->isEmpty()) {
            return 0;
        }

        $sent = 0;

        foreach ($this->groupContractsByRecipient($contracts) as $email => $recipientContracts) {
            Mail::to($email)->send(new ContractUnderThreeMonthsReminder($recipientContracts));
            $sent++;
        }

        return $sent;
    }

    /**
     * Active HQ-approved contracts under 3 months with no negeri follow-up started.
     *
     * @return Collection<int, RentalContract>
     */
    public function contractsNeedingReminder(): Collection
    {
        return RentalContract::query()
            ->hqApproved()
            ->notSuperseded()
            ->kontrakNotExpired()
            ->whereDoesntHave('followUpApplications')
            ->with(['premise', 'adminNegeriUser'])
            ->get()
            ->filter(fn (RentalContract $contract): bool => $contract->isContractEndWithinThreeMonths())
            ->sortBy(fn (RentalContract $contract) => $contract->daysUntilContractEnd())
            ->values();
    }

    /**
     * @param  Collection<int, RentalContract>  $contracts
     * @return array<string, Collection<int, RentalContract>>
     */
    public function groupContractsByRecipient(Collection $contracts): array
    {
        $grouped = [];

        foreach ($contracts as $contract) {
            foreach ($this->adminNegeriRecipients($contract) as $email) {
                $grouped[$email] ??= collect();
                $grouped[$email]->push($contract);
            }
        }

        return $grouped;
    }

    /**
     * @return list<string>
     */
    public function adminNegeriRecipients(RentalContract $contract): array
    {
        $testRecipient = config('mail.test_recipient');

        if (filled($testRecipient)) {
            return [$testRecipient];
        }

        $contract->loadMissing(['adminNegeriUser', 'premise']);

        if (
            filled($contract->adminNegeriUser?->email)
            && $contract->adminNegeriUser->is_active
            && $contract->adminNegeriUser->isAdminNegeri()
        ) {
            return [$contract->adminNegeriUser->email];
        }

        $negeri = $contract->premise?->negeri;

        if (! filled($negeri)) {
            return [];
        }

        return User::query()
            ->where('role', 'admin_negeri')
            ->where('negeri', $negeri)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();
    }
}
