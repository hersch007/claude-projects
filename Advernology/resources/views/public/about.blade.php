@extends('layouts.public')

@section('title', 'About Us — Advernology Service')
@section('meta_description', 'Advernology Service helps former Lumos internet customers keep their email address working through a fast, secure migration.')

@section('content')

{{-- HERO --}}
<section class="hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="hero-badge">
                    <i class="bi bi-info-circle-fill"></i>
                    About Us
                </div>
                <h1>Built to keep Lumos customers<br><span>connected</span></h1>
                <p class="lead mx-auto">
                    Advernology Service exists for one reason: when Lumos internet service changes,
                    thousands of email addresses are at risk of disappearing. We built a fast, secure
                    way to keep them working.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- TRUST BAR --}}
<section class="trust-bar">
    <div class="container">
        <div class="row justify-content-center g-3">
            <div class="col-6 col-md-auto">
                <div class="trust-item"><i class="bi bi-patch-check-fill"></i>2,400+ Migrations</div>
            </div>
            <div class="col-6 col-md-auto">
                <div class="trust-item"><i class="bi bi-shield-fill-check"></i>Secure & Private</div>
            </div>
            <div class="col-6 col-md-auto">
                <div class="trust-item"><i class="bi bi-headset"></i>Real Support, Real People</div>
            </div>
        </div>
    </div>
</section>

{{-- STORY --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="section-label text-center d-block mb-2">Our Story</div>
                <h2 class="section-title text-center mb-4">Why we started Advernology Service</h2>
                <p class="text-muted mb-3" style="line-height:1.8">
                    When Lumos transitions customers off their internet service, the email addresses tied
                    to that account are usually the first thing people lose — and the last thing they think
                    to plan for. Years of receipts, family photos, account logins, and personal
                    correspondence are all attached to an address that can simply stop working.
                </p>
                <p class="text-muted mb-3" style="line-height:1.8">
                    We built Advernology Service to make the fix as painless as the problem is disruptive.
                    Check your eligibility, choose a plan, and our team handles the migration — usually
                    within 24 hours — so your address keeps working without you having to notify every
                    contact or update every account.
                </p>
                <p class="text-muted mb-0" style="line-height:1.8">
                    We're not affiliated with Lumos Networks. We're an independent service built
                    specifically for people affected by this transition.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- WHY US --}}
<section class="py-5 py-lg-6" style="background:var(--brand-surface)">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">What We Value</div>
            <h2 class="section-title mb-2">How we operate</h2>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-sm-6 col-lg-4">
                <div style="background:#fff;border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem;height:100%">
                    <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                        <i class="bi bi-lightning-charge-fill" style="color:var(--brand-primary);font-size:1.2rem"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Fast, hands-off migration</h6>
                    <p class="text-muted small mb-0">Our team does the migration work — you don't have to.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div style="background:#fff;border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem;height:100%">
                    <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                        <i class="bi bi-shield-fill-check" style="color:var(--brand-primary);font-size:1.2rem"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Privacy first</h6>
                    <p class="text-muted small mb-0">Your data is never sold, shared, or stored beyond what's needed.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div style="background:#fff;border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem;height:100%">
                    <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                        <i class="bi bi-headset" style="color:var(--brand-primary);font-size:1.2rem"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Real support</h6>
                    <p class="text-muted small mb-0">Reach a real person by email — not a chatbot.</p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
