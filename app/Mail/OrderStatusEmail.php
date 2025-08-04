<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $previousStatus;
    public string $newStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $previousStatus, string $newStatus)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusMessages = [
            'created' => 'Tu orden ha sido creada',
            'paid' => 'Tu orden ha sido pagada',
            'cancelled' => 'Tu orden ha sido cancelada',
            'delivered' => 'Tu orden ha sido entregada',
        ];

        $subject = $statusMessages[$this->newStatus] ?? 'Actualización de tu orden';
        $subject .= ' - Orden #' . $this->order->order_number;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status',
            with: [
                'order' => $this->order,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
                'statusLabel' => $this->getStatusLabel($this->newStatus),
                'previousStatusLabel' => $this->getStatusLabel($this->previousStatus),
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

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'draft' => 'Borrador',
            'created' => 'Creada',
            'paid' => 'Pagada',
            'cancelled' => 'Cancelada',
            'delivered' => 'Entregada',
        ];

        return $labels[$status] ?? ucfirst($status);
    }
}

