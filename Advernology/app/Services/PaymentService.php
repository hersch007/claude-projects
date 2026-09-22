<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;

class PaymentService
{
    /**
     * Create a pending payment record for a product order.
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
            'payment_method' => 'swipesimple',
            'notes'          => $data['notes'] ?? null,
        ]);
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
