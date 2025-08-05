<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public UserVerification $verification;
    public string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, UserVerification $verification)
    {
        $this->user = $user;
        $this->verification = $verification;
        $this->verificationUrl = route('verification.verify', ['token' => $verification->token]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifica tu dirección de email - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verification',
            with: [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
                'expiresAt' => $this->verification->expires_at,
            ],
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
