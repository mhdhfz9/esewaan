<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractExpiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\RentalContract>  $contracts
     */
    public function __construct(
        public $contracts
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'E-Sewaan AADK: Peringatan Kontrak Kurang 8 Bulan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-expiry',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
