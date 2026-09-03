<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'Advernology Service') — Keep Your Lumos Email Address</title>
    <meta name="description" content="@yield('meta_description', 'Don\'t lose your Lumos email. Advernology Service migrates your email address in minutes — starting at $6.99.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #F5A623;
            --brand-primary-dark: #d4891a;
            --brand-primary-light: #fef9ee;
            --brand-navy: #2b2b2b;
            --brand-navy-mid: #3d3d3d;
            --brand-text: #2b2b2b;
            --brand-muted: #6b7280;
            --brand-border: #e5e7eb;
            --brand-surface: #f9fafb;
        }

        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--brand-text); background: #fff; }

        /* NAV */
        .site-nav {
            background: #fff;
            border-bottom: 1px solid var(--brand-border);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .site-nav .navbar-brand {
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--brand-navy) !important;
            letter-spacing: -.3px;
        }
        .site-nav .nav-link {
            font-weight: 500;
            font-size: .9rem;
            color: #374151 !important;
            padding: 1.5rem .9rem !important;
        }
        .site-nav .nav-link:hover { color: var(--brand-primary) !important; }
        .btn-nav-cta {
            background: var(--brand-primary);
            color: #fff !important;
            border-radius: 6px;
            font-weight: 600;
            font-size: .875rem;
            padding: .5rem 1.25rem !important;
            border: none;
            transition: background .15s;
        }
        .btn-nav-cta:hover { background: var(--brand-primary-dark) !important; color: #fff !important; }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, var(--brand-navy) 0%, var(--brand-navy-mid) 60%, #3d4a8a 100%);
            color: #fff;
            padding: 90px 0 100px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(255,107,0,.18) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,107,0,.15);
            border: 1px solid rgba(255,107,0,.35);
            color: #ffb380;
            font-size: .8rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            letter-spacing: .3px;
        }
        .hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.5px;
            margin-bottom: 1.25rem;
        }
        .hero h1 span { color: var(--brand-primary); }
        .hero .lead {
            font-size: 1.1rem;
            color: rgba(255,255,255,.78);
            line-height: 1.7;
            max-width: 580px;
            margin: 0 auto 2.5rem;
        }
        .btn-hero-primary {
            background: var(--brand-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            padding: .85rem 2rem;
            transition: all .15s;
            box-shadow: 0 4px 14px rgba(255,107,0,.35);
        }
        .btn-hero-primary:hover { background: var(--brand-primary-dark); color: #fff; transform: translateY(-1px); }
        .btn-hero-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,.35);
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            padding: .85rem 1.75rem;
            transition: all .15s;
        }
        .btn-hero-outline:hover { border-color: #fff; background: rgba(255,255,255,.08); color: #fff; }

        /* TRUST BAR */
        .trust-bar {
            background: #fff;
            border-bottom: 1px solid var(--brand-border);
            padding: 1.1rem 0;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .85rem;
            font-weight: 600;
            color: #374151;
        }
        .trust-item i { color: var(--brand-primary); font-size: 1.1rem; }

        /* SECTIONS */
        .section-label {
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--brand-primary);
            margin-bottom: .5rem;
        }
        .section-title {
            font-size: clamp(1.5rem, 3vw, 2.1rem);
            font-weight: 800;
            letter-spacing: -.3px;
            color: var(--brand-navy);
        }
        .section-subtitle {
            color: var(--brand-muted);
            font-size: 1rem;
            line-height: 1.7;
            max-width: 540px;
            margin: 0 auto;
        }

        /* ELIGIBILITY CARD */
        .eligibility-card {
            background: #fff;
            border: 1.5px solid var(--brand-border);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.07);
        }
        .eligibility-card .input-group-text {
            background: var(--brand-surface);
            border-color: var(--brand-border);
            border-right: none;
        }
        .eligibility-card .form-control {
            border-color: var(--brand-border);
            font-size: 1rem;
            padding: .75rem 1rem;
        }
        .eligibility-card .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(255,107,0,.12);
        }
        .btn-check-email {
            background: var(--brand-primary);
            color: #fff;
            border: none;
            font-weight: 700;
            padding: 0 1.5rem;
            font-size: .95rem;
            transition: background .15s;
        }
        .btn-check-email:hover { background: var(--brand-primary-dark); color: #fff; }

        /* STEPS */
        .step-number {
            width: 52px; height: 52px;
            background: var(--brand-primary-light);
            color: var(--brand-primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .step-connector {
            position: absolute;
            top: 26px;
            left: calc(50% + 36px);
            right: calc(-50% + 36px);
            height: 2px;
            background: linear-gradient(90deg, var(--brand-primary) 0%, #e5e7eb 100%);
        }

        /* PRICING */
        .pricing-card {
            background: #fff;
            border: 1.5px solid var(--brand-border);
            border-radius: 16px;
            padding: 2rem 1.75rem;
            transition: all .2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .pricing-card:hover {
            border-color: var(--brand-primary);
            box-shadow: 0 8px 32px rgba(255,107,0,.12);
            transform: translateY(-3px);
        }
        .pricing-card.featured {
            border-color: var(--brand-primary);
            box-shadow: 0 8px 32px rgba(255,107,0,.15);
            transform: translateY(-6px);
            background: #fff;
        }
        .pricing-badge {
            background: var(--brand-primary);
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: .75rem;
        }
        .pricing-name { font-size: .875rem; font-weight: 600; color: var(--brand-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: .5rem; }
        .pricing-price { font-size: 2.5rem; font-weight: 800; color: var(--brand-navy); line-height: 1; }
        .pricing-price sup { font-size: 1.25rem; font-weight: 700; vertical-align: top; margin-top: .4rem; }
        .pricing-period { font-size: .8rem; color: var(--brand-muted); margin-top: .25rem; }
        .pricing-features li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: .875rem;
            color: #374151;
            margin-bottom: .5rem;
        }
        .pricing-features li i { color: #16a34a; margin-top: 2px; flex-shrink: 0; }
        .btn-pricing {
            background: var(--brand-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: .9rem;
            padding: .75rem;
            transition: all .15s;
            text-align: center;
            display: block;
            text-decoration: none;
            margin-top: auto;
        }
        .btn-pricing:hover { background: var(--brand-primary-dark); color: #fff; }
        .btn-pricing-outline {
            background: transparent;
            color: var(--brand-primary);
            border: 1.5px solid var(--brand-primary);
            border-radius: 8px;
            font-weight: 700;
            font-size: .9rem;
            padding: .75rem;
            transition: all .15s;
            text-align: center;
            display: block;
            text-decoration: none;
            margin-top: auto;
        }
        .btn-pricing-outline:hover { background: var(--brand-primary); color: #fff; }

        /* FAQ */
        .faq-item {
            border: 1px solid var(--brand-border);
            border-radius: 10px;
            margin-bottom: .75rem;
            overflow: hidden;
        }
        .faq-question {
            background: #fff;
            padding: 1.1rem 1.25rem;
            font-weight: 600;
            font-size: .95rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            width: 100%;
            text-align: left;
            color: var(--brand-navy);
        }
        .faq-question:hover { background: var(--brand-surface); }
        .faq-answer {
            display: none;
            padding: 0 1.25rem 1.1rem;
            font-size: .9rem;
            color: var(--brand-muted);
            line-height: 1.7;
            border-top: 1px solid var(--brand-border);
        }
        .faq-item.open .faq-answer { display: block; }
        .faq-item.open .faq-icon { transform: rotate(45deg); }
        .faq-icon { transition: transform .2s; font-size: 1.1rem; color: var(--brand-primary); }

        /* CTA BAND */
        .cta-band {
            background: linear-gradient(135deg, var(--brand-primary) 0%, #ff8c00 100%);
            color: #fff;
            padding: 70px 0;
        }
        .cta-band h2 { font-size: clamp(1.5rem, 3vw, 2.2rem); font-weight: 800; }
        .btn-cta-white {
            background: #fff;
            color: var(--brand-primary);
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            padding: .85rem 2rem;
            transition: all .15s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-cta-white:hover { background: #f9fafb; color: var(--brand-primary-dark); transform: translateY(-1px); }

        /* FOOTER */
        footer {
            background: var(--brand-navy);
            color: rgba(255,255,255,.6);
            padding: 3.5rem 0 1.5rem;
        }
        footer .footer-brand {
            font-weight: 800;
            font-size: 1.2rem;
            color: #fff;
            letter-spacing: -.2px;
        }
        footer h6 { color: rgba(255,255,255,.85); font-weight: 600; margin-bottom: 1rem; font-size: .85rem; text-transform: uppercase; letter-spacing: .5px; }
        footer a { color: rgba(255,255,255,.55); text-decoration: none; font-size: .875rem; transition: color .15s; }
        footer a:hover { color: #fff; }
        footer li { margin-bottom: .5rem; }
        footer hr { border-color: rgba(255,255,255,.1); margin: 2rem 0 1.25rem; }
        footer .copyright { font-size: .8rem; }

        /* GENERAL */
        .btn-primary { background: var(--brand-primary) !important; border-color: var(--brand-primary) !important; font-weight: 600; }
        .btn-primary:hover { background: var(--brand-primary-dark) !important; border-color: var(--brand-primary-dark) !important; }
        .text-primary { color: var(--brand-primary) !important; }

        @media (max-width: 768px) {
            .hero { padding: 60px 0 70px; }
            .step-connector { display: none; }
        }
    </style>

    @stack('styles')
</head>
<body>

<nav class="site-nav navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <span style="color:var(--brand-primary)">●</span> Advernology Service
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#how-it-works">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#pricing">Pricing</a></li>
                <li class="nav-item ms-2">
                    <a class="btn-nav-cta nav-link" href="{{ route('home') }}#check-eligibility">Check My Email →</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible m-3 fade show rounded-3 border-0 shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @yield('content')
</main>

<section class="cta-band">
    <div class="container text-center">
        <h2 class="mb-3">Don't wait until your Lumos email stops working.</h2>
        <p class="mb-4 opacity-85" style="font-size:1.05rem">Takes just minutes. Migration handled by our team.</p>
        <a href="{{ route('home') }}#check-eligibility" class="btn-cta-white">
            <i class="bi bi-search me-2"></i>Check My Email Address
        </a>
    </div>
</section>

<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 mb-2">
                <div class="footer-brand mb-2"><span style="color:var(--brand-primary)">●</span> Advernology Service</div>
                <p style="font-size:.875rem;line-height:1.7">We help former Lumos internet customers keep their email addresses by migrating them to our reliable, affordable platform.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Services</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('home') }}#check-eligibility">Email Migration</a></li>
                    <li><a href="{{ route('home') }}#pricing">Pricing</a></li>
                    <li><a href="{{ route('home') }}#how-it-works">How It Works</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Support</h6>
                <ul class="list-unstyled">
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="mailto:support@advernologyservice.com">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6>Contact</h6>
                <p style="font-size:.875rem"><i class="bi bi-envelope me-2" style="color:var(--brand-primary)"></i><a href="mailto:support@advernologyservice.com">support@advernologyservice.com</a></p>
                <p style="font-size:.875rem"><i class="bi bi-globe me-2" style="color:var(--brand-primary)"></i><a href="https://advernologyservice.com">advernologyservice.com</a></p>
                <div class="mt-3 d-flex gap-2">
                    <span style="background:rgba(255,107,0,.15);border:1px solid rgba(255,107,0,.3);color:#ffb380;font-size:.72rem;font-weight:600;padding:4px 10px;border-radius:20px"><i class="bi bi-shield-check me-1"></i>Secure Payments</span>
                    <span style="background:rgba(255,107,0,.15);border:1px solid rgba(255,107,0,.3);color:#ffb380;font-size:.72rem;font-weight:600;padding:4px 10px;border-radius:20px"><i class="bi bi-lock me-1"></i>SSL Encrypted</span>
                </div>
            </div>
        </div>
        <hr>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 copyright">
            <span>© {{ date('Y') }} Advernology Service. All rights reserved.</span>
            <span>Not affiliated with Lumos Networks.</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.faq-question').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.closest('.faq-item');
            const wasOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
            if (!wasOpen) item.classList.add('open');
        });
    });
</script>
@stack('scripts')
</body>
</html>
