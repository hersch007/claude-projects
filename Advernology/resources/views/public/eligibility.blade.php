@extends('layouts.public')

@section('title', $eligible ? 'Great News — You\'re Eligible' : 'Email Not Found')

@section('content')
<section class="py-5" style="background:var(--brand-surface);min-height:70vh">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                @if($eligible)
                    <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:14px;padding:1.5rem;display:flex;align-items:flex-start;gap:1rem;margin-bottom:2rem">
                        <div style="width:44px;height:44px;background:#16a34a;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-check-lg" style="color:#fff;font-size:1.3rem"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;color:#15803d;font-size:1rem">You're eligible for migration!</div>
                            <div style="color:#166534;font-size:.875rem;margin-top:.2rem"><strong>{{ $email }}</strong> is in our system. Select a package below to get started.</div>
                        </div>
                    </div>

                    <div class="text-center mb-4">
                        <div class="section-label">Available Plans</div>
                        <h2 class="section-title">Choose Your Package</h2>
                        <p class="section-subtitle">All plans include full migration support and 24-hour setup.</p>
                    </div>

                    <div class="row g-3">
                        @foreach($products as $index => $product)
                        <div class="col-sm-6">
                            <div class="pricing-card {{ $index === 2 ? 'featured' : '' }}">
                                @if($index === 2)
                                    <div><span class="pricing-badge">⭐ Most Popular</span></div>
                                @endif
                                <div class="pricing-name">{{ $product->name }}</div>
                                <div class="pricing-price"><sup>$</sup>{{ number_format($product->price, 2) }}</div>
                                <div class="pricing-period mb-3">one-time payment</div>
                                <p style="font-size:.825rem;color:var(--brand-muted)" class="mb-3">{{ $product->description }}</p>
                                <ul class="list-unstyled pricing-features mb-4">
                                    <li><i class="bi bi-check-circle-fill"></i>{{ $product->emails_count }} email {{ $product->emails_count === 1 ? 'address' : 'addresses' }}</li>
                                    <li><i class="bi bi-check-circle-fill"></i>Full migration support</li>
                                    <li><i class="bi bi-check-circle-fill"></i>Setup within 24 hours</li>
                                </ul>
                                @if($index === 2)
                                    <a href="{{ route('order.create', $product) }}?email={{ urlencode($email) }}" class="btn-pricing">Order Now →</a>
                                @else
                                    <a href="{{ route('order.create', $product) }}?email={{ urlencode($email) }}" class="btn-pricing-outline">Order Now →</a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <p class="text-center mt-4" style="font-size:.8rem;color:var(--brand-muted)">
                        <i class="bi bi-lock me-1" style="color:var(--brand-primary)"></i>
                        Secure payment via SwipePay. Your information is never stored on our servers.
                    </p>

                @else
                    <div class="text-center py-5">
                        <div style="width:80px;height:80px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
                            <i class="bi bi-envelope-x" style="font-size:2rem;color:#dc2626"></i>
                        </div>
                        <h3 class="fw-bold mb-2">Email Not Found in Our System</h3>
                        <p class="text-muted mb-4">
                            We couldn't find <strong>{{ $email }}</strong> in our list of eligible Lumos customers.
                        </p>
                        <div style="background:#fff;border:1.5px solid var(--brand-border);border-radius:14px;padding:1.5rem;text-align:left;margin-bottom:2rem">
                            <h6 class="fw-bold mb-3">What to do next:</h6>
                            <ul class="mb-0" style="font-size:.9rem;color:var(--brand-muted);line-height:2">
                                <li>Double-check you entered the correct email address</li>
                                <li>Try a different Lumos email address you may have</li>
                                <li>Contact us — we may be able to add you manually</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                            <a href="{{ route('home') }}#check-eligibility" class="btn-hero-primary" style="text-decoration:none;display:inline-block">
                                <i class="bi bi-arrow-left me-2"></i>Try Another Email
                            </a>
                            <a href="mailto:support@advernologyservice.com" style="background:transparent;color:var(--brand-navy);border:1.5px solid var(--brand-border);border-radius:8px;font-weight:600;font-size:.9rem;padding:.75rem 1.5rem;text-decoration:none;display:inline-block;transition:all .15s">
                                <i class="bi bi-envelope me-2"></i>Contact Support
                            </a>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</section>
@endsection
