@extends('layouts.public')

@section('title', 'Order Cancelled')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-5">
                    <i class="bi bi-x-circle display-4 text-danger mb-3 d-block"></i>
                    <h3 class="fw-bold mb-2">Order Cancelled</h3>
                    <p class="text-muted mb-4">Your order #{{ $payment->id }} was cancelled. No payment was taken.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary me-2">Try Again</a>
                    <a href="mailto:support@advernologyservice.com" class="btn btn-outline-secondary">Get Help</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
