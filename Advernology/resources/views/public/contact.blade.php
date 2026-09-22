@extends('layouts.public')

@section('title', 'Contact Us — Advernology Service')
@section('meta_description', 'Get in touch with the Advernology Service support team about your Lumos email migration.')

@section('content')

{{-- HERO --}}
<section class="hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="hero-badge">
                    <i class="bi bi-envelope-fill"></i>
                    Contact Us
                </div>
                <h1>We're here to<br><span>help</span></h1>
                <p class="lead mx-auto">
                    Questions about eligibility, pricing, or an in-progress migration? Reach a real person —
                    not a chatbot.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- CONTACT DETAILS --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="eligibility-card h-100">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-envelope" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Email Support</h5>
                            <p class="text-muted mb-3" style="line-height:1.7">
                                The fastest way to reach us. Include your order number or the email address
                                you're migrating so we can look up your account quickly.
                            </p>
                            <a href="mailto:support@advernologyservice.com" style="color:var(--brand-primary);font-weight:600;text-decoration:none">
                                support@advernologyservice.com <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="eligibility-card h-100">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-question-circle" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Have a Quick Question?</h5>
                            <p class="text-muted mb-3" style="line-height:1.7">
                                Check our knowledge base first — most eligibility, pricing, and migration
                                questions are answered there.
                            </p>
                            <a href="{{ route('knowledge') }}" style="color:var(--brand-primary);font-weight:600;text-decoration:none">
                                Visit the Knowledge Base <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <p class="text-muted small mb-0">
                        Not sure if your email is eligible yet?
                        <a href="{{ route('home') }}#check-eligibility" style="color:var(--brand-primary);font-weight:600">Check your eligibility first →</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
