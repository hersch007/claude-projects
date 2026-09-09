# On-Page SEO Audit — Contact Us

- **Live URL:** https://gcpflexpack-24024882.hs-sites.com/contact/
- **HubSpot editor:** https://app.hubspot.com/pages/24024882/editor/212726038965/settings
- **Production URL:** https://gcpflexpack.com/contact/
- **Page type:** Contact
- **Audited:** 2026-08-14 · **Status:** **IMPLEMENTED — verified live on staging 2026-09-07** (title, meta, H1, ContactPage schema).

## Keywords
- **Primary:** contact C-P Flexible Packaging, flexible packaging contact
- **Secondary:** flexible packaging locations, York PA headquarters, request a quote, packaging experts

## Recommended Title (50 chars)
`Contact C-P Flexible Packaging | 9 Plant Locations`

## Recommended Meta Description (139 chars)
`Contact C-P Flexible Packaging—9 manufacturing locations across the US & Canada. Call (800) 815-0667 or message our packaging experts online.`

## Recommended H1 (on-page heading)
- **Current:** ~~Contact us~~
- **Update to:** **Contact C-P Flexible Packaging**

## Images
Contact page — no page-specific content images to alt-text.

## Recommended Structured Data (ContactPage schema)
```json
{
  "@context": "https://schema.org",
  "@type": "ContactPage",
  "name": "Contact C-P Flexible Packaging",
  "url": "https://gcpflexpack.com/contact/",
  "mainEntity": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/",
    "telephone": "+1-800-815-0667",
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "+1-800-815-0667",
      "contactType": "sales"
    }
  }
}
```

## Notes
- **H1 "Contact us" is generic** — change to **"Contact C-P Flexible Packaging"** for a branded, keyword-bearing heading.
- ✅ **NAP abbreviations standardized (2026-08-14)** — Contact page now spells out Street/Road/South (York, Buffalo, Fond du Lac, Fruth, Aurora) to match the location pages + LocalBusiness schema. All 9 addresses now internally consistent.
- ⚠️ **Still to do — GBP verification:** confirm all 9 match each **Google Business Profile** exactly (GBP is the master record — highest-impact NAP check):
  - C-P York · C-P Buffalo · C-P Bristol · C-P Lakeville · C-P Aurora · C-P Fond du Lac · Fruth Custom Packaging · Cleanroom Film & Bags · Preferred Packaging
- ✅ **Verified live on staging 2026-09-07** — title, meta, H1 and ContactPage schema all match this audit.
- ℹ️ **Map module (optional polish):** all 9 location map pins share alt="Preferred Packaging" (module default). Either set alt="" (each pin already links with a title) or give each pin its location name, e.g. "C-P York location". Leaflet tile images (`5.png`, `6.png`) are map tiles — ignore.
