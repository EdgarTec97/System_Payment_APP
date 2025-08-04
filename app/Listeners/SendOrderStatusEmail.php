<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Mail\OrderStatusEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Don't send email for draft status changes
        if ($event->newStatus === 'draft' || $event->previousStatus === 'draft') {
            return;
        }

        try {
            Mail::to($event->order->user->email)
                ->send(new OrderStatusEmail($event->order, $event->previousStatus, $event->newStatus));

            Log::info('Order status email sent successfully', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'user_email' => $event->order->user->email,
                'previous_status' => $event->previousStatus,
                'new_status' => $event->newStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order status email', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'user_email' => $event->order->user->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderStatusChanged $event, \Throwable $exception): void
    {
        Log::error('Order status email job failed', [
            'order_id' => $event->order->id,
            'order_number' => $event->order->order_number,
            'user_email' => $event->order->user->email,
            'error' => $exception->getMessage(),
        ]);
    }
}

