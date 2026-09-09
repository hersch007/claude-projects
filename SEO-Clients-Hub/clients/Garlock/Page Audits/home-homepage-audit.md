# On-Page SEO Audit — Home (Homepage)

- **Live URL:** https://gcpflexpack-24024882.hs-sites.com/
- **HubSpot editor:** https://app.hubspot.com/pages/24024882/editor/212643437655/settings
- **Production URL:** https://gcpflexpack.com/
- **Page type:** Home
- **Audited:** 2026-08-14 · **Status:** **IMPLEMENTED — verified live on staging 2026-09-07** (title, meta, 9/10 alt text, Organization schema). Remaining: hero alt + logo alt (see Notes).

## Keywords
- **Primary:** flexible packaging, custom flexible packaging & printing
- **Secondary:** printed rollstock, premade pouches, labels, sustainable packaging, HD flexographic printing, food/medical/aerospace packaging

## Recommended Title (61 chars)
`Custom Flexible Packaging & Printing | C-P Flexible Packaging`

## Recommended Meta Description (146 chars)
`C-P Flexible Packaging delivers custom printed rollstock, pouches, labels & sustainable packaging—60 years of HD flexo expertise. Explore our range.`

## Recommended H1 (on-page heading)
- **Current:** Inspired Packaging for Today's Lifestyles
- **Update to:** **No change** — the tagline H1 is fine for the homepage; keyword relevance is carried by the title tag + Organization schema.

## Image Alt Text (10 content images — verify current alt in HubSpot)
| Image | Recommended alt | Chars |
|---|---|---|
| `sustainable-paper-packaging.webp` | Sustainable paper-based flexible packaging from C-P Flexible Packaging | 69 |
| `cleanroom-medical-packaging.webp` | Medical and electronics cleanroom packaging by C-P Flexible Packaging | 68 |
| `food-packaging.webp` | Custom printed food packaging by C-P Flexible Packaging | 54 |
| `recloseable-packaging-market-circle.webp` | Consumer-friendly recloseable flexible packaging from C-P Flexible Packaging | 75 |
| `health-wellness-shrink-sleeve.webp` | Health and wellness product with a C-P Flexible Packaging shrink sleeve | 70 |
| `products-groupshot-1.webp` | Group shot of C-P Flexible Packaging's flexible packaging product portfolio | 74 |
| `concept-to-prototype.webp` | C-P Flexible Packaging concept-to-prototype design process | 57 |
| `sustainability.webp` | C-P Flexible Packaging sustainability and recycling commitment | 61 |
| `six-decades-of-experience.webp` | C-P Flexible Packaging — six decades of flexible packaging experience | 68 |
| `about-us.webp` | C-P Flexible Packaging customer service and support team | 56 |

## Recommended Structured Data (Organization schema — the site's most important)
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "C-P Flexible Packaging",
  "url": "https://gcpflexpack.com/",
  "logo": "https://gcpflexpack.com/hubfs/logo.png",
  "foundingDate": "1958",
  "description": "Custom flexible packaging converter — printed rollstock, premade pouches, labels and sustainable packaging — serving food, medical, electronics and aerospace markets.",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "15 Grumbacher Road",
    "addressLocality": "York",
    "addressRegion": "PA",
    "postalCode": "17406",
    "addressCountry": "US"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-800-815-0667",
    "contactType": "sales"
  },
  "sameAs": ["https://www.linkedin.com/company/c-p-flexible-packaging/"]
}
```

## Notes
- ⚠️ **Update the logo URL** in the schema to the real HubSpot-hosted logo file before publishing.
- **10 content images** need alt text — confirm current alt in HubSpot (several are likely filename-style) and apply the recommendations. This makes Home a heavier page than a standard product page.
- H1 tagline is fine; SEO relevance carried by the title + Organization schema.
- ✅ **Verified live on staging 2026-09-07** — title, meta, Organization schema (logo path filled in) and 9 of 10 alt tags match this audit.
- ⚠️ **Still to fix (2 items):** (1) hero image `sustainable-paper-packaging.webp` currently carries the sustainability-block alt ("…sustainability and recycling commitment") — change to the recommended "Sustainable paper-based flexible packaging from C-P Flexible Packaging". (2) Header/footer logo alt is still "Garlock CP Flex Logo" / "Garlock CP Flex Logo (1)" — set the sitewide standard "C-P Flexible Packaging company logo" in the global modules and drop the duplicate "(1)" logo file.
