<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstname;
    public $lastname;
    public $barcode;
    public $expiryDate;
    public $source;
    public $homeBranchName;
    public $homeBranchLink;
    /**
     * Create a new message instance.
     */
    public function __construct($firstname, $lastname, $barcode, $source, $expiryDate, $homeBranchName = null, $homeBranchLink = null)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->barcode = $barcode;
        $this->source = $source;
        $this->expiryDate = $expiryDate;
        $this->homeBranchName = $source === 'CIC' ? $homeBranchName : null;
        $this->homeBranchLink = $source === 'CIC' ? $homeBranchLink : null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // if $source is CIC, use 'Discover EPL with Your New Library Card' as the subject
        // otherwise use 'Your New Library Card' as the subject
        if ($this->source === 'CIC') {
            $subject = 'Discover EPL with Your New Library Card';
        } else {
            $subject = 'Your New Library Card';
        }
        return new Envelope(
            subject: $subject,
            from: 'noreply@epl.ca',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
