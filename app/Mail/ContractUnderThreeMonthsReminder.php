<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ContractUnderThreeMonthsReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\RentalContract>  $contracts
     */
    public function __construct(
        public Collection $contracts
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'E-Sewaan AADK: Peringatan Mingguan Kontrak Bawah 3 Bulan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-under-three-months-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
