<?php

namespace App\Services;

use App\Mail\ApplicationApprovedByHqNotification;
use App\Mail\ApplicationSubmittedToAdminNegeriNotification;
use App\Mail\ApplicationSubmittedToHqNotification;
use App\Mail\ApplicationWorkflowStageNotification;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ApplicationWorkflowMailer
{
    public function notifySubmittedToHq(RentalContract $contract, User $submittedBy): void
    {
        $contract->loadMissing(['premise', 'adminNegeriUser', 'submittedBy']);

        foreach ($this->adminHqNotificationRecipients() as $email) {
            Mail::to($email)->send(new ApplicationSubmittedToHqNotification($contract, $submittedBy));
        }

        foreach ($this->adminNegeriNotificationRecipients($contract) as $email) {
            Mail::to($email)->send(new ApplicationSubmittedToAdminNegeriNotification($contract, $submittedBy));
        }
    }

    public function notifyApprovedByHq(RentalContract $contract, User $approvedBy): void
    {
        $contract->loadMissing(['premise', 'adminNegeriUser', 'submittedBy']);

        foreach ($this->adminNegeriNotificationRecipients($contract) as $email) {
            Mail::to($email)->send(new ApplicationApprovedByHqNotification($contract, $approvedBy));
        }

        foreach ($this->adminHqNotificationRecipients() as $email) {
            Mail::to($email)->send(new ApplicationApprovedByHqNotification($contract, $approvedBy));
        }
    }

    public function notifyDraftUploaded(RentalContract $contract, User $actedBy): void
    {
        $round = (int) $contract->semakan_count;

        $this->notifyStage($contract, $actedBy, [
            'subject' => 'E-Sewaan AADK: Draf Perjanjian Dihantar untuk Semakan PUU',
            'heading' => 'Dalam Tindakan PUU',
            'intro' => 'Draf perjanjian telah dimuat naik dan dihantar untuk Semakan '.$round.'.',
            'next_status' => 'Peringkat 4 — Dalam tindakan PUU (Semakan '.$round.')',
            'cta_url' => route('status-permohonan.review', $contract),
            'cta_label' => 'Semak Draf Perjanjian',
        ]);
    }

    public function notifyPuuRejected(RentalContract $contract, User $actedBy): void
    {
        $this->notifyStage($contract, $actedBy, [
            'subject' => 'E-Sewaan AADK: Semakan PUU Memerlukan Pindaan',
            'heading' => 'Pindaan Berdasarkan PUU',
            'intro' => 'Semakan PUU dibatalkan. Negeri perlu pinda dan muat naik draf perjanjian semula.',
            'next_status' => 'Peringkat 5 — Pindaan Berdasarkan PUU',
            'cta_url' => route('status-permohonan.review', $contract),
            'cta_label' => 'Lihat Permohonan',
        ]);
    }

    public function notifyDraftApproved(RentalContract $contract, User $actedBy): void
    {
        $this->notifyStage($contract, $actedBy, [
            'subject' => 'E-Sewaan AADK: Draf Perjanjian Diluluskan',
            'heading' => 'Draf Lulus',
            'intro' => 'Draf perjanjian telah diluluskan. Negeri boleh menyediakan dokumen akhir dan mengembalikannya kepada Ibu Pejabat.',
            'next_status' => 'Peringkat 6 — Draf Lulus',
            'cta_url' => route('status-permohonan.review', $contract),
            'cta_label' => 'Lihat Permohonan',
        ]);
    }

    public function notifyReturnedToHq(RentalContract $contract, User $actedBy): void
    {
        $this->notifyStage($contract, $actedBy, [
            'subject' => 'E-Sewaan AADK: Dokumen Dikembalikan untuk Pengesahan & Tandatangan',
            'heading' => 'Pengesahan & Tandatangan',
            'intro' => 'Negeri telah mengembalikan draf akhir kepada Cawangan Pembangunan AADK untuk pengesahan dan tandatangan.',
            'next_status' => 'Peringkat 7 — Pengesahan & Tandatangan',
            'cta_url' => route('status-permohonan.review', $contract),
            'cta_label' => 'Semak Dokumen',
        ]);
    }

    public function notifySentForStampDuty(RentalContract $contract, User $actedBy): void
    {
        $this->notifyStage($contract, $actedBy, [
            'subject' => 'E-Sewaan AADK: Perjanjian Dihantar untuk Mati Setem',
            'heading' => 'Mati Setem',
            'intro' => 'Ibu Pejabat telah mengesahkan tandatangan. Negeri perlu lengkapkan mati setem.',
            'next_status' => 'Peringkat 8 — Mati Setem',
            'cta_url' => route('status-permohonan.review', $contract),
            'cta_label' => 'Lengkapkan Mati Setem',
        ]);
    }

    public function notifyCompleted(RentalContract $contract, User $actedBy): void
    {
        $this->notifyStage($contract, $actedBy, [
            'subject' => 'E-Sewaan AADK: Permohonan Selesai — Dalam Senarai Kontrak Sewaan',
            'heading' => 'Selesai',
            'intro' => 'Mati setem telah selesai. Permohonan dimasukkan ke dalam Senarai Kontrak Sewaan.',
            'next_status' => 'Peringkat 9 — Selesai',
            'cta_url' => route('kontrak-sewaan.show', $contract),
            'cta_label' => 'Lihat Kontrak Sewaan',
        ]);
    }

    /**
     * @param  array{subject: string, heading: string, intro: string, next_status: string, cta_url: string, cta_label: string}  $payload
     */
    private function notifyStage(RentalContract $contract, User $actedBy, array $payload): void
    {
        $contract->loadMissing(['premise', 'adminNegeriUser', 'submittedBy']);

        foreach ($this->workflowRecipients($contract) as $email) {
            Mail::to($email)->send(new ApplicationWorkflowStageNotification($contract, $actedBy, $payload));
        }
    }

    /**
     * @return list<string>
     */
    private function workflowRecipients(RentalContract $contract): array
    {
        return array_values(array_unique(array_merge(
            $this->adminHqNotificationRecipients(),
            $this->adminNegeriNotificationRecipients($contract),
        )));
    }

    /**
     * @return list<string>
     */
    private function adminHqNotificationRecipients(): array
    {
        $testRecipient = config('mail.test_recipient');

        if (filled($testRecipient)) {
            return [$testRecipient];
        }

        return User::query()
            ->where('role', 'admin_hq')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();
    }

    /**
     * @return list<string>
     */
    private function adminNegeriNotificationRecipients(RentalContract $contract): array
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
            ->all();
    }
}
