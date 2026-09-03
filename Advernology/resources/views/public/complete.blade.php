@extends('layouts.public')

@section('title', 'Order Complete — Thank You!')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-5">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mx-auto mb-4" style="width:80px;height:80px;font-size:2.5rem">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Thank You!</h2>
                    <p class="text-muted mb-4">
                        Your order <strong>#{{ $payment->id }}</strong> has been received.
                        @if($payment->status === 'paid')
                            Payment confirmed!
                        @else
                            We'll confirm your payment shortly.
                        @endif
                    </p>
                    <div class="bg-light rounded-3 p-3 mb-4 text-start">
                        <p class="small mb-1"><strong>What happens next:</strong></p>
                        <ul class="small mb-0">
                            <li>You'll receive a confirmation email</li>
                            <li>Our team will begin your email migration</li>
                            <li>Setup is typically complete within 24 hours</li>
                        </ul>
                    </div>
                    <a href="{{ route('home') }}" class="btn btn-primary">Return Home</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
