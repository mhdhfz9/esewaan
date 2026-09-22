<?php

namespace App\Console\Commands;

use App\Services\ContractUnderThreeMonthsReminderMailer;
use Illuminate\Console\Command;

class SendContractUnderThreeMonthsReminder extends Command
{
    protected $signature = 'contracts:send-under-three-months-reminder';

    protected $description = 'Hantar peringatan mingguan kepada admin negeri untuk kontrak aktif bawah 3 bulan yang belum mula tindakan';

    public function handle(ContractUnderThreeMonthsReminderMailer $mailer): int
    {
        $contracts = $mailer->contractsNeedingReminder();

        if ($contracts->isEmpty()) {
            $this->info('Tiada kontrak bawah 3 bulan yang perlu peringatan.');

            return self::SUCCESS;
        }

        $sent = $mailer->send();

        $this->info(sprintf(
            'Peringatan dihantar kepada %d penerima negeri untuk %d kontrak bawah 3 bulan.',
            $sent,
            $contracts->count()
        ));

        return self::SUCCESS;
    }
}
