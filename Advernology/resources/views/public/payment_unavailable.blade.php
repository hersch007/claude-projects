@extends('layouts.public')

@section('title', 'Payment Unavailable')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-5">
                    <i class="bi bi-exclamation-triangle display-4 text-warning mb-3 d-block"></i>
                    <h3 class="fw-bold mb-2">This Package Isn't Ready for Online Payment Yet</h3>
                    <p class="text-muted mb-4">
                        Your order #{{ $payment->id }} was saved, but online payment isn't set up for this
                        package yet. No payment was taken. Please contact us and we'll get you sorted out.
                    </p>
                    <a href="mailto:support@advernologyservice.com" class="btn btn-primary me-2">Contact Support</a>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
