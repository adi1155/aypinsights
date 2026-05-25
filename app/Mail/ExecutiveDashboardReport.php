<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExecutiveDashboardReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reportType,
        public array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AYP Executive Report: '.ucwords(str_replace('_', ' ', $this->reportType)),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.executive-report',
            with: ['reportType' => $this->reportType, 'data' => $this->data],
        );
    }
}
