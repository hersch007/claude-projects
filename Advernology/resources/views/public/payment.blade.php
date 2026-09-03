@extends('layouts.public')

@section('title', 'Complete Payment')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 text-center">

                <div class="card border-0 shadow-sm rounded-4 p-5">
                    <i class="bi bi-credit-card display-4 text-primary mb-3 d-block"></i>
                    <h3 class="fw-bold mb-2">Complete Your Payment</h3>
                    <p class="text-muted mb-4">You're ordering: <strong>{{ $product->name }}</strong></p>

                    <div class="bg-light rounded-3 p-3 mb-4 text-start">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Package</span>
                            <span class="fw-semibold small">{{ $product->name }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Order #</span>
                            <span class="fw-semibold small">{{ $payment->id }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold text-primary">${{ number_format($payment->amount, 2) }}</span>
                        </div>
                    </div>

                    {{-- SwipePay redirect button --}}
                    <a href="{{ $payUrl }}" class="btn btn-success btn-lg d-grid fw-semibold mb-3">
                        <i class="bi bi-lock me-2"></i>Pay ${{ number_format($payment->amount, 2) }} Securely
                    </a>

                    {{-- Manual Pay Option --}}
                    <p class="text-muted small">
                        Prefer to pay by phone? Call us and reference Order #{{ $payment->id }}.
                    </p>
                    <a href="{{ route('order.cancel', $payment) }}" class="btn btn-link btn-sm text-muted">Cancel this order</a>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
