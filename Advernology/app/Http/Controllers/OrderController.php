<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Payment;
use App\Models\Product;
use App\Services\PaymentService;

class OrderController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function create(Product $product)
    {
        abort_unless($product->active, 404);
        return view('public.order', compact('product'));
    }

    public function store(OrderRequest $request, Product $product)
    {
        abort_unless($product->active, 404);

        $payment = $this->paymentService->initiate($request->validated(), $product);

        if ($product->payment_link) {
            return redirect($product->payment_link);
        }

        return redirect()->route('order.unavailable', $payment);
    }

    public function unavailable(Payment $payment)
    {
        return view('public.payment_unavailable', compact('payment'));
    }

    public function complete(Payment $payment)
    {
        // Callback after a successful SwipeSimple payment
        $this->paymentService->handleCallback($payment, request()->all());

        return view('public.complete', compact('payment'));
    }

    public function cancel(Payment $payment)
    {
        $payment->update(['status' => 'failed']);
        return view('public.cancel', compact('payment'));
    }
}
