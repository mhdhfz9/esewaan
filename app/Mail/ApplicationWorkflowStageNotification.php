<?php

namespace App\Mail;

use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationWorkflowStageNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{subject: string, heading: string, intro: string, next_status: string, cta_url: string, cta_label: string}  $payload
     */
    public function __construct(
        public RentalContract $contract,
        public User $actedBy,
        public array $payload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->payload['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-workflow-stage',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
