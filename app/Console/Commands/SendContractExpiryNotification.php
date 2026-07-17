<?php

namespace App\Console\Commands;

use App\Mail\ContractExpiryNotification;
use App\Models\RentalContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendContractExpiryNotification extends Command
{
    protected $signature = 'contracts:send-expiry-notification';

    protected $description = 'Hantar emel notifikasi untuk kontrak yang baki kurang 8 bulan';

    public function handle(): int
    {
        $contracts = RentalContract::query()
            ->with('premise')
            ->where('status_aktif', 'aktif')
            ->where('tarikh_tamat', '>=', Carbon::today())
            ->whereRaw('DATEDIFF(tarikh_tamat, CURDATE()) <= 240')
            ->orderBy('tarikh_tamat')
            ->get();

        if ($contracts->isEmpty()) {
            $this->info('Tiada kontrak yang perlu notifikasi.');

            return self::SUCCESS;
        }

        $admins = User::query()->whereIn('role', ['admin_hq', 'admin_negeri'])->get();

        foreach ($admins as $admin) {
            if ($admin->email) {
                Mail::to($admin->email)->send(new ContractExpiryNotification($contracts));
            }
        }

        $this->info('Notifikasi dihantar kepada '.$admins->count().' pentadbir untuk '.$contracts->count().' kontrak.');

        return self::SUCCESS;
    }
}
