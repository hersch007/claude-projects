# SEO Audit — Aerospace Packaging

**Page:** Aerospace Products Packaging
**Live URL:** https://gcpflexpack-24024882.hs-sites.com/markets/aerospace-packaging/
**HubSpot Editor:** https://app.hubspot.com/pages/24024882/editor/212697483948
**Audited:** 2026-06-23
**Auditor:** SEO (C-P Flexible Packaging)
**Status:** Recommendations ready — pending implementation in HubSpot

---

## Page Context

- **H1:** Aerospace Products Packaging
- **Key H2s:** Aerospace packaging · Packaging process for the aerospace market · Let's talk flexible packaging · Sustainable Packaging
- **Primary keyword:** aerospace packaging
- **Secondary keywords:** ESD packaging, flame-retardant film, cleanroom packaging, barrier packaging, aviation component protection, transit covers

---

## 1. Recommended Page Title (60 chars)

```
Aerospace Packaging | ESD, Flame-Retardant & Cleanroom Films
```

- Keyword front-loaded ("Aerospace Packaging")
- Includes high-value secondary terms (ESD, flame-retardant, cleanroom)
- Brand at the end; under the 155-char limit

## 2. Recommended Meta Description (151 chars)

```
Protect aerospace parts with custom flexible packaging—ESD, flame-retardant & cleanroom films built for aviation. Talk to C-P Flexible Packaging today.
```

- Benefit-led opening ("Protect aerospace parts")
- Keywords woven in naturally
- Action-oriented CTA ("Talk to C-P Flexible Packaging today")

---

## 3. Image + Alt Text Audit

| Image # & Context | Current Alt Text | Suggested Alt Text | Char Count | Rationale & SEO Benefit |
|---|---|---|---|---|
| 1 — Company logo (`garlock-cp-logo.png`) | `garlock-cp-logo` | `C-P Flexible Packaging company logo` | 48 | Brand alt for logo; replace filename. Apply consistently sitewide. |
| 2 — Hero aerospace shot (`aerospace-market-1.webp`) | *empty* | `Custom flexible packaging protecting aerospace and aviation components in transit` | 80 | Primary keyword + context on the lead image; major relevance + a11y gain. |
| 3 — Flame-retardant film (`Flame-Retardant-Polyethylene.webp`) | *empty* | `Flame-retardant polyethylene film for aerospace parts packaging and protection` | 78 | Targets "flame-retardant film" secondary keyword. |
| 4 — Cleanroom packaging (`cleanroom-aerospace-1.webp`) | *empty* | `Cleanroom-grade flexible packaging for sensitive aerospace electronic components` | 79 | Captures "cleanroom packaging" + ESD intent. |
| 5 — GreenStream sustainability (`greenstream-packaging-products-1.webp`) | `greenstream-packaging-products-1` | `GreenStream sustainable flexible packaging products by C-P Flexible Packaging` | 71 | Ties image to sustainability sub-brand + company. |
| 6 — Phone icon (`phone.svg`) | `phone` | `alt=""` (decorative) | 0 | Icon beside a visible phone number; empty alt avoids screen-reader noise. |
| 7–9 — Certification badges (`footer-1/2/3.webp`) | *empty* | `[Certification name] food safety & quality certification – C-P Flexible Packaging` | ~70 | ⚠️ Replace `[Certification name]` with the real cert (SQF, BRC, ISO 9001, etc.). Trust signal — not decorative. |
| 10 — Favicon (`garlock-cp-favicon.png`) | *empty* | `alt=""` (decorative) | 0 | Favicons are not content; no alt needed. |

---

## 3a. Recommended Schema — JSON-LD (Service only)

**Decision:** Add **Service** schema only. **Breadcrumb skipped** — there is no visible breadcrumb in the page UI, so BreadcrumbList would mark up a non-existent element. Service schema is *not* a rich-result type (no SERP change); it helps engines understand the page and requires **no visible UI and no new content** — it simply labels existing copy.

Paste the script block below into HubSpot Settings → Advanced → Head HTML. Uses production URLs (`gcpflexpack.com`) — confirm the path stays `/markets/aerospace-packaging/` at launch. `areaServed`/`offers` are omitted because they are not stated on the page.

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Aerospace Flexible Packaging",
  "serviceType": "Aerospace flexible packaging",
  "description": "Custom flexible packaging that protects aerospace and aviation components during shipment and storage, including ESD, flame-retardant, cleanroom, and high-barrier films engineered for stringent aerospace requirements.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/markets/aerospace-packaging/"
}
</script>
```

> Verify a sitewide **Organization** schema exists (usually on the homepage) before relying on the `provider` reference. If it doesn't, add one with logo, social profiles, and contact details.

## 4. HubSpot Image SEO Quick Tips

- **Rename files before re-upload** where possible (e.g., `aerospace-market-1.webp` → `aerospace-flexible-packaging.webp`). HubSpot uses filenames as a ranking signal.
- Keep **WebP** (already in use) and enable **lazy loading** on below-the-fold images via the HubSpot module setting (Core Web Vitals).
- Set **width/height** attributes to avoid Cumulative Layout Shift (CLS).
- Use HubSpot's **alt-text field** in the image module rather than hard-coding alt in HTML, so it survives template changes.
- Add a **descriptive caption** to the hero where the design allows — reinforces topical relevance.

---

## 5. Action Checklist (for implementation in HubSpot)

- [ ] Update page title tag
- [ ] Update meta description
- [ ] Apply 5 content-image alt texts (images 1–5)
- [ ] Set decorative alt="" on phone icon + favicon
- [ ] Confirm certification names and apply badge alt text (images 7–9)
- [ ] (Optional) Rename image files to keyword-friendly slugs
- [ ] (Optional) Add FAQ section + FAQPage schema

> Rebrand note: the site still reads **"C-P Flexible Packaging"** in titles/logos. Recommended alt text uses the new **C-P Flexible Packaging** brand to support the transition.
