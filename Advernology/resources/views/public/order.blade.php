@extends('layouts.public')

@section('title', 'Order — ' . $product->name)

@section('content')
<section class="py-5" style="background:var(--brand-surface);min-height:70vh">
    <div class="container">
        <div class="row justify-content-center g-4">

            {{-- Order Form --}}
            <div class="col-lg-6">
                <div style="background:#fff;border:1.5px solid var(--brand-border);border-radius:16px;padding:2rem;box-shadow:0 4px 24px rgba(0,0,0,.06)">
                    <h4 class="fw-bold mb-1">Your Information</h4>
                    <p class="text-muted small mb-4">We'll use these details to set up your migrated email account.</p>

                    <form action="{{ route('order.store', $product) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:.875rem">Lumos Email Address <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                name="email"
                                class="form-control form-control-lg @error('email') is-invalid @enderror"
                                value="{{ old('email', request('email')) }}"
                                placeholder="yourname@lumos.com"
                                required
                                style="border-color:var(--brand-border)"
                            >
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:.875rem">Full Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="name"
                                class="form-control form-control-lg @error('name') is-invalid @enderror"
                                value="{{ old('name') }}"
                                placeholder="Jane Smith"
                                required
                                style="border-color:var(--brand-border)"
                            >
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:.875rem">Phone Number</label>
                            <input
                                type="tel"
                                name="phone"
                                class="form-control form-control-lg @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}"
                                placeholder="(555) 555-5555"
                                style="border-color:var(--brand-border)"
                            >
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="font-size:.875rem">Additional Notes</label>
                            <textarea
                                name="notes"
                                class="form-control @error('notes') is-invalid @enderror"
                                rows="3"
                                placeholder="List all email addresses if ordering a multi-email package..."
                                style="border-color:var(--brand-border)"
                            >{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" style="width:100%;background:var(--brand-primary);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:1rem;padding:1rem;transition:background .15s">
                            <i class="bi bi-lock me-2"></i>Continue to Payment — ${{ number_format($product->price, 2) }}
                        </button>

                        <p class="text-center mt-3" style="font-size:.75rem;color:var(--brand-muted)">
                            <i class="bi bi-shield-check me-1" style="color:var(--brand-primary)"></i>
                            Secured by SwipePay. Your info is never stored on our servers.
                        </p>
                    </form>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="col-lg-4">
                <div style="background:var(--brand-navy);color:#fff;border-radius:16px;padding:1.75rem;margin-bottom:1rem">
                    <div style="font-size:.75rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:1rem">Order Summary</div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div style="font-weight:700;font-size:1rem">{{ $product->name }}</div>
                            <div style="font-size:.8rem;color:rgba(255,255,255,.5);margin-top:2px">{{ $product->description }}</div>
                        </div>
                        <div style="font-size:1.75rem;font-weight:800;flex-shrink:0;margin-left:1rem">${{ number_format($product->price, 2) }}</div>
                    </div>
                    <div style="border-top:1px solid rgba(255,255,255,.1);padding-top:1rem">
                        <div class="d-flex justify-content-between" style="font-size:.875rem;color:rgba(255,255,255,.6);margin-bottom:.4rem">
                            <span>Email addresses</span>
                            <span>{{ $product->emails_count }}</span>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:.875rem;color:rgba(255,255,255,.6);margin-bottom:.4rem">
                            <span>Payment type</span>
                            <span>One-time</span>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:.875rem;color:rgba(255,255,255,.6)">
                            <span>Setup time</span>
                            <span>Within 24 hours</span>
                        </div>
                    </div>
                    <div style="border-top:1px solid rgba(255,255,255,.1);margin-top:1rem;padding-top:1rem;display:flex;justify-content:space-between;font-weight:700">
                        <span>Total</span>
                        <span style="color:var(--brand-primary)">${{ number_format($product->price, 2) }}</span>
                    </div>
                </div>

                <div style="background:#fff;border:1.5px solid var(--brand-border);border-radius:14px;padding:1.25rem">
                    <h6 class="fw-bold mb-3" style="font-size:.875rem">What's Included</h6>
                    <ul class="list-unstyled mb-0 pricing-features">
                        <li><i class="bi bi-check-circle-fill"></i>{{ $product->emails_count }} email {{ $product->emails_count === 1 ? 'address' : 'addresses' }} migrated</li>
                        <li><i class="bi bi-check-circle-fill"></i>Full migration handled by our team</li>
                        <li><i class="bi bi-check-circle-fill"></i>Setup within 24 hours</li>
                        <li><i class="bi bi-check-circle-fill"></i>Email confirmation when complete</li>
                        <li><i class="bi bi-check-circle-fill"></i>Dedicated support included</li>
                    </ul>
                </div>

                <div class="mt-3 text-center">
                    <a href="{{ route('home') }}#pricing" style="font-size:.825rem;color:var(--brand-muted);text-decoration:none">
                        <i class="bi bi-arrow-left me-1"></i>Choose a different plan
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
