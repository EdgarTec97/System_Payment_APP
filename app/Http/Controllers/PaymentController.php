<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->middleware(['auth', 'verified'])->except(['webhook']);
    }

    /**
     * Create payment intent for order.
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Verify order belongs to current user
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], 404);
        }

        // Verify order is in correct status
        if ($order->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden ya no puede ser procesada.',
            ], 400);
        }

        // Validate stock for all items with SELECT FOR UPDATE
        DB::beginTransaction();

        try {
            $stockErrors = [];

            foreach ($order->items as $item) {
                // Lock the product row to prevent race conditions
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product || !$product->isAvailable()) {
                    $stockErrors[] = "El producto '{$item->product_title}' ya no está disponible.";
                    continue;
                }

                if ($product->stock < $item->quantity) {
                    $stockErrors[] = "No hay suficiente stock para '{$item->product_title}'. Stock disponible: {$product->stock}";
                }
            }

            if (!empty($stockErrors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Problemas de stock detectados.',
                    'errors' => $stockErrors,
                ], 400);
            }

            // Create or update Stripe PaymentIntent
            $paymentIntentData = [
                'amount' => intval($order->total * 100), // Convert to cents
                'currency' => strtolower($order->currency),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ];

            if ($order->stripe_payment_intent_id) {
                // Update existing PaymentIntent
                $paymentIntent = PaymentIntent::update(
                    $order->stripe_payment_intent_id,
                    $paymentIntentData
                );
            } else {
                // Create new PaymentIntent
                $paymentIntent = PaymentIntent::create($paymentIntentData);

                $order->update([
                    'stripe_payment_intent_id' => $paymentIntent->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating payment intent', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Confirm payment and update order.
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada.',
                ], 404);
            }

            if ($paymentIntent->status === 'succeeded') {
                DB::beginTransaction();

                try {
                    // Reserve stock for all items
                    foreach ($order->items as $item) {
                        $product = Product::where('id', $item->product_id)
                            ->lockForUpdate()
                            ->first();

                        if ($product && $product->stock >= $item->quantity) {
                            $product->decreaseStock($item->quantity);
                        }
                    }

                    // Update order status
                    $order->updateStatus('created');
                    $order->update([
                        'payment_method' => 'stripe',
                        'payment_status' => 'paid',
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Pago procesado exitosamente.',
                        'order_number' => $order->order_number,
                        'redirect_url' => route('orders.show', $order),
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'El pago no fue completado.',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error confirming payment', [
                'payment_intent_id' => $request->payment_intent_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar el pago.',
            ], 500);
        }
    }

    /**
     * Handle Stripe webhooks.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid payload in Stripe webhook', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid signature in Stripe webhook', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event['data']['object']);
                break;

            case 'payment_intent.canceled':
                $this->handlePaymentIntentCanceled($event['data']['object']);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $event['type']]);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle successful payment intent.
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($order && $order->status === 'created') {
            DB::beginTransaction();

            try {
                $order->updateStatus('paid');

                Log::info('Payment confirmed via webhook', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntent['id'],
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error processing successful payment webhook', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle failed payment intent.
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($order) {
            Log::warning('Payment failed', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent['id'],
                'failure_reason' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown',
            ]);

            // Optionally, you could update order status or send notification
        }
    }

    /**
     * Handle canceled payment intent.
     */
    private function handlePaymentIntentCanceled($paymentIntent)
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($order) {
            Log::info('Payment canceled', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent['id'],
            ]);

            // Optionally, you could update order status
        }
    }
}
