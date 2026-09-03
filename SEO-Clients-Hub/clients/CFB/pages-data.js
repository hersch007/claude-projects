// CFB On-Page SEO Audit Running Log — data file
// Add new page entries to `pages` as batches are audited, then run gen-running-log.js
// Status convention: 'AUDITED YYYY-MM-DD — pending implementation' → 'IMPLEMENTED in HubSpot YYYY-MM-DD (title, meta, alt text, schema)'

module.exports = {
  client: 'Cleanroom Film & Bags',
  docTitle: 'On-Page SEO Audit — Running Log',
  docSubtitle: 'Image alt text, page titles, meta descriptions & schema',
  lastUpdated: '2026-07-16',
  totalPages: 52,

  guidelines: [
    'Rename image files to keyword-friendly slugs before upload — HubSpot uses filenames as a ranking signal (e.g., "Screenshot 2026-02-25 152600.jpg" is dead weight).',
    'Prefer WebP/AVIF format and enable lazy loading on below-the-fold images for Core Web Vitals.',
    'Set explicit width/height to prevent layout shift (CLS).',
    'Enter alt text in HubSpot’s image-module alt field, not hard-coded HTML, so it survives template changes.',
    'Alt text: 50–125 characters, descriptive, natural, one relevant keyword — never keyword-stuffed.',
    'Use alt="" for purely decorative images (repeated logos that are not links, spacer graphics).',
    'CFB shares HubSpot portal 24024882 with other brands — keep CFB assets in the "Cleanroom Film and Bags" hubfs folder so cross-brand mixups (like the vendor seal served from the Fruth folder) don’t recur.'
  ],

  // Sitewide elements — set once, apply everywhere. Not repeated in per-page tables.
  globalElements: [
    ['Company logo, header (CFB Logo.png)', 'Cleanroom Film & Bags Logo', 'Cleanroom Film & Bags logo', '26', 'Brand alt for the header logo; standardize casing sitewide.'],
    ['Company logo, mid-page instance (CFB Logo.png)', 'CFB Logo', 'Cleanroom Film & Bags logo', '26', 'Standardize with header instance for consistency.'],
    ['Company logo, footer (CFB Logo.png)', 'CFB Logo', 'Cleanroom Film & Bags logo', '26', 'Footer module — set once, propagates sitewide. Use alt="" instead if it is a non-linked duplicate.'],
    ['Verified Vendor seal (verified-vendor-seal-2024-med.avif)', 'verified-vendor-seal-2024-med', '2024 Verified Vendor seal', '25', '⚠ FLAG: file is served from the Fruth Custom Packaging hubfs folder on the shared portal. Confirm the seal was issued to CFB (not Fruth), then move the asset into the CFB folder. Seal is also dated 2024 — renew or remove.']
  ],

  pages: [
    {
      name: 'Homepage',
      status: 'IMPLEMENTED in HubSpot 2026-07-16 (title, meta, alt text, Organization schema)',
      liveUrl: 'https://www.cleanroomfilm.com/',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-16',
      primaryKw: 'cleanroom packaging',
      secondaryKw: 'cleanroom film, cleanroom bags, sterile packaging, Class 100 cleanroom, ESD packaging, USA-made packaging',
      title: { text: 'Cleanroom Packaging: Film & Bags Manufacturer | CFB', chars: 52 },
      meta: { text: 'ISO-certified cleanroom packaging manufacturer. Class 100 film & bags for medical, semiconductor, aerospace & pharma. USA made 35+ years. Get a quote.', chars: 150 },
      notes: 'H1 still split across three hero phrases ("From the tiniest of micro-chips" / "To life saving surgical tools" / …). Consolidate to one keyword-bearing H1, e.g. "Cleanroom Packaging Film & Bags — Sterile Delivery When It Matters Most", and demote narrative lines to styled paragraph text.',
      images: [
        ['Products montage (cleanroom-film-and-bags-products.png)', 'cleanroom-film-and-bags-products', 'Cleanroom film rollstock and sterile cleanroom bags manufactured by CFB', '72', 'Filename-dump alt replaced with primary keyword + both product families. Verify against actual image.'],
        ['Capabilities photo (cleanroom-packaging-capabilities (1).jpg)', 'cleanroom packaging capabilities', 'Technician operating blown film extrusion equipment in CFB’s Class 100 cleanroom', '81', 'Adds process + Class 100 credential. Verify the pictured process; adjust verb if it shows sealing/converting instead.'],
        ['Unnamed screenshot (Screenshot 2026-02-25 152600.jpg)', 'Screenshot 2026-02-25 152600', 'Cleanroom bag production at CFB’s Placentia facility (verify content, then finalize)', '55', '⚠ Re-upload with a descriptive filename first (e.g., cleanroom-bag-production.jpg) — alt text alone fixes only half the problem.'],
        ['Facility photo (cleanroom-packaging-facility.jpg)', 'cleanroom packaging facility', 'Cleanroom Film & Bags ISO-certified manufacturing facility in Placentia, California', '84', 'Adds brand, credential, and location — supports local/entity signals.']
      ],
      schemaLabel: 'Deployed Structured Data (Organization schema) — LIVE as of 2026-07-16',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Cleanroom Film & Bags",
  "alternateName": "CFB",
  "url": "https://www.cleanroomfilm.com/",
  "logo": "https://www.cleanroomfilm.com/hubfs/CFB%20Logo.png",
  "description": "ISO-certified cleanroom packaging manufacturer. Class 100 film & bags for medical, semiconductor, aerospace & pharma. USA made 35+ years.",
  "foundingDate": "1990",
  "slogan": "Sterile Packaging Solutions That Fuel Life",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "1700 Barcelona Circle",
    "addressLocality": "Placentia",
    "addressRegion": "CA",
    "postalCode": "92870",
    "addressCountry": "US"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-714-744-8361",
    "contactType": "sales",
    "email": "sales@cleanbags.com",
    "areaServed": "US"
  },
  "sameAs": [
    "https://www.linkedin.com/company/cleanbags"
  ],
  "hasCredential": [
    { "@type": "EducationalOccupationalCredential", "name": "ISO 9001:2015" },
    { "@type": "EducationalOccupationalCredential", "name": "ISO 14644-1 Class 5 Cleanroom" }
  ],
  "knowsAbout": [
    "cleanroom packaging",
    "cleanroom film",
    "cleanroom bags",
    "ESD packaging",
    "low outgassing films",
    "CLEANTUFF films",
    "Tyvek pouches",
    "sterile barrier packaging"
  ]
}
</script>`
    },

    {
      name: 'Medical Cleanroom Packaging',
      status: 'IMPLEMENTED in HubSpot 2026-07-16 (title, meta, alt text, schema)',
      liveUrl: 'https://www.cleanroomfilm.com/markets/medical-cleanroom-packaging',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-16',
      primaryKw: 'medical cleanroom packaging',
      secondaryKw: 'medical device packaging, CLEANTUFF® LDPE film, sterile medical bags, Class 100 packaging, nylon medical packaging',
      title: { text: 'Medical Cleanroom Packaging: CLEANTUFF® & LDPE Bags | CFB', chars: 57 },
      meta: { text: 'Medical cleanroom packaging in ISO-certified Class 100 cleanrooms — CLEANTUFF® LDPE film, nylon & sterile bags for medical devices. Get a quote.', chars: 144 },
      notes: 'Current title is 76 chars (truncates); current meta has a live grammar error ("manufacturer of cleanroom bags and sterile for the medical cleanroom packaging market") — replace, don’t patch. H1 ("Medical Cleanroom Packaging Supplies") is fine. ~550–600 words, best content depth of the market pages. Add contextual link to the organ-transplant Learning Center story for E-E-A-T.',
      images: [
        ['Hero (medical-cleanroom-packaging.jpg)', 'medical-cleanroom-packaging', 'Medical device packaging produced in CFB’s Class 100 cleanroom', '62', 'Filename-dump alt replaced with keyword + credential.'],
        ['CT-100 section (cleanroom-film-products-sheeting.jpg)', 'cleanroom-film-products-sheeting', 'CT-100 CLEANTUFF® LDPE cleanroom sheeting for medical packaging', '63', 'Ties flagship exclusive material to the medical keyword.'],
        ['Standard LDPE section (cleanroom-film-products.jpg)', 'cleanroom-film-products', 'Standard LDPE cleanroom film rolls for medical packaging', '56', 'Describes the actual product pictured + page keyword.'],
        ['Nylon section (cleanroom-bottom-seal-bags.jpg)', 'nylon packaging', 'Nylon bottom-seal cleanroom bags for medical devices', '52', 'Names the specific bag type shown; adds device intent.']
      ],
      schemaLabel: 'Recommended Structured Data (Service schema)',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Medical Cleanroom Packaging",
  "serviceType": "Medical cleanroom packaging manufacturing",
  "description": "Medical cleanroom packaging manufactured in ISO-certified Class 100 cleanrooms, including CT-100 CLEANTUFF® LDPE film, standard LDPE, and nylon bags for medical devices and sterile applications.",
  "provider": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "url": "https://www.cleanroomfilm.com/"
  },
  "areaServed": "US",
  "url": "https://www.cleanroomfilm.com/markets/medical-cleanroom-packaging"
}
</script>`
    },

    {
      name: 'Semiconductor Cleanroom Packaging',
      status: 'IMPLEMENTED in HubSpot 2026-07-16 (title, meta, alt text, schema)',
      liveUrl: 'https://www.cleanroomfilm.com/markets/semiconductor-cleanroom-packaging',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-16',
      primaryKw: 'semiconductor cleanroom packaging',
      secondaryKw: 'low outgassing film, ULO poly film, anti-static nylon, FOUP packaging, wafer packaging, ISO 14644',
      title: { text: 'Semiconductor Cleanroom Packaging: ULO Film & ESD Bags | CFB', chars: 60 },
      meta: { text: 'Semiconductor cleanroom packaging with extreme low outgassing (ULO) film and anti-static nylon — USA-made to ISO 14644 standards. Request a quote.', chars: 146 },
      notes: 'Thinnest of the three market pages (~350–400 words) — expand toward 800 with wafer/FOUP applications and cleanliness specs. Add contextual links to the FOUP case study (exists in Learning Center) and the ULO material page — this page should be the hub for CFB’s strongest exclusive. Current title/meta serviceable but generic: no ULO, no CTA.',
      images: [
        ['Hero (semi-conductor-cleanroom-packaging.jpg)', 'semi-conductor-cleanroom-packaging', 'Semiconductor wafer and chip packaging in CFB’s Class 100 cleanroom', '67', 'Filename-dump alt replaced with application + credential.'],
        ['ULO section (extreme-low-outgassing.jpg)', 'ultra low outgassing', 'Extreme low outgassing (ULO) poly film for semiconductor packaging', '66', 'Matches the exclusive material’s official name + page keyword.'],
        ['Anti-static section (orange-anti-static-film.jpg)', 'orange anti-static film', 'Orange anti-static nylon film protecting semiconductor components', '65', 'Adds material + protective function + audience.'],
        ['Capabilities (cleanroom-packaging-capabilities (1).jpg)', 'cleanroom-packaging-capabilities (1)', 'Blown film extrusion line producing semiconductor cleanroom packaging', '69', 'Image reused from homepage — alt should be contextual to this page, not the filename.']
      ],
      schemaLabel: 'Recommended Structured Data (Service schema)',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Semiconductor Cleanroom Packaging",
  "serviceType": "Semiconductor cleanroom packaging manufacturing",
  "description": "Cleanroom packaging for semiconductors and microelectronics, including extreme low outgassing (ULO) poly film and anti-static nylon, manufactured in the USA to ISO 14644 cleanliness standards.",
  "provider": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "url": "https://www.cleanroomfilm.com/"
  },
  "areaServed": "US",
  "url": "https://www.cleanroomfilm.com/markets/semiconductor-cleanroom-packaging"
}
</script>`
    },

    {
      name: 'Aerospace Cleanroom Packaging',
      status: 'IMPLEMENTED in HubSpot 2026-07-17 (title, meta, alt text, schema)',
      liveUrl: 'https://www.cleanroomfilm.com/markets/aerospace-cleanroom-packaging',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-16',
      primaryKw: 'aerospace cleanroom packaging',
      secondaryKw: 'ESD packaging, flame-retardant film, barrier packaging, large part transit covers, Aclar® PCTFE, cryogenic packaging',
      title: { text: 'Aerospace Cleanroom Packaging: ESD & Flame-Retardant | CFB', chars: 58 },
      meta: { text: 'CFB offers aerospace packaging including barrier packaging, ESD packaging, large part transit covers, cleanroom solutions, and flame retardant options.', chars: 151 },
      notes: 'Strongest of the three pages — current meta is genuinely good (kept as-is above); current title fine but the recommended version adds the two differentiators buyers search. Alt text here is already partial-quality; the suggestions below are polish, not rescue. The space case study ("Strong Enough for Outer Space", -455°F ACLAR Hydroblock® PCTFE) is already featured — add a cross-link to the Boeing/Aclar® case study and the Aclar® material page too.',
      images: [
        ['Hero (aerospace-cleanroom-packaging.jpg)', 'aerospace cleanroom packaging', 'Aerospace cleanroom packaging protecting aviation components', '60', 'Adds the protective function to an already-decent alt.'],
        ['Barrier section (cleanroom-film-products-rollstock.jpg)', 'barrier packaging for aerospace parts, products', 'High-barrier cleanroom film rollstock for aerospace parts', '57', 'Names the actual product (rollstock) + tightens phrasing.'],
        ['ESD section (esd-cleanroom-packaging.jpg)', 'esd cleanroom packaging for aerospace', 'ESD cleanroom packaging for aerospace electronics', '49', 'Minor polish: capitalization + electronics context.'],
        ['Transit covers (aerospace-cleanroom-packaging.jpg — reused)', 'Large Part Transit Covers for aerospace', 'Large part transit covers for oversized aerospace components', '60', 'Same file as hero, reused — consider a distinct photo; alt made section-specific.'],
        ['Flame-retardant section (aerospace-cleanroom-packaging-flame-retardant.jpg)', 'aerospace cleanroom packaging flame-retardant', 'Flame-retardant cleanroom packaging film for aerospace safety compliance', '72', 'Adds the compliance intent buyers search for.'],
        ['Case study (aerospace-cleanroom-packaging-case-study.jpg)', 'aerospace cleanroom packaging case study', 'CFB cryogenic packaging case study — space shuttle storage at -455°F', '68', 'Specific beats generic: the -455°F detail is the hook.']
      ],
      schemaLabel: 'Recommended Structured Data (Service schema)',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Aerospace Cleanroom Packaging",
  "serviceType": "Aerospace cleanroom packaging manufacturing",
  "description": "Cleanroom packaging for aerospace components including high-barrier films, ESD packaging, flame-retardant options, large part transit covers, and cryogenic-tolerant ACLAR Hydroblock® PCTFE rated to -455°F.",
  "provider": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "url": "https://www.cleanroomfilm.com/"
  },
  "areaServed": "US",
  "url": "https://www.cleanroomfilm.com/markets/aerospace-cleanroom-packaging"
}
</script>`
    },

    {
      name: 'Pharmaceutical Cleanroom Packaging',
      status: 'IMPLEMENTED in HubSpot 2026-07-17 (title, meta, alt text, schema)',
      liveUrl: 'https://www.cleanroomfilm.com/markets/pharmaceutical-cleanroom-packaging',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-16',
      primaryKw: 'pharmaceutical cleanroom packaging',
      secondaryKw: 'pharma packaging, LDPE bags, HDPE bags, gamma irradiation packaging, FDA compliant packaging, sterile drug packaging',
      title: { text: 'Pharmaceutical Cleanroom Packaging: FDA-Compliant Bags | CFB', chars: 60 },
      meta: { text: 'Pharmaceutical cleanroom packaging that ensures drug safety — ISO-certified LDPE, HDPE & nylon bags for gamma, heat & steam sterilization. Get a quote.', chars: 149 },
      notes: 'Current meta is decent (sterilization-focused). ~350–400 words. H1 "Cleanroom Pharmaceutical Packaging" is fine but word order differs from URL/title — keep title keyword-first. Page claims FDA & EU compliance but the body does not name specific sterilization methods the meta references (gamma/heat/steam) — add a short methods paragraph so page matches its own snippet. Add link to /standards for the compliance claims.',
      images: [
        ['Hero (pharmaceutical-cleanroom-packaging.jpg)', 'pharmaceutical cleanroom packaging', 'Pharmaceutical cleanroom packaging for sterile drug products', '60', 'Adds application context to a keyword-only alt.'],
        ['LDPE section (perforated-bags.jpg)', 'cleanroom ldpe bags', 'Perforated LDPE cleanroom bags for pharmaceutical packaging', '58', 'Names the actual product (perforated) + page keyword.'],
        ['HDPE section (cleanroom-bags-zipper (1).jpg)', 'cleanroom hdpe bags', 'HDPE cleanroom zipper bags for pharmaceutical products', '54', 'Reflects the zipper bag pictured + material.'],
        ['Poly tubing section (cleanroom-bags-on-roll.jpg)', 'cleanroom poly bags/tubing', 'Cleanroom poly bags on a roll for pharmaceutical packaging', '57', 'Cleaner phrasing; drops the slash; adds intent.'],
        ['Nylon section (cleanroom-film-products-sheeting.jpg)', 'cleanroom nylon bags', 'Nylon cleanroom sheeting for pharmaceutical packaging', '53', 'Image is sheeting, not bags — alt corrected to match.'],
        ['Compliance (pharma-packaging-cleanroom.jpg)', 'pharma packaging cleanroom', 'FDA- and EU-compliant pharmaceutical cleanroom packaging', '56', 'Ties image to the compliance message it accompanies.']
      ],
      schemaLabel: 'Recommended Structured Data (Service schema)',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Pharmaceutical Cleanroom Packaging",
  "serviceType": "Pharmaceutical cleanroom packaging manufacturing",
  "description": "ISO-certified pharmaceutical cleanroom packaging including LDPE, HDPE, and nylon bags suitable for gamma irradiation, heat, and steam sterilization, meeting FDA and EU compliance requirements.",
  "provider": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "url": "https://www.cleanroomfilm.com/"
  },
  "areaServed": "US",
  "url": "https://www.cleanroomfilm.com/markets/pharmaceutical-cleanroom-packaging"
}
</script>`
    },

    {
      name: 'Electronic Cleanroom Packaging',
      status: 'IMPLEMENTED in HubSpot 2026-07-17 (title, meta, alt text, schema)',
      liveUrl: 'https://www.cleanroomfilm.com/markets/electronic-cleanroom-packaging',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-16',
      primaryKw: 'electronic cleanroom packaging',
      secondaryKw: 'ESD bags, anti-static packaging, static shielding bags, CleanTronics, low outgassing film, microelectronics packaging',
      title: { text: 'Electronic Cleanroom Packaging: ESD & Anti-Static Bags | CFB', chars: 60 },
      meta: { text: 'Electronic cleanroom packaging for microelectronics — ESD, anti-static & static-shielding bags plus CFB CleanTronics™ advanced packaging. Get a quote.', chars: 149 },
      notes: 'Current meta is weak and shares the medical page’s boilerplate ("manufacturer of cleanroom bags and sterile packaging"). Best keyword-rich market page after aerospace — 6 H2s, ~450 words. IMPORTANT: CleanTronics™ is a CFB-branded electronics line (like CLEANTUFF®/ULO) — treat it as an exclusive keyword to own; note the page uses both "ELO" and "ULO/Extreme-Low Outgassing" — standardize the abbreviation sitewide. Cross-link to the CleanTronics Learning Center announcement and the ESD/static-shielding material pages.',
      images: [
        ['Hero (cfb-cleantronics.png)', 'cfb-cleantronics', 'CFB CleanTronics™ advanced cleanroom packaging for electronics', '61', 'Filename-dump alt replaced; surfaces the branded line.'],
        ['ULO section (extreme-low-outgassing.jpg)', 'ultra low outgassing', 'Extreme low outgassing (ULO) film for electronic components', '58', 'Matches official material name; fixes "ultra" vs "extreme".'],
        ['Nylon section (nylon-electronic-packaging.jpg)', 'nylon electronic packaging', 'Nylon film packaging for electronic components', '46', 'Tightens; keeps material + audience.'],
        ['ESD section (esd-cleanroom-packaging.jpg)', 'esd cleanroom packaging', 'ESD static-shielding bags for electronic component protection', '60', 'Adds the shielding function + audience.'],
        ['Bags-on-roll section (cleanroom-bags-on-roll.jpg)', 'cleanroom bags-on-roll', 'Cleanroom bags on a roll for electronics packaging', '50', 'Readable phrasing + page context.'],
        ['Anti-static LDPE section (anti-static-ldpe.jpg)', 'anti-static ldpe', 'Pink anti-static LDPE film for electronic and medical protection', '63', 'Adds the color/type detail buyers recognize.']
      ],
      schemaLabel: 'Recommended Structured Data (Service schema)',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Electronic Cleanroom Packaging",
  "serviceType": "Electronic and microelectronic cleanroom packaging",
  "description": "Cleanroom packaging for electronics and microelectronics, including ESD, anti-static, and static-shielding bags, low outgassing film, and CFB CleanTronics advanced packaging.",
  "provider": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "url": "https://www.cleanroomfilm.com/"
  },
  "areaServed": "US",
  "url": "https://www.cleanroomfilm.com/markets/electronic-cleanroom-packaging"
}
</script>`
    },

    {
      name: 'Food Cleanroom Packaging',
      status: 'IMPLEMENTED in HubSpot 2026-07-17 (title, meta, alt text, schema)',
      liveUrl: 'https://www.cleanroomfilm.com/markets/food-cleanroom-packaging',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-16',
      primaryKw: 'food cleanroom packaging',
      secondaryKw: 'nylon food bags, high heat oven bags, food grade cleanroom packaging, FDA approved food bags, oven safe bags',
      title: { text: 'Food Cleanroom Packaging: Nylon & High-Heat Oven Bags | CFB', chars: 59 },
      meta: { text: 'Food-grade cleanroom packaging — nylon bags and high-heat oven bags (to 375°F), BPA-free & FDA-approved for oven, microwave & freezer. Get a quote.', chars: 145 },
      notes: 'Thinnest market page (~320 words) and ranks #19 for "clean room for food packaging" (Search Volume 10) — low competition, easy win with more depth. Expand toward 600 words: add nylon barrier specs, custom sizing (12x18" to 26x34"), and food-safety certifications. Only 2 content images — consider adding a nylon film shot. H4 "High Heat Oven Bags" should be an H2 for cleaner hierarchy.',
      images: [
        ['Hero (oven-bags-for-food.jpg)', 'food cleanroom packaging - oven bags for food', 'Food-grade cleanroom oven bags for high-heat cooking', '52', 'Tightens the doubled phrasing into one clean keyword line.'],
        ['Oven bags (food-cleanroom-packaging-bags.jpg)', 'High heat oven bags', 'High-heat nylon oven bags rated to 375°F, oven and microwave safe', '65', 'Adds the temp spec + use cases buyers search.']
      ],
      schemaLabel: 'Recommended Structured Data (Service schema)',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Food Cleanroom Packaging",
  "serviceType": "Food-grade cleanroom packaging manufacturing",
  "description": "Food-grade cleanroom packaging including nylon bags and high-heat oven bags rated to 375°F, BPA-free and FDA-approved for oven, microwave, and freezer use.",
  "provider": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "url": "https://www.cleanroomfilm.com/"
  },
  "areaServed": "US",
  "url": "https://www.cleanroomfilm.com/markets/food-cleanroom-packaging"
}
</script>`
    },

    // ---------------- LEARNING CENTER (blog) batch — audited 2026-07-17 ----------------
    // Dates below are HubSpot migration timestamps unless noted; replace datePublished with
    // original publish dates where known (CleanTronics displayed "Mar 26, 2024" on the live page).
    blogDone('Blog — Adds High-Speed Converting Line', 'cleanroom-film-bags-adds-high-speed-converting-line-to-serve-increasing-demand-for-cleanroom-bags',
      'NewsArticle', '2026-04-22',
      'cleanroom bag manufacturing capacity',
      'high-speed converting, cleanroom bag production, lead times',
      'Adds High-Speed Converting Line for Cleanroom Bags | CFB', 55,
      'CFB adds high-speed converting line to boost cleanroom bag production, delivering faster lead times, strong seals, and reliable performance.', 140,
      'Current meta is good — keep. Current title is the full editorial headline (~92 chars, truncates); trimmed version keeps news value under 60. Add NewsArticle schema + author byline. Internal-link to /products/cleanroom-bags.'),

    blogDone('Blog — Case Study: Aclar® Film at Boeing', 'case-study-the-use-of-aclar-packaging-film-for-protecting-parts-at-boeing',
      'Article', '2026-04-22',
      'Aclar film aerospace case study',
      'moisture barrier packaging, corrosion protection, Boeing, Aclar®',
      'Case Study: Aclar® Film Protecting Parts at Boeing | CFB', 56,
      'Aclar® film case study: Boeing improves protection, reduces corrosion, and boosts efficiency with advanced moisture barrier packaging.', 134,
      'Flagship E-E-A-T asset (names Boeing). Keep meta. Add Article schema. Cross-link to /materials/aclar and /markets/aerospace — currently orphaned from both.'),

    blogDone('Blog — Case Study: FOUP Packaging (HDPE)', 'case-study-cleanroom-film-bags-foup-packaging-using-high-density-resin',
      'Article', '2026-04-22',
      'FOUP packaging case study',
      'high-density resin, semiconductor packaging, moisture protection, FOUP',
      'Case Study: FOUP Packaging Using High-Density Resin | CFB', 57,
      'Keep meta. Add Article schema. Cross-link to /markets/semiconductor (the semiconductor page audit already calls for this link).'),

    blogDone('Blog — Announces CFB CleanTronics™', 'cfb-cleantronics-launch',
      'NewsArticle', '2024-03-26',
      'CFB CleanTronics semiconductor packaging',
      'CleanTronics, low outgassing, ESD, microelectronics',
      'CFB Announces CleanTronics™ Advanced Packaging | CFB', 52,
      'CFB launches CleanTronics advanced packaging for semiconductors, offering low outgassing, ESD protection, and ultra-clean solutions.', 132,
      'ORIGINAL published date confirmed as 2024-03-26 (visible on live page) — use that in schema, NOT the Apr 2026 migration date. Announces the CleanTronics™ brand — link to /markets/electronic where the brand is featured. Keep meta. URL SHORTENED 2026-08-11 per Ubersuggest flag: was /learning-center/cleanroom-film-bags-announces-cfb-cleantronics-advanced-packaging-for-semiconductors-and-microelectronics (105 chars) → now /learning-center/cfb-cleantronics-launch. HubSpot 301 redirect confirmed in place from the old URL.'),

    blogDone('Blog — Adds Nylon Film & Bag Capacity', 'cleanroom-film-bags-adds-capacity-to-serve-increasing-demand-for-cleanroom-nylon-films-and-bags',
      'NewsArticle', '2026-04-22',
      'cleanroom nylon film capacity',
      'nylon films, cleanroom bags, medical, aerospace, lead times',
      'Adds Cleanroom Nylon Film & Bag Capacity | CFB', 47,
      'CFB expands nylon cleanroom packaging capacity, delivering high-purity, durable films with fast lead times for medical, aerospace, and tech.', 140,
      'Keep meta. Trim title from full headline. Add NewsArticle schema. Link to /materials/nylon.'),

    blogDone('Blog — Expands Sterilizable Packaging', 'cleanroom-film-bags-expands-offering-of-customized-sterilizable-packaging',
      'NewsArticle', '2026-04-22',
      'customized sterilizable packaging',
      'Tyvek® packaging, medical device packaging, barrier protection',
      'Expands Customized Sterilizable Tyvek® Packaging | CFB', 54,
      'CFB expands Tyvek® sterilizable packaging, offering durable, customizable solutions for medical devices with superior barrier protection.', 137,
      'Keep meta. Add NewsArticle schema. Link to /materials/tyvek and /markets/medical.'),

    blogDone('Blog — How To Choose Medical Device Packaging', 'how-to-choose-the-right-medical-device-packaging',
      'Article', '2026-04-22',
      'how to choose medical device packaging',
      'cleanroom packaging options, contamination, sterility, medical device',
      'How To Choose the Right Medical Device Packaging | CFB', 54,
      'Only informational/guide article in the set — highest evergreen SEO value (targets a real informational query). Keep meta. Consider HowTo or FAQ schema in addition to Article. Link to /markets/medical and /standards. Strong candidate for expansion into a pillar guide.'),

    blogDone('Blog — Strong Enough for Outer Space', 'cfb-packaging-strong-enough-for-outer-space',
      'Article', '2026-04-22',
      'aerospace cryogenic packaging',
      'CFB7000 HydroBlock, Aclar® PCTFE, moisture barrier, extreme conditions',
      'CFB Packaging Strong Enough for Outer Space | CFB', 49,
      'Keep meta. Add Article schema. Already featured on /markets/aerospace — ensure the reverse link exists too. The -455°F / CFB7000 HydroBlock® detail is a standout hook.'),

    blogDone('Blog — Ensures Safe Organ Transplant', 'cfb-packaging-ensures-safe-organ-transplant',
      'Article', '2026-04-21',
      'medical transport packaging',
      'organ transport, HDPE film, non-scratch, contamination control',
      'CFB Packaging Ensures Safe Organ Transplant | CFB', 49,
      'Powerful E-E-A-T story. Keep meta. Add Article schema. Link to /markets/medical and /markets/healthcare.'),

    blogDone('Blog — Opens State-of-the-Art Plant', 'cleanroom-film-bags-opens-state-of-the-art-plant-in-placentia',
      'NewsArticle', '2026-04-22',
      'Placentia cleanroom facility',
      'new plant, cleanroom capacity, sustainability, nationwide delivery',
      'CFB Opens State-of-the-Art Plant in Placentia | CFB', 51,
      'Keep meta. Add NewsArticle schema. Local-SEO value (names Placentia) — link to /contact-us and /our-story; supports the LocalBusiness entity.'),

    {
      name: 'Markets (Hub)',
      status: 'IMPLEMENTED in HubSpot 2026-07-22 (title, meta, 7 image alts, CollectionPage schema)',
      liveUrl: 'https://www.cleanroomfilm.com/markets',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-22',
      primaryKw: 'cleanroom packaging industries',
      secondaryKw: 'medical, pharmaceutical, semiconductor, aerospace, electronics, food, healthcare cleanroom packaging',
      title: { text: 'Industries We Serve: Medical, Semiconductor, Aerospace | CFB', chars: 62 },
      meta: { text: 'Cleanroom packaging for medical, pharmaceutical, semiconductor, aerospace, electronics & food industries. ISO-certified, USA-made film and bags.', chars: 145 },
      notes: 'Replaced the old title ("Markets | Poly Bag & Blown Film Manufacturing | Cleanroom Film & Bags") and a generic boilerplate meta that had appeared on the page since the original audit (which had found it missing entirely). Added CollectionPage schema listing all 7 market pages as hasPart, reinforcing the internal-link structure to the already-optimized market pages. All 7 tile image alts polished from generic-but-correct to brand-tied, de-duplicated phrasing.',
      images: [
        ['Medical tile (medical-cleanroom-packaging.jpg)', 'medical cleanroom packaging', 'Medical cleanroom packaging solutions by CFB', '46', 'Ties to brand; de-duplicates near-identical alt text across the 7 tiles.'],
        ['Healthcare tile (healthcare-cleanroom-packaging.jpg)', 'healthcare cleanroom packaging', 'Healthcare cleanroom packaging solutions by CFB', '48', 'Same rationale.'],
        ['Pharmaceutical tile (pharmaceutical-cleanroom-packaging.jpg)', 'pharmaceutical cleanroom packaging', 'Pharmaceutical cleanroom packaging solutions by CFB', '52', 'Same rationale.'],
        ['Electronics tile (electronics-cleanroom-packaging.jpg)', 'electronics cleanroom packaging', 'Electronic cleanroom packaging and ESD solutions by CFB', '57', 'Adds ESD to match the linked page\'s focus.'],
        ['Semiconductor tile (semi-conductor-cleanroom-packaging.jpg)', 'semiconductor cleanroom packaging', 'Semiconductor cleanroom packaging solutions by CFB', '51', 'Same rationale.'],
        ['Aerospace tile (aerospace-cleanroom-packaging.jpg)', 'aerospace cleanroom packaging', 'Aerospace cleanroom packaging solutions by CFB', '47', 'Same rationale.'],
        ['Food tile (food-cleanroom-packaging.jpg)', 'food cleanroom nylon packaging', 'Food-grade cleanroom packaging solutions by CFB', '48', 'Tightened from "nylon packaging" to the page-level keyword.']
      ],
      schemaLabel: 'Deployed Structured Data (CollectionPage schema) — LIVE as of 2026-07-22',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Industries We Serve",
  "url": "https://www.cleanroomfilm.com/markets",
  "description": "Cleanroom packaging for medical, pharmaceutical, semiconductor, aerospace, electronics & food industries. ISO-certified, USA-made film and bags.",
  "hasPart": [
    { "@type": "WebPage", "name": "Medical", "url": "https://www.cleanroomfilm.com/markets/medical-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Semiconductor", "url": "https://www.cleanroomfilm.com/markets/semiconductor-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Aerospace", "url": "https://www.cleanroomfilm.com/markets/aerospace-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Pharmaceutical", "url": "https://www.cleanroomfilm.com/markets/pharmaceutical-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Electronic", "url": "https://www.cleanroomfilm.com/markets/electronic-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Food", "url": "https://www.cleanroomfilm.com/markets/food-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Healthcare", "url": "https://www.cleanroomfilm.com/markets/healthcare-cleanroom-packaging" }
  ]
}
</script>`
    },

    {
      name: 'Our Story',
      status: 'IMPLEMENTED in HubSpot 2026-07-22 (title, meta, image alt, AboutPage schema, 3 internal links)',
      liveUrl: 'https://www.cleanroomfilm.com/our-story',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-07-22',
      primaryKw: 'CFB company history',
      secondaryKw: '35 years cleanroom packaging, ISO 14644 Class 5, CLEANTUFF, ULO, Placentia facility',
      title: { text: 'Our Story: 35+ Years of Cleanroom Packaging Excellence | CFB', chars: 61 },
      meta: { text: '35+ years of cleanroom packaging excellence. Class 100 vertically integrated facility in Placentia, CA. Exclusive manufacturer of CLEANTUFF® and ULO films.', chars: 158 },
      notes: 'Replaced the original mismatched title/meta ("Sterile Packaging: Healthcare, Pharmaceutical, & Food Bags | CFB") which had been live since the first audit. Added AboutPage schema with mainEntity pointing to the Organization. Added 3 internal links promoting the buried CLEANTUFF®/ULO exclusive-material pages, per the audit\'s top strategic recommendation.',
      images: [
        ['Headquarters photo (cleanroom-film-bags-headquarters.jpg)', 'cleanroom-film-bags-headquarters', 'Cleanroom Film & Bags corporate headquarters in Placentia, California', '71', 'Filename-dump alt replaced with location context — supports local-SEO signal the audit flagged as weak.']
      ],
      schemaLabel: 'Deployed Structured Data (AboutPage schema) — LIVE as of 2026-07-22',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "AboutPage",
  "name": "Our Story",
  "url": "https://www.cleanroomfilm.com/our-story",
  "description": "35+ years of cleanroom packaging excellence. Class 100 vertically integrated facility in Placentia, CA. Exclusive manufacturer of CLEANTUFF® and ULO films.",
  "mainEntity": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "foundingDate": "1990",
    "url": "https://www.cleanroomfilm.com/"
  }
}
</script>`
    },

    {
      name: 'Anti-Static Nylon Cleanroom Packaging',
      status: 'IMPLEMENTED in HubSpot 2026-08-11 (title, meta, H1, 6 image alts, Product schema, 3 internal links) — VERIFIED LIVE',
      liveUrl: 'https://www.cleanroomfilm.com/materials/anti-static-nylon-cleanroom-packaging',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-08-11',
      primaryKw: 'anti-static bags',
      secondaryKw: 'anti-static nylon, ESD protection, CFB5100, static shielding bags, cleanroom nylon film',
      title: { text: 'Anti-Static Bags: Nylon Cleanroom Packaging | CFB', chars: 51 },
      meta: { text: 'Anti-static bags in cleanroom-grade nylon — ESD protection, color-coded identification, and durability for electronics, pharma & aerospace. Get a quote.', chars: 155 },
      notes: 'PRIORITY 1 from the July 28 progress report: "anti-static bags" is the single largest untapped keyword found in rank tracking — 4,400 searches/month, not yet ranking. Page is already substantial (~1,200–1,400 words, real CFB5100 spec table: tensile strength, surface resistivity, cleanliness level) — no content rewrite needed. The gap is purely keyword emphasis: the page uses "anti-static nylon" throughout but never leads with the bare high-volume phrase "anti-static bags." Title, meta, and H1 all reordered to lead with the exact-match term. H1 recommended change: "Anti-Static Bags: Nylon Cleanroom Packaging" (was "Anti-Static Nylon Bags & Cleanroom Packaging").',
      images: [
        ['CFB5100 sample (CFB-5100-orange-anti-static.png)', 'CFB-5100-orange-anti-static', 'CFB5100 orange anti-static nylon bags for ESD protection', '59', 'Filename-dump alt replaced with product name + function.'],
        ['Medical section (medical-device-packaging.png)', 'medical device packaging', 'Anti-static bags for medical device packaging', '47', 'Ties generic alt to page-specific primary keyword.'],
        ['Aerospace section (aerospace-packaging.png)', 'aerospace-packaging', 'Anti-static bags for aerospace component packaging', '52', 'Same rationale.'],
        ['Semiconductor section (semiconductor-packaging.png)', 'semiconductor packaging', 'Anti-static bags for semiconductor and electronics packaging', '62', 'Same rationale.'],
        ['Cleanroom facilities section (cleanroom-facilities-packaging.png)', 'cleanroom facilities packaging', 'Anti-static nylon bags used in cleanroom facility operations', '63', 'Same rationale.'],
        ['Orange film sample (orange-anti-static-film.jpg — reused from market pages)', 'orange anti-static film', 'Orange anti-static nylon film for cleanroom packaging', '55', 'Minor tightening for consistency with CFB5100 branding.']
      ],
      schemaLabel: 'Recommended Structured Data (Product schema)',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "CFB5100 Anti-Static Nylon Bags",
  "description": "Anti-static bags in cleanroom-grade nylon with ESD protection, color-coded identification, and durability for electronics, pharmaceutical, and aerospace applications.",
  "brand": { "@type": "Brand", "name": "Cleanroom Film & Bags" },
  "material": "Nylon",
  "url": "https://www.cleanroomfilm.com/materials/anti-static-nylon-cleanroom-packaging"
}
</script>`
    },

    {
      name: 'Learning Center (Hub)',
      status: 'IMPLEMENTED in HubSpot 2026-08-11 (blog header, page title, meta, Blog schema)',
      liveUrl: 'https://www.cleanroomfilm.com/learning-center',
      editorUrl: 'https://app.hubspot.com/blog/24024882/settings/ (add blog ID from HubSpot)',
      audited: '2026-08-11',
      primaryKw: 'cleanroom packaging news and case studies',
      secondaryKw: 'cleanroom packaging blog, aerospace, semiconductor, medical packaging insights',
      title: { text: 'Learning Center: Cleanroom Packaging Case Studies & News | CFB', chars: 64 },
      meta: { text: 'Explore CFB\'s cleanroom packaging case studies, product announcements, and industry insights — covering aerospace, semiconductor, and medical applications.', chars: 158 },
      notes: 'Found the weakest SEO on the entire site: page title was the bare company name ("Cleanroom Film & Bags", no keyword targeting) and meta description was the two-word placeholder "Learning Center" — likely never replaced since blog setup. Fixed both, plus the on-page Blog header field (visible H1-equivalent, was also "Cleanroom Film & Bags") to "Learning Center: Cleanroom Packaging News & Case Studies". Added Blog schema referencing the publisher Organization. This hub sits in front of all 10 optimized Learning Center posts, so its own optimization was overdue.',
      images: [],
      schemaLabel: 'Deployed Structured Data (Blog schema) — LIVE as of 2026-08-11',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Blog",
  "name": "Learning Center",
  "url": "https://www.cleanroomfilm.com/learning-center",
  "description": "Explore CFB's cleanroom packaging case studies, product announcements, and industry insights — covering aerospace, semiconductor, and medical applications.",
  "publisher": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "logo": { "@type": "ImageObject", "url": "https://www.cleanroomfilm.com/hubfs/CFB%20Logo.png" }
  }
}
</script>`
    },

    {
      name: 'Materials (Hub)',
      status: 'IMPLEMENTED in HubSpot 2026-08-11 (title, meta, CollectionPage schema)',
      liveUrl: 'https://www.cleanroomfilm.com/materials',
      editorUrl: 'https://app.hubspot.com/pages/24024882/editor/ (add page ID from HubSpot)',
      audited: '2026-08-11',
      primaryKw: 'cleanroom packaging materials',
      secondaryKw: 'nylon, Tyvek, Aclar, CLEANTUFF, ULO, ESD, static shielding, barrier, polyethylene',
      title: { text: 'Cleanroom Packaging Materials: Nylon, Tyvek®, Aclar® & More | CFB', chars: 67 },
      meta: { text: 'Cleanroom packaging materials: Nylon, Tyvek®, Aclar®, CLEANTUFF®, ULO, ESD & more — 11 material options for medical, aerospace & semiconductor.', chars: 146 },
      notes: 'Flagged by Ubersuggest as a too-long title (72 chars, confirmed live). Same pattern as the original /markets hub finding: title targets only ESD ("ESD Packaging Materials: Anti-Static Polyethylene Bags & Film | LDPE") while the page covers 11 materials — a mismatch with its own H1 ("Cleanroom Packaging Materials"), which is already correctly broad and needs no change. Meta description had the same ESD-only narrowing. Added CollectionPage schema listing all 11 material pages, matching the pattern used on the Markets hub. No content images found on this page — nothing to alt-text.',
      images: [],
      schemaLabel: 'Recommended Structured Data (CollectionPage schema)',
      schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Cleanroom Packaging Materials",
  "url": "https://www.cleanroomfilm.com/materials",
  "description": "Cleanroom packaging materials: Nylon, Tyvek®, Aclar®, CLEANTUFF®, ULO, ESD & more — 11 material options for medical, aerospace & semiconductor.",
  "hasPart": [
    { "@type": "WebPage", "name": "CLEANTUFF®", "url": "https://www.cleanroomfilm.com/materials/cleantuff-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Extreme Low Outgassing (ULO)", "url": "https://www.cleanroomfilm.com/materials/extreme-low-outgassing-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Aclar®", "url": "https://www.cleanroomfilm.com/materials/aclar-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Tyvek®", "url": "https://www.cleanroomfilm.com/materials/tyvek-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Anti-Static Nylon", "url": "https://www.cleanroomfilm.com/materials/anti-static-nylon-cleanroom-packaging" },
    { "@type": "WebPage", "name": "ESD", "url": "https://www.cleanroomfilm.com/materials/esd-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Static Shielding", "url": "https://www.cleanroomfilm.com/materials/static-shielding-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Barrier", "url": "https://www.cleanroomfilm.com/materials/barrier-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Nylon", "url": "https://www.cleanroomfilm.com/materials/nylon" },
    { "@type": "WebPage", "name": "Nylon/Polyethylene", "url": "https://www.cleanroomfilm.com/materials/nylon-polyethylene-cleanroom-packaging" },
    { "@type": "WebPage", "name": "Polyethylene", "url": "https://www.cleanroomfilm.com/materials/polyethylene-cleanroom-packaging" }
  ]
}
</script>`
    }
  ]
};

// blogDone(): same as blog() but marked implemented in HubSpot on 2026-07-17
function blogDone(...args) {
  const p = blog(...args);
  p.status = 'IMPLEMENTED in HubSpot 2026-07-17 (title, meta, featured-image alt, schema)';
  return p;
}

// ---- blog(): compact factory for Learning Center article entries ----
function blog(name, slug, schemaType, datePublished, primaryKw, secondaryKw, titleText, titleChars, metaText, metaChars, notes) {
  const url = 'https://www.cleanroomfilm.com/learning-center/' + slug;
  return {
    name,
    status: 'AUDITED 2026-07-17 — pending implementation',
    liveUrl: url,
    editorUrl: 'https://app.hubspot.com/blog/24024882/editor/ (add post ID from HubSpot)',
    audited: '2026-07-17',
    author: 'Jonny Grigg',
    datePublished,
    primaryKw,
    secondaryKw,
    title: { text: titleText, chars: titleChars },
    meta: { text: metaText, chars: metaChars },
    notes,
    images: [],
    featuredImageNote: 'Set descriptive alt on the featured image using the primary keyword (verify image content on the live post before finalizing).',
    schemaLabel: `Recommended Structured Data (${schemaType} schema)`,
    schema: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "${schemaType}",
  "headline": ${JSON.stringify(titleText.replace(/ \| CFB$/, ''))},
  "description": ${JSON.stringify(metaText)},
  "datePublished": "${datePublished}",
  "author": { "@type": "Person", "name": "Jonny Grigg" },
  "publisher": {
    "@type": "Organization",
    "name": "Cleanroom Film & Bags",
    "logo": { "@type": "ImageObject", "url": "https://www.cleanroomfilm.com/hubfs/CFB%20Logo.png" }
  },
  "mainEntityOfPage": "${url}"
}
</script>`
  };
}
