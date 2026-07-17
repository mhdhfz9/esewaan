<?php

namespace App\Mail;

use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationSubmittedToAdminNegeriNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RentalContract $contract,
        public User $submittedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'E-Sewaan AADK: Permohonan Dihantar ke Admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-submitted-to-admin-negeri',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
