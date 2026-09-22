@extends('layouts.public')

@section('title', 'Our Technology — Advernology Service')
@section('meta_description', 'See how Advernology Service migrates your Lumos email address securely, without downtime, and without losing your existing history.')

@section('content')

{{-- HERO --}}
<section class="hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="hero-badge">
                    <i class="bi bi-gear-fill"></i>
                    Our Technology
                </div>
                <h1>A migration built to be<br><span>invisible</span></h1>
                <p class="lead mx-auto">
                    Your inbox, contacts, and address should keep working the same way they always have —
                    just on infrastructure that isn't going away.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- HOW IT WORKS (TECHNICAL) --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">Under the Hood</div>
            <h2 class="section-title mb-2">How the migration works</h2>
            <p class="section-subtitle">A quick look at what happens after you submit your email for eligibility.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-sm-6 col-lg-3 text-center position-relative">
                <div class="step-number"><i class="bi bi-search"></i></div>
                <h5 class="fw-bold mb-2">Domain Verification</h5>
                <p class="text-muted small">We check your email's domain against our eligible-domain records to confirm it's part of the Lumos transition.</p>
            </div>
            <div class="col-sm-6 col-lg-3 text-center position-relative">
                <div class="step-number"><i class="bi bi-box-seam"></i></div>
                <h5 class="fw-bold mb-2">Mailbox Provisioning</h5>
                <p class="text-muted small">Once you order a package, we provision a new mailbox on our hosting platform tied to your existing address.</p>
            </div>
            <div class="col-sm-6 col-lg-3 text-center position-relative">
                <div class="step-number"><i class="bi bi-arrow-repeat"></i></div>
                <h5 class="fw-bold mb-2">DNS & Routing Cutover</h5>
                <p class="text-muted small">We update the mail routing (MX) records for your domain so incoming mail flows to your new mailbox seamlessly.</p>
            </div>
            <div class="col-sm-6 col-lg-3 text-center">
                <div class="step-number"><i class="bi bi-check2-circle"></i></div>
                <h5 class="fw-bold mb-2">Verification & Handoff</h5>
                <p class="text-muted small">We confirm mail is flowing correctly and notify you by email once your migration is complete.</p>
            </div>
        </div>
    </div>
</section>

{{-- RELIABILITY / SECURITY --}}
<section class="py-5 py-lg-6" style="background:var(--brand-surface)">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="section-label">Reliability & Security</div>
                <h2 class="section-title mb-3">Built on infrastructure that stays up</h2>
                <p class="text-muted" style="line-height:1.8">
                    Once migrated, your email is hosted on stable infrastructure with no dependency on
                    Lumos's network — so it keeps working regardless of what happens to your former
                    internet service.
                </p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div style="background:#fff;border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-hdd-network-fill" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Independent hosting</h6>
                            <p class="text-muted small mb-0">Your mailbox no longer depends on Lumos's network at all.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="background:#fff;border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-lock-fill" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Encrypted in transit</h6>
                            <p class="text-muted small mb-0">Mail and payment data are transmitted over encrypted connections.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="background:#fff;border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-clock-history" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Minimal downtime</h6>
                            <p class="text-muted small mb-0">Mail routing cutovers are timed to keep your inbox receiving mail throughout.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="background:#fff;border:1px solid var(--brand-border);border-radius:14px;padding:1.5rem">
                            <div style="width:44px;height:44px;background:var(--brand-primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                                <i class="bi bi-person-check-fill" style="color:var(--brand-primary);font-size:1.2rem"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Human-reviewed</h6>
                            <p class="text-muted small mb-0">Every migration is verified by our team before we mark it complete.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
