@extends('layouts.public')

@section('title', 'Keep Your Lumos Email Address — Advernology Service')
@section('meta_description', 'Don\'t lose your Lumos email when service ends. Advernology migrates your email address in minutes. Plans from $6.99.')

@section('content')

{{-- HERO --}}
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 text-center text-lg-start">
                <div class="hero-badge">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    Important Notice for Lumos Customers
                </div>
                <h1>Keep Your Lumos Email<br><span>Before It's Gone</span></h1>
                <p class="lead">
                    Lumos internet service is changing — and thousands of email addresses are at risk.
                    We make it fast and affordable to keep your address working. Setup in minutes, migration handled by our team.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                    <a href="#check-eligibility" class="btn-hero-primary">
                        <i class="bi bi-search me-2"></i>Check My Email Address
                    </a>
                    <a href="#pricing" class="btn-hero-outline">View Packages</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:2rem;">
                    <div style="background:rgba(255,255,255,.08);border-radius:12px;padding:1.5rem;margin-bottom:1rem;">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:40px;height:40px;background:rgba(255,107,0,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-envelope-check" style="color:var(--brand-primary);font-size:1.1rem"></i>
                            </div>
                            <div>
                                <div style="font-size:.8rem;color:rgba(255,255,255,.5)">Migration Status</div>
                                <div style="font-weight:700;color:#fff">yourname@lumos.com</div>
                            </div>
                            <span style="margin-left:auto;background:#16a34a;color:#fff;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px">✓ Active</span>
                        </div>
                        <div style="height:4px;background:rgba(255,255,255,.1);border-radius:4px;overflow:hidden">
                            <div style="width:100%;height:100%;background:linear-gradient(90deg,var(--brand-primary),#ff8c00);border-radius:4px"></div>
                        </div>
                        <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:.5rem">Migration complete • 24 hours</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:1rem;text-align:center">
                                <div style="font-size:1.4rem;font-weight:800;color:#fff">2,400+</div>
                                <div style="font-size:.7rem;color:rgba(255,255,255,.5)">Emails Saved</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:1rem;text-align:center">
                                <div style="font-size:1.4rem;font-weight:800;color:#fff">24hr</div>
                                <div style="font-size:.7rem;color:rgba(255,255,255,.5)">Avg Setup</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="background:rgba(255,255,255,.06);border-radius:10px;padding:1rem;text-align:center">
                                <div style="font-size:1.4rem;font-weight:800;color:#fff">$6.99</div>
                                <div style="font-size:.7rem;color:rgba(255,255,255,.5)">Starting At</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- TRUST BAR --}}
<section class="trust-bar">
    <div class="container">
        <div class="row justify-content-center g-3">
            <div class="col-6 col-md-auto">
                <div class="trust-item"><i class="bi bi-shield-fill-check"></i>Secure & Private</div>
            </div>
            <div class="col-6 col-md-auto">
                <div class="trust-item"><i class="bi bi-lightning-charge-fill"></i>Setup in Minutes</div>
            </div>
            <div class="col-6 col-md-auto">
                <div class="trust-item"><i class="bi bi-headset"></i>Dedicated Support</div>
            </div>
            <div class="col-6 col-md-auto">
                <div class="trust-item"><i class="bi bi-patch-check-fill"></i>2,400+ Migrations</div>
            </div>
            <div class="col-6 col-md-auto">
                <div class="trust-item"><i class="bi bi-wallet2"></i>Plans from $6.99</div>
            </div>
        </div>
    </div>
</section>

{{-- ELIGIBILITY CHECKER --}}
<section class="py-5 py-lg-6" id="check-eligibility" style="background:var(--brand-surface)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center mb-4">
                <div class="section-label">Step 1</div>
                <h2 class="section-title mb-2">Check Your Email Eligibility</h2>
                <p class="section-subtitle">Enter your Lumos email address below to instantly see if you qualify and which packages are available.</p>
            </div>
            <div class="col-lg-6">
                <div class="eligibility-card">
                    <form action="{{ route('eligibility.check') }}" method="POST">
                        @csrf
                        <label class="form-label fw-semibold mb-2" style="font-size:.9rem">Your Lumos Email Address</label>
                        <div class="input-group input-group-lg mb-3">
                            <span class="input-group-text">
                                <i class="bi bi-envelope" style="color:var(--brand-primary)"></i>
                            </span>
                            <input
                                type="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="yourname@lumos.com"
                                value="{{ old('email') }}"
                                required
                            >
                            <button class="btn-check-email btn" type="submit">
                                Check Now →
                            </button>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex align-items-center justify-content-center gap-4" style="font-size:.78rem;color:var(--brand-muted)">
                            <span><i class="bi bi-lock me-1"></i>We never share your email</span>
                            <span><i class="bi bi-lightning me-1"></i>Instant results</span>
                            <span><i class="bi bi-x-circle me-1"></i>No obligation</span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<section class="py-5 py-lg-6" id="how-it-works">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">The Process</div>
            <h2 class="section-title mb-2">How It Works</h2>
            <p class="section-subtitle">From sign-up to fully migrated email in as little as 24 hours.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-sm-6 col-lg-3 text-center position-relative">
                <div class="step-number">1</div>
                <h5 class="fw-bold mb-2">Check Eligibility</h5>
                <p class="text-muted small">Enter your Lumos email above to confirm you qualify for migration.</p>
            </div>
            <div class="col-sm-6 col-lg-3 text-center">
                <div class="step-number">2</div>
                <h5 class="fw-bold mb-2">Choose a Package</h5>
                <p class="text-muted small">Select how many email addresses you need — individual or family plans available.</p>
            </div>
            <div class="col-sm-6 col-lg-3 text-center">
                <div class="step-number">3</div>
                <h5 class="fw-bold mb-2">Secure Payment</h5>
                <p class="text-muted small">Complete your one-time payment via SwipePay. Takes under 2 minutes.</p>
            </div>
            <div class="col-sm-6 col-lg-3 text-center">
                <div class="step-number">4</div>
                <h5 class="fw-bold mb-2">We Handle the Rest</h5>
                <p class="text-muted small">Our team migrates your email and notifies you when it's ready — usually within 24 hours.</p>
            </div>
        </div>
    </div>
</section>

{{-- PRICING --}}
<section class="py-5 py-lg-6" id="pricing" style="background:var(--brand-surface)">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">Pricing</div>
            <h2 class="section-title mb-2">Simple, Transparent Pricing</h2>
            <p class="section-subtitle">One-time payment. No subscriptions. No hidden fees. Keep your email address forever.</p>
        </div>

        <div class="row g-4 justify-content-center">
            @foreach($products as $index => $product)
            <div class="col-md-6 col-lg-3">
                <div class="pricing-card {{ $index === 2 ? 'featured' : '' }}">
                    @if($index === 2)
                        <div><span class="pricing-badge">⭐ Most Popular</span></div>
                    @endif
                    <div class="pricing-name">{{ $product->name }}</div>
                    <div class="pricing-price"><sup>$</sup>{{ number_format($product->price, 2) }}</div>
                    <div class="pricing-period mb-3">one-time payment</div>
                    <p class="text-muted small mb-3" style="font-size:.825rem">{{ $product->description }}</p>
                    <ul class="list-unstyled pricing-features mb-4">
                        <li><i class="bi bi-check-circle-fill"></i>{{ $product->emails_count }} email {{ $product->emails_count === 1 ? 'address' : 'addresses' }}</li>
                        <li><i class="bi bi-check-circle-fill"></i>Full migration support</li>
                        <li><i class="bi bi-check-circle-fill"></i>Setup within 24 hours</li>
                        <li><i class="bi bi-check-circle-fill"></i>Dedicated customer support</li>
                        @if($product->price >= 29.99)
                        <li><i class="bi bi-check-circle-fill"></i>Priority migration queue</li>
                        @endif
                    </ul>
                    @if($index === 2)
                        <a href="{{ route('order.create', $product) }}" class="btn-pricing">Get Started →</a>
                    @else
                        <a href="{{ route('order.create', $product) }}" class="btn-pricing-outline">Get Started →</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <p class="text-center mt-4" style="font-size:.825rem;color:var(--brand-muted)">
            <i class="bi bi-shield-check me-1" style="color:var(--brand-primary)"></i>
            All payments are secured and encrypted. Not sure which plan? <a href="#check-eligibility" style="color:var(--brand-primary);font-weight:600">Check your eligibility first →</a>
        </p>
    </div>
</section>

{{-- WHY US --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="section-label">Why Choose Us</div>
                <h2 class="section-title mb-3">The easiest way to keep your Lumos email</h2>
                <p class="text-muted mb-4" style="line-height:1.8">We've migrated thousands of Lumos customers and built our process specifically for this transition — so you don't have to figure it out yourself.</p>
                <a href="#check-eligibility" class="btn-hero-primary d-inline-block" style="text-decoration:none">
                    <i class="bi bi-arrow-right-circle me-2"></i>Get Started Today
                </a>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div style="background:var(--brand-surface);border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-lightning-charge-fill" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Fast Migration</h6>
                            <p class="text-muted small mb-0">Most migrations completed within 24 hours of payment.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="background:var(--brand-surface);border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-people-fill" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Family Plans</h6>
                            <p class="text-muted small mb-0">Migrate multiple email addresses under one payment.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="background:var(--brand-surface);border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-shield-fill-check" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Secure & Private</h6>
                            <p class="text-muted small mb-0">Your data is never sold, shared, or stored beyond what's needed.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="background:var(--brand-surface);border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-headset" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Real Support</h6>
                            <p class="text-muted small mb-0">Reach a real person by email — not a chatbot.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="py-5 py-lg-6" id="faq" style="background:var(--brand-surface)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="text-center mb-5">
                    <div class="section-label">FAQ</div>
                    <h2 class="section-title">Common Questions</h2>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        What happens to my Lumos email address?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        When Lumos transitions customers off their internet service, the associated email addresses are at risk of being deactivated. Our service migrates your address to a reliable hosting platform so it continues working as if nothing changed.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Do I need to change my email address?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        In most cases, no. Our goal is to keep your existing Lumos email address working so you don't have to notify contacts or update accounts. We'll confirm the specifics based on your domain during migration.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        How long does migration take?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Most migrations are completed within 24 hours of payment. Priority plan customers are moved to the front of the queue. You'll receive an email confirmation when your migration is complete.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Is this a one-time payment or a subscription?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        It's a one-time payment to cover the migration process. There are no recurring charges or hidden fees.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        What if my email isn't found in the system?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        If your email doesn't appear eligible, contact us at <a href="mailto:support@advernologyservice.com" style="color:var(--brand-primary)">support@advernologyservice.com</a>. We may be able to add you manually or help determine the best path forward.
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted small">Still have questions? <a href="mailto:support@advernologyservice.com" style="color:var(--brand-primary);font-weight:600">Email our support team →</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
