@extends('layouts.public')

@section('title', 'Knowledge Base — Advernology Service')
@section('meta_description', 'Answers to common questions about migrating and keeping your Lumos email address with Advernology Service.')

@section('content')

{{-- HERO --}}
<section class="hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="hero-badge">
                    <i class="bi bi-book-fill"></i>
                    Knowledge Base
                </div>
                <h1>Answers about your<br><span>Lumos email migration</span></h1>
                <p class="lead mx-auto">
                    Everything you need to know about eligibility, pricing, and how the migration works.
                    Can't find what you need? <a href="mailto:support@advernologyservice.com" style="color:#fff;text-decoration:underline">Email our support team</a>.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- GETTING STARTED --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="text-center mb-5">
                    <div class="section-label">Getting Started</div>
                    <h2 class="section-title">Before you order</h2>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        How do I know if my email is eligible?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Use the <a href="{{ route('home') }}#check-eligibility" style="color:var(--brand-primary)">eligibility checker</a> on our home page.
                        Enter your Lumos email address and we'll instantly tell you whether it qualifies and which packages are available.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        What information do I need to provide?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Just your existing Lumos email address, your name, and a phone number in case our team needs to reach you
                        during the migration. If you're migrating multiple addresses, list them all in the notes field at checkout.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Which package should I choose?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Pick the package that covers the number of email addresses you need migrated. If you're not sure,
                        start with a single-address plan — you can always contact us to add more later.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- DURING & AFTER MIGRATION --}}
<section class="py-5 py-lg-6" style="background:var(--brand-surface)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="text-center mb-5">
                    <div class="section-label">During & After Migration</div>
                    <h2 class="section-title">What to expect</h2>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        How long does the migration take?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Most migrations are completed within 24 hours of payment. You'll get an email confirmation once yours is done.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Will I lose any existing emails?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        No. Our team times the mail routing cutover to keep your inbox receiving mail throughout the process,
                        so you shouldn't lose anything in transit.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Do I need to change my email address or notify my contacts?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        In most cases, no. The goal is to keep your existing Lumos email address working exactly as it did before,
                        so contacts and accounts tied to that address don't need to be updated.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        What if something looks wrong after migration?
                        <i class="bi bi-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Contact <a href="mailto:support@advernologyservice.com" style="color:var(--brand-primary)">support@advernologyservice.com</a>
                        with your order details and we'll take a look right away.
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
            </div>
        </div>
    </div>
</section>

@endsection
