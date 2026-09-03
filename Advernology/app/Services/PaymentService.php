<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;

class PaymentService
{
    /**
     * Create a pending payment record and return the Payment + redirect URL.
     */
    public function initiate(array $data, Product $product): Payment
    {
        $customer = Customer::firstOrCreate(
            ['email' => strtolower($data['email'])],
            [
                'name'   => $data['name'],
                'phone'  => $data['phone'] ?? null,
                'domain' => strtolower(substr(strrchr($data['email'], '@'), 1)),
                'notes'  => $data['notes'] ?? null,
            ]
        );

        return Payment::create([
            'customer_id'    => $customer->id,
            'product_id'     => $product->id,
            'amount'         => $product->price,
            'status'         => 'pending',
            'payment_method' => 'swipepay',
            'notes'          => $data['notes'] ?? null,
        ]);
    }

    /**
     * Build SwipePay redirect URL (placeholder — replace with real SDK call).
     */
    public function buildSwipePayUrl(Payment $payment): string
    {
        $params = http_build_query([
            'merchant_id'  => config('services.swipepay.merchant_id'),
            'amount'       => $payment->amount,
            'order_id'     => $payment->id,
            'description'  => $payment->product->name ?? 'Email Migration',
            'return_url'   => route('order.complete', $payment->id),
            'cancel_url'   => route('order.cancel', $payment->id),
        ]);

        $base = config('services.swipepay.env') === 'sandbox'
            ? 'https://sandbox.swipepay.com/pay'
            : 'https://pay.swipepay.com/pay';

        return "{$base}?{$params}";
    }

    public function handleCallback(Payment $payment, array $gatewayData): bool
    {
        $success = ($gatewayData['status'] ?? '') === 'approved';

        $payment->update([
            'status'           => $success ? 'paid' : 'failed',
            'transaction_id'   => $gatewayData['transaction_id'] ?? null,
            'payment_date'     => $success ? now() : null,
            'gateway_response' => $gatewayData,
        ]);

        return $success;
    }
}
