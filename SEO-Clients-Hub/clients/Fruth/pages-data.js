// Fruth On-Page SEO Audit Running Log — data file
// Reconciles the hand-written audit (FCP-SEO-AUDIT-2026-08-31.md, 63/100) against the
// automated crawler dashboard (Fruth-Custom-Packaging-SEO-Audit-2026-08-31.html, 91/100)
// and the content-creation/CE_Fruth_*.docx drafts. Add new entries as pages are implemented,
// then run gen-running-log.js.
// Status convention: 'AUDITED YYYY-MM-DD — pending implementation' -> 'VERIFIED LIVE YYYY-MM-DD (...)'

module.exports = {
  "client": "Fruth Custom Packaging",
  "docTitle": "On-Page SEO Audit — Running Log",
  "docSubtitle": "Reconciled findings: hand-written audit vs. crawler dashboard vs. content-creation drafts",
  "lastUpdated": "2026-09-25",
  "totalPages": 48,
  "guidelines": [
    "The crawler dashboard (Fruth-Custom-Packaging-SEO-Audit-*.html) parses live HTML and is the source of truth for title/meta/schema/alt presence — the hand-written .md audit was built via WebFetch, which does not reliably see <head> meta tags, so its \"missing meta description\" findings for the 6 main pages have been corrected here.",
    "Meta descriptions: target 140-160 characters. Titles: target 50-60 characters.",
    "Body-copy expansion drafts live in content-creation/CE_Fruth_*.docx; paste-ready text is consolidated in FRUTH-IMPLEMENTATION-PACKET.md.",
    "No HubSpot portal access confirmed yet for Fruth as of 2026-09-11 — verify editor URLs once access is set up."
  ],
  "globalElements": [],
  "pages": [
    {
      "name": "Homepage",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Fruth Custom Packaging | Plastic Bags & Barrier Films",
        "chars": 53
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): Multiple H1 tags (4) — Having more than one H1 dilutes the page focus signal for search engines.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~197 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"Organization\",\n\"name\": \"Fruth Custom Packaging\",\n\"url\": \"https://www.fruth.com\",\n\"logo\": \"https://www.fruth.com/logo.png\",\n\"address\": {\n\"@type\": \"PostalAddress\",\n\"streetAddress\": \"701 S. Richfield Rd.\",\n\"addressLocality\": \"Placentia\",\n\"addressRegion\": \"CA\",\n\"postalCode\": \"92870\",\n\"addressCountry\": \"US\"\n},\n\"telephone\": \"+17149939955\",\n\"email\": \"sales@fruth.com\",\n\"description\": \"U.S.-based custom packaging manufacturer producing flexible bags and barrier films for industrial, food, medical, electronics, and cleanroom applications. ISO 9001:2015 certified, FDA and USDA compliant. Made in the USA.\",\n\"hasOfferCatalog\": {\n\"@type\": \"OfferCatalog\",\n\"name\": \"Custom Packaging Products\",\n\"itemListElement\": [\n{ \"@type\": \"Offer\", \"itemOffered\": { \"@type\": \"Product\", \"name\": \"Custom Bags\" } },\n{ \"@type\": \"Offer\", \"itemOffered\": { \"@type\": \"Product\", \"name\": \"Barrier Films\" } },\n{ \"@type\": \"Offer\", \"itemOffered\": { \"@type\": \"Product\", \"name\": \"Poly Films\" } }\n]\n}\n}\n</script>"
    },
    {
      "name": "Our Story",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/our-story",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Our Story | 100% American Made Custom Packaging | Fruth",
        "chars": 55
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): VideoObject, FAQPage",
      "schema": null
    },
    {
      "name": "Capabilities",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/capabilities",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Custom Packaging Capabilities | Vertically Integrated | Fruth",
        "chars": 61
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Industries",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/industries",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Industries We Serve | Custom Packaging Solutions | Fruth",
        "chars": 56
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Fruth 360",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/fruth-360",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Fruth 360 | Vertically Integrated Packaging Manufacturing",
        "chars": 57
      },
      "meta": {
        "text": "Fruth 360 is our vertically integrated production model — extrusion, conversion, printing, and tooling under one roof for faster lead times.",
        "chars": 140
      },
      "notes": "Crawler findings (2026-08-31): Meta description long (171 chars, aim for 140-160) — Long meta descriptions get truncated in search results.. Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): VideoObject, FAQPage",
      "schema": null
    },
    {
      "name": "Anti-Static Bags",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/products/films/anti-static-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Custom Anti-Static Bags | ESD Packaging | Fruth",
        "chars": 47
      },
      "meta": {
        "text": "Custom anti-static and static shielding bags for PCBs, semiconductors, and ESD-sensitive electronics. ANSI/ESD compliant. Get a custom quote.",
        "chars": 141
      },
      "notes": "Crawler findings (2026-08-31): Meta description long (171 chars, aim for 140-160) — Long meta descriptions get truncated in search results.. Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Cushion Packaging Barrier Film",
      "status": "VERIFIED LIVE 2026-09-21 (body copy + FAQ schema — drafted, billed, and confirmed live by this project)",
      "liveUrl": "https://www.fruth.com/products/barrier-films/cushion-packaging-barrier-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Cushion Packaging Barrier Film | Fruth Custom Packaging",
        "chars": 55
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (118 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): None",
      "schema": null
    },
    {
      "name": "EMI Static Shielding Barrier Film",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/products/barrier-films/emi-static-shielding-barrier-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "EMI Static Shielding Barrier Film | ESD Packaging | Fruth",
        "chars": 57
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "ESD Packaging",
      "status": "BLOCKED — URL 404s on live site; page does not exist. Draft cannot be implemented until this is resolved with the client/HubSpot.",
      "liveUrl": "https://www.fruth.com/products/bags/esd-packaging",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": null,
      "meta": null,
      "notes": "Direct site check 2026-09-14: this URL returns a 404. The draft was written for a page that was never published, or was published at a different URL. Do not implement until the correct URL is confirmed.",
      "schemaLabel": null,
      "schema": null
    },
    {
      "name": "Flame Retardant PE Film",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/products/films/flame-retardant-polyethylene-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Flame Retardant Polyethylene Film | NFPA 701 Compliant | Fruth",
        "chars": 62
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Foam Sheets and Rolls",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/barrier-films/foam-sheets-rolls",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Foam Sheets & Rolls | Protective Packaging Foam Materials",
        "chars": 57
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~454 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What are polyethylene foam sheets and rolls used for?\",\n\"acceptedAnswer\": {\n\"@type\": \"Answer\",\n\"text\": \"Polyethylene foam sheets and rolls are used for interleaving, wrapping, cushioning, void fill, and surface protection in industrial, medical, and commercial packaging. They are non-abrasive, anti-slip, water resistant, and lightweight — protecting products from scratching, shifting, and impact damage during storage and transit.\"\n}\n},\n{\n\"@type\": \"Question\",\n\"name\": \"What is anti-static foam used for?\",\n\"acceptedAnswer\": {\n\"@type\": \"Answer\",\n\"text\": \"Anti-static foam protects ESD-sensitive electronics components from electrostatic discharge during storage and shipping. Common applications include circuit boards, motherboards, HDDs and SSDs, RF modules, optical drives, sensors, and laser diodes — any component where static discharge could cause damage or failure.\"\n}\n},\n{\n\"@type\": \"Question\",\n\"name\": \"What thicknesses and sizes does Fruth foam sheeting come in?\",\n\"acceptedAnswer\": {\n\"@type\": \"Answer\",\n\"text\": \"Fruth polyethylene foam sheets and rolls are available in standard thicknesses of 1/16\" and 1/8\", with custom sizes available. Anti-static variants are also available. Contact us with your specifications for a custom quote.\"\n}\n}\n]\n}\n</script>"
    },
    {
      "name": "Lip and Tape Bags",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/bags/lip-and-tape-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Lip & Tape Bags | Resealable Protective Packaging Bags",
        "chars": 54
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~294 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What is the difference between permanent and resealable lip and tape bags?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Permanent tape bags form a tamper-evident seal — once closed the bag must be torn or damaged to open, making tampering visible. Resealable bags use a repositionable adhesive that can be opened and resealed multiple times without losing adhesion.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Are lip and tape bags tamper evident?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Permanent closure versions are tamper evident. Once sealed the adhesive will not release cleanly so any attempt to open the bag leaves visible evidence of tampering.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Can lip and tape bags be custom printed?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth produces custom printed lip and tape bags for retail branding, product identification, and handling instructions. Any size, film type, or print configuration is available.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "MIL-PRF-131K Barrier Film",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/barrier-films/mil-prf-131k-barrier-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "MIL-PRF-131K Barrier Film | Military-Grade Packaging | Fruth",
        "chars": 60
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): Low word count (103 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~295 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What does MIL-PRF-131K Class 1 mean?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"MIL-PRF-131K is a U.S. Department of Defense performance specification for barrier materials used in military packaging. Class 1 designates the highest barrier rating, providing certified protection against moisture vapor, oxygen, and light for long-term preservation of military and industrial equipment.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Why does MIL-PRF-131K film contain no amines or amides?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Amines, amides, and N-Octanoic acid can react with or damage polycarbonate components commonly found in electronics and optical equipment. Fruth MIL-PRF-131K film is formulated without these compounds, making it safe for direct contact with polycarbonate parts.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Can Fruth produce MIL-PRF-131K film in custom sizes?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth produces MIL-PRF-131K barrier film in custom widths, lengths, and configurations. Contact us with your specifications and application details for a quote.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Multi-Pocket Bags",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/bags/multi-pocket-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Multi Pocket Bags | Custom Compartment Packaging Bags",
        "chars": 53
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (83 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~282 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What are multi-pocket bags used for?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Multi-pocket bags are used to organize and separate multiple items within a single bag. Common applications include prescription and pharmacy packaging, medical supply kits, industrial parts kits, and retail promotional packaging.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Can multi-pocket bags be custom printed?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth produces multi-pocket bags in virtually any color and print configuration. Custom sizing and pocket layout are also available to match your specific application.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Are Fruth multi-pocket bags FDA compliant?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth multi-pocket bags meet FDA and USDA food and safety specifications and are manufactured under ISO 9001:2015 certified quality controls.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Nuclear Green PE Film",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/products/films/nuclear-green-polyethylene-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Nuclear Green Polyethylene Film | MIL-DTL-24466 Spec | Fruth",
        "chars": 60
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Nylon Film",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/films/nylon-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Nylon Film | Fruth Custom Packaging",
        "chars": 35
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (145 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~240 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What is nylon film used for in packaging?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Nylon film is used in packaging applications that require toughness, cleanliness, and abrasion resistance. Common uses include medical and laboratory device packaging, electronics and semiconductor protection, cleanroom packaging, and chemical and solvent containment.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"How does nylon film differ from standard polyethylene?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Nylon provides significantly higher puncture resistance, abrasion resistance, and barrier performance compared to standard polyethylene. It is preferred when product sharpness, particulate sensitivity, or chemical barrier requirements exceed what standard poly can deliver.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Is Fruth nylon film available in custom sizes?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth produces nylon packaging in both standard and custom configurations. Contact us with your dimensions and application requirements for a quote.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Polypropylene Film",
      "status": "VERIFIED LIVE 2026-09-14 (confirmed via direct site check — NOT in the original implementation tracker, implementation date unknown)",
      "liveUrl": "https://www.fruth.com/products/films/polypropylene-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Polypropylene Film | Custom PP Film for Autoclave & Medical",
        "chars": 59
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Scrim Foil Barrier Film",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/barrier-films/scrim-foil-barrier-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Scrim Foil Barrier Film | Reinforced Moisture Barrier Packaging",
        "chars": 63
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (90 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~282 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What is scrim foil barrier film used for?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Scrim foil barrier film is a sealing laminate used on foil-faced fiberglass ductwork and sheet metal ducts in HVAC and mechanical insulation applications. It is used to seal duct seams, repair damaged insulation, and provide moisture and vapor barrier protection.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Does Fruth scrim foil require special tools to install?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"No. Fruth scrim foil is designed for straightforward application to fibrous and sheet metal ductwork without special tools or installation methods.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Is Fruth scrim foil resistant to moisture and mold?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth scrim foil barrier film is highly resistant to moisture, mold, and vapors, making it suitable for HVAC and mechanical insulation environments where long-term performance is required.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Side Seal Bags",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/bags/side-seal-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Side Seal Bags | Durable Custom Plastic Packaging Bags",
        "chars": 54
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (148 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~280 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What are side seal bags?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Side seal bags are flexible pouches sealed on three sides with an open top or bottom for filling. The three-sided seal provides strong containment for food, liquid, powder, and retail product applications.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Can side seal bags include hang holes or zippers?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth side seal bags are available with hang holes for retail display and zipper closures for resealable applications.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Are Fruth side seal bags FDA compliant?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth side seal bags comply with FDA and USDA food safety specifications and are suitable for direct food contact applications.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Square Bottom Bags",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/bags/square-bottom-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Square Bottom Bags | Fruth Custom Packaging",
        "chars": 43
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~290 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What is a square bottom bag?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"A square bottom bag combines the expandable sides of a gusseted bag with a flat stable base that allows it to stand upright, giving maximum fill volume, shelf stability, and a large flat panel for branding and graphics.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Can square bottom bags include degassing valves?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth square bottom bags support degassing valve integration, making them well suited for freshly roasted coffee and other products that off-gas after sealing.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"What lamination options are available for square bottom bags?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Fruth offers foil, metallized, and clear poly lamination options for square bottom bags, providing varying levels of barrier protection, opacity, and visual finish.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Tamper Evident Bags",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/bags/tamper-evident-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Tamper Evident Bags | Fruth Custom Packaging",
        "chars": 44
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (86 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~292 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"How do tamper evident bags show signs of tampering?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Fruth tamper evident bags are engineered to resist side-breach and reseal attempts. If the bag is opened or an entry is attempted, the bag construction makes tampering visible — providing verifiable evidence of unauthorized access.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"What industries use tamper evident bags?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Common users include pharmaceutical distributors, healthcare providers, financial institutions, legal and document management firms, and retailers who require chain-of-custody verification or loss prevention packaging.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Can tamper evident bags be custom sized and printed?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth produces tamper evident bags in custom sizes with print options for barcodes, sequential numbering, branding, and security features. Contact us with your requirements for a quote.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Vacuum Seal Bags",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/bags/vacuum-seal-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Vacuum Seal Bags | Fruth Custom Packaging",
        "chars": 41
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (95 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~609 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": null,
      "schema": null
    },
    {
      "name": "Wicketed Bags",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/bags/wicketed-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Wicketed Bags | Fruth Custom Packaging",
        "chars": 38
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (107 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~304 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What are wicketed bags?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Wicketed bags are poly bags mounted on a metal wire wicket that holds them in a stacked dispensable format. This allows bags to be peeled off one at a time during manual or automated filling significantly increasing packaging line speed and efficiency.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Are wicketed bags compatible with automated filling equipment?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth wicketed bags are designed for use with automatic and semi-automatic filling equipment. Wicket spacing can be configured to match your specific machinery requirements.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Are Fruth wicketed bags FDA compliant?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth wicketed bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications including produce, bakery, and processed food packaging.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Zipper Bags",
      "status": "VERIFIED PENDING 2026-09-21 (direct site check confirms AFTER content is NOT live — safe to implement)",
      "liveUrl": "https://www.fruth.com/products/bags/zipper-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Zipper Bags | Fruth Custom Packaging",
        "chars": 36
      },
      "meta": null,
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Low word count (129 words) — Pages with very little content are harder for Google to rank.. Direct site check 2026-09-14 confirms this AFTER content is NOT live. Content-creation draft adds ~260 words of body copy — see FRUTH-IMPLEMENTATION-PACKET.md for full paste-ready text.",
      "schemaLabel": "Recommended schema (from content-creation draft — not yet on live page)",
      "schema": "<script type=\"application/ld+json\">\n{\n\"@context\": \"https://schema.org\",\n\"@type\": \"FAQPage\",\n\"mainEntity\": [\n{\n\"@type\": \"Question\",\n\"name\": \"What materials are Fruth zipper bags made from?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Fruth zipper bags are constructed from high-density polyethylene (HDPE) and are recyclable. LDPE and poly blend options are also available depending on application requirements.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Can zipper bags be custom printed?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth offers custom print options for branding, product identification, and handling instructions in any color or print configuration.\" }\n},\n{\n\"@type\": \"Question\",\n\"name\": \"Are Fruth zipper bags FDA compliant?\",\n\"acceptedAnswer\": { \"@type\": \"Answer\",\n\"text\": \"Yes. Fruth zipper bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications.\" }\n}\n]\n}\n</script>"
    },
    {
      "name": "Bags Hub",
      "status": "VERIFIED LIVE 2026-07-07 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Custom Plastic Bags for Medical, Food & Industrial Use | Fruth",
        "chars": 62
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Autoclave Bags",
      "status": "VERIFIED LIVE 2026-07-07 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/autoclave-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Autoclave Bags | Biohazard & Sterilization Bags",
        "chars": 47
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): VideoObject, FAQPage",
      "schema": null
    },
    {
      "name": "Bakery Bags",
      "status": "VERIFIED LIVE 2026-07-07 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/bakery-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Custom Bakery Bags | Food Safe Packaging for Baked Goods",
        "chars": 56
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Black Conductive Film",
      "status": "VERIFIED LIVE 2026-07-07 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/films/black-conductive-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Black Conductive Film | Fruth Custom Packaging",
        "chars": 46
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Bottom Seal Bags",
      "status": "VERIFIED LIVE 2026-07-07 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/bottom-seal-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Bottom Seal Bags | Custom Industrial & Food Packaging | Fruth",
        "chars": 61
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Cleanroom Bags",
      "status": "VERIFIED LIVE 2026-07-07 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/cleanroom-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "ISO Cleanroom Packaging for Electronics & Medical",
        "chars": 49
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "FFP Barrier Film",
      "status": "VERIFIED LIVE 2026-07-08 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/barrier-films/ffp-barrier-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "FFP Barrier Film | Fruth Custom Packaging",
        "chars": 41
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Foam Bags",
      "status": "VERIFIED LIVE 2026-07-08 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/foam-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Foam Bags | Polyethylene Foam Protective Packaging | Fruth",
        "chars": 58
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Fresh Produce Bags",
      "status": "VERIFIED LIVE 2026-07-08 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/fresh-produce-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Fresh Produce Bags | Custom Food Packaging for Produce",
        "chars": 54
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Grow Bags",
      "status": "VERIFIED LIVE 2026-07-08 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/grow-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Grow Bags | Plastic Plant Bags for Nurseries & Commercial Growers",
        "chars": 65
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Gusset Bags",
      "status": "VERIFIED LIVE 2026-07-08 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/gusset-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Gusset Bags | Expandable Custom Plastic Packaging Bags",
        "chars": 54
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Header Bags",
      "status": "VERIFIED LIVE 2026-07-08 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/header-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Header Bags | Custom Plastic Packaging Bags",
        "chars": 43
      },
      "meta": {
        "text": "Custom header bags with reinforced hang holes for retail and hardware packaging. Available in any size, gauge, and print. Request a quote today.",
        "chars": 144
      },
      "notes": "Crawler findings (2026-08-31): Meta description short (95 chars) — Short meta descriptions waste valuable space to attract clicks from search results.. Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Kraft Foil Barrier Film",
      "status": "VERIFIED LIVE 2026-07-13 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/barrier-films/kraft-foil-barrier-film",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Kraft Foil Barrier Film | Fruth Custom Packaging",
        "chars": 48
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Lay Flat Bags",
      "status": "VERIFIED LIVE 2026-07-13 (body copy + FAQ schema implemented per content-creation batch)",
      "liveUrl": "https://www.fruth.com/products/bags/lay-flat-bags",
      "editorUrl": "https://app.hubspot.com/ (Fruth portal — add page ID once access is confirmed)",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": "",
      "title": {
        "text": "Lay Flat Poly Bags | Custom Sizes | Fruth Custom Packaging",
        "chars": 58
      },
      "meta": null,
      "notes": "Direct site check 2026-09-14 confirms this body copy is already live on the page — DO NOT paste this again.",
      "schemaLabel": "Schema live per crawler (2026-08-31): FAQPage",
      "schema": null
    },
    {
      "name": "Homepage — H1 structure",
      "status": "AUDITED 2026-08-31 — technical fix needed, not yet implemented",
      "liveUrl": "https://www.fruth.com",
      "notes": "Crawler found 4 H1 tags on the homepage. Having more than one H1 dilutes the topic-focus signal for search engines. Consolidate to a single keyword-bearing H1 and demote the rest to styled paragraph/subhead text.",
      "schemaLabel": null,
      "schema": null,
      "title": null,
      "meta": null,
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": ""
    },
    {
      "name": "Learning Center — technical fixes",
      "status": "AUDITED 2026-08-31 — meta, schema, alt text, canonical all need attention",
      "liveUrl": "https://www.fruth.com/learning-center",
      "meta": {
        "text": "Explore Fruth's packaging resources — guides on materials, compliance, lead times, and supplier selection for industrial and specialty packaging buyers.",
        "chars": 152
      },
      "notes": "Crawler findings (2026-08-31): No JSON-LD schema found | Meta description short (77 chars) | 1 image(s) have empty alt text | No canonical tag. Also the audit's Quick Win to strengthen the H1 from \"Learning Center\" to \"Custom Packaging Resources & Industry Insights\" is still open (H1 presence is fine per crawler, but text is generic).",
      "schemaLabel": "Recommended schema (Blog/CollectionPage — not yet drafted, add before implementing)",
      "schema": null,
      "title": null,
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": ""
    },
    {
      "name": "Learning Center Author Archive",
      "status": "AUDITED 2026-08-31 — same 4 issues as Learning Center hub",
      "liveUrl": "https://www.fruth.com/learning-center/author/fruth-custom-packaging",
      "notes": "Auto-generated HubSpot blog author page. Crawler findings (2026-08-31): No JSON-LD schema found | Meta description short (77 chars) | 1 image(s) have empty alt text | No canonical tag. Low priority — confirm with client whether this page should be noindexed instead of optimized (it is a generic archive, not unique content).",
      "schemaLabel": null,
      "schema": null,
      "title": null,
      "meta": null,
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": ""
    },
    {
      "name": "/products (top-level hub) — correction",
      "status": "VERIFIED LIVE — already has title, meta, and schema; no action needed",
      "liveUrl": "https://www.fruth.com/products",
      "notes": "CORRECTION: the 2026-08-31 hand-written audit and an earlier packet both listed this page as missing a title/meta. The crawler dashboard (same date) shows it already has a live title (\"Custom Plastic Bags, Films & Barrier Materials | Fruth Products\"), a present meta description with no length flag, and CollectionPage/BreadcrumbList/FAQPage schema. No changes recommended unless the client wants to review the exact wording.",
      "schemaLabel": "Schema live per crawler: CollectionPage, BreadcrumbList, FAQPage, CollectionPage",
      "schema": null,
      "title": null,
      "meta": null,
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": ""
    },
    {
      "name": "Capabilities / Industries / Our Story — correction",
      "status": "VERIFIED LIVE — title + meta already present, no crawler findings",
      "liveUrl": "https://www.fruth.com/capabilities, /industries, /our-story",
      "notes": "CORRECTION: the hand-written audit listed all three as missing meta descriptions. The crawler dashboard (2026-08-31) shows title, H1, meta, schema, and image alts all present with zero findings flagged for any of the three. The only real open item for these pages is the body-copy expansion (see their individual entries above), not title/meta.",
      "schemaLabel": null,
      "schema": null,
      "title": null,
      "meta": null,
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": ""
    },
    {
      "name": "Thin Content Pages (word count)",
      "status": "AUDITED 2026-08-31 — crawler-confirmed, mapped to content-creation drafts",
      "liveUrl": "multiple — see notes",
      "notes": "Crawler flagged low word count on: Cushion Packaging Barrier Film (118 words), Nylon Film (145), Tamper Evident Bags (86), Scrim Foil Barrier Film (90), Vacuum Seal Bags (95), Multi-Pocket Bags (83), Side Seal Bags (148), Zipper Bags (129), Wicketed Bags (107), MIL-PRF-131K Barrier Film (103). All already have body-copy expansion drafted and ready to implement (see their individual page entries above and FRUTH-IMPLEMENTATION-PACKET.md). /contact was also flagged at 26 words on 2026-08-31 but a direct site check on 2026-09-18 found it now has ~89 words — see its own entry below; this note no longer applies to /contact.",
      "schemaLabel": null,
      "schema": null,
      "title": null,
      "meta": null,
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-08-31",
      "primaryKw": "",
      "secondaryKw": ""
    },
    {
      "name": "Contact Page",
      "status": "VERIFIED LIVE 2026-09-18 (title + meta present; meta slightly long) — optional tightening only",
      "liveUrl": "https://www.fruth.com/contact",
      "title": {
        "text": "Contact Fruth Custom Packaging | Request a Quote",
        "chars": 48
      },
      "meta": {
        "text": "Have questions or need a custom packaging solution? Contact Fruth to request a quote, get product info, or speak with a packaging specialist.",
        "chars": 144
      },
      "notes": "Direct site check 2026-09-18 (document.title + meta tag read via JS, not the Aug 31 crawler): live title is 48 chars, fine as-is — no change recommended. Live meta is 165 chars, 5 over the ~160 safe limit; tightened version above trims to 144 while keeping the same CTA. Word count is now ~89 (crawler recorded 26 words on 2026-08-31 — content has already been expanded on the client/HubSpot side since then, independent of this project). Not a required fix, just an optional tightening.",
      "schemaLabel": "Schema live per crawler (2026-08-31): ContactPage",
      "schema": null,
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-09-18",
      "primaryKw": "",
      "secondaryKw": ""
    },
    {
      "name": "Blog: 3 Questions to Ask Your Medical Packaging Manufacturer",
      "status": "AUDITED 2026-09-25 — 1 fix needed (schema), 1 opportunity (internal links)",
      "liveUrl": "https://www.fruth.com/learning-center/3-questions-to-be-asking-your-medical-packaging-manufacturer",
      "title": {
        "text": "3 Questions to be Asking Your Medical Packaging Manufacturer",
        "chars": 60
      },
      "meta": {
        "text": "Learn how to choose the right medical packaging manufacturer. Discover key factors like in-house production, lead times, and custom packaging solutions.",
        "chars": 152
      },
      "notes": "TITLE TAG: no change needed (60 chars, current — already live).\nMETA DESCRIPTION: no change needed (152 chars, current — already live).\nALT TAGS: no change needed. All 4 images checked via DOM — image 1 is the post's own hero (alt matches this post's title, correct); images 2-4 are an auto-generated \"Related Posts\" module (hhs-blog-card-inner), each correctly linking to and describing its OWN article. Not a bug.\nCANONICAL / H1 / WORD COUNT: all fine (canonical present + correct, H1 matches title, 661 words).\n\nSCHEMA — FIX NEEDED: live \"publisher\" field says \"Garlock Flexibles\" with Garlock's logo instead of Fruth (cross-brand contamination from the shared HubSpot portal — same pattern as the C-P Flexible Packaging news post). Corrected block below; placeholder logo URL (fruth.com/logo.png) not independently verified against HubSpot.\n\nPOTENTIAL LINKS — 0 internal links currently in the article body (confirmed via DOM check; only the auto Related-Posts cards link anywhere). Recommend adding:\n1. Q1 \"operate fully in-house\" -> /capabilities\n2. Q1 \"A vertically integrated manufacturer\" -> /fruth-360\n3. Q2 \"fully manufactured in the United States\" -> /our-story\n4. Q3 \"sterilization compatibility\" -> /products/bags/autoclave-bags\n5. Closing \"protected, compliant, and ready for market\" -> /industries",
      "schemaLabel": "SCHEMA (Head HTML) — current is wrong, replace with corrected version below",
      "schema": "CURRENT (live, wrong publisher):\n<script type=\"application/ld+json\">\n{\n  \"mainEntityOfPage\" : { \"@type\" : \"WebPage\", \"@id\" : \"https://www.fruth.com/learning-center/3-questions-to-be-asking-your-medical-packaging-manufacturer\" },\n  \"author\" : { \"name\" : \"Fruth Custom Packaging\", \"url\" : \"https://www.fruth.com/learning-center/author/fruth-custom-packaging\", \"@type\" : \"Person\" },\n  \"headline\" : \"3 Questions to be Asking Your Medical Packaging Manufacturer\",\n  \"datePublished\" : \"2024-09-13T12:45:00.000Z\",\n  \"dateModified\" : \"2026-02-17T15:52:14.829Z\",\n  \"publisher\" : { \"name\" : \"Garlock Flexibles\", \"logo\" : { \"url\" : \"https://www.fruth.com/hubfs/Garlock%20Logo.jpg\", \"@type\" : \"ImageObject\" }, \"@type\" : \"Organization\" },\n  \"@context\" : \"https://schema.org\",\n  \"@type\" : \"BlogPosting\",\n  \"image\" : [ \"https://www.fruth.com/hubfs/Fruth%20Custom%20Packaging/Home/customization.jpg\" ]\n}\n</script>\n\nCORRECTED (paste this instead):\n<script type=\"application/ld+json\">\n{\n  \"mainEntityOfPage\" : { \"@type\" : \"WebPage\", \"@id\" : \"https://www.fruth.com/learning-center/3-questions-to-be-asking-your-medical-packaging-manufacturer\" },\n  \"author\" : { \"name\" : \"Fruth Custom Packaging\", \"url\" : \"https://www.fruth.com/learning-center/author/fruth-custom-packaging\", \"@type\" : \"Person\" },\n  \"headline\" : \"3 Questions to be Asking Your Medical Packaging Manufacturer\",\n  \"datePublished\" : \"2024-09-13T12:45:00.000Z\",\n  \"dateModified\" : \"2026-02-17T15:52:14.829Z\",\n  \"publisher\" : { \"name\" : \"Fruth Custom Packaging\", \"logo\" : { \"url\" : \"https://www.fruth.com/logo.png\", \"@type\" : \"ImageObject\" }, \"@type\" : \"Organization\" },\n  \"@context\" : \"https://schema.org\",\n  \"@type\" : \"BlogPosting\",\n  \"image\" : [ \"https://www.fruth.com/hubfs/Fruth%20Custom%20Packaging/Home/customization.jpg\" ]\n}\n</script>",
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-09-25",
      "primaryKw": "",
      "secondaryKw": ""
    },
    {
      "name": "Blog: Custom Medical Packaging for Catheter & Syringe Bags",
      "status": "AUDITED 2026-09-25 — 2 fixes needed (meta, schema), 1 opportunity (internal links)",
      "liveUrl": "https://www.fruth.com/learning-center/custom-medical-packaging-for-catheter-syringe-bags",
      "title": {
        "text": "Custom Medical Packaging for Catheter Syringe Bags",
        "chars": 50
      },
      "meta": {
        "text": "Fruth manufactures custom medical packaging for catheters and syringes — sterile bags built to spec for device makers and healthcare buyers.",
        "chars": 140
      },
      "notes": "TITLE TAG: no change needed (50 chars, current — already live).\nMETA DESCRIPTION — FIX NEEDED: current is 182 chars (aim 140-160), gets truncated in search results. Corrected version above (140 chars).\nALT TAGS: no change needed. Same pattern as the other blog posts on this site — image 1 is this post's own hero (alt matches this post's title, correct); images 2-4 are the auto \"Related Posts\" module, each correctly linking to and describing its own article.\nCANONICAL / H1 / WORD COUNT: all fine (canonical present + correct, single H1 matches title, 484 words).\n\nSCHEMA — FIX NEEDED: same \"Garlock Flexibles\" publisher bug found on the \"3 Questions\" blog post — this is now confirmed on 2 of 2 blog posts checked, likely site-wide across every Learning Center article on this shared HubSpot portal. Corrected block below; placeholder logo URL (fruth.com/logo.png) not independently verified.\n\nPOTENTIAL LINKS — 0 internal links currently in the article body. Recommend adding:\n1. \"we recently partnered with a medical packaging supplier\" -> /industries (Medical/Bio/Pharma Packaging section)\n2. \"state-of-the-art printing presses and in-house flexographic printing capabilities\" -> /capabilities\n3. \"advanced conversion capabilities\" -> /capabilities\n4. Closing \"our team is ready to help design a solution tailored to your requirements\" -> /contact",
      "schemaLabel": "SCHEMA (Head HTML) — current is wrong, replace with corrected version below",
      "schema": "CURRENT (live, wrong publisher):\n<script type=\"application/ld+json\">\n{\n  \"mainEntityOfPage\" : { \"@type\" : \"WebPage\", \"@id\" : \"https://www.fruth.com/learning-center/custom-medical-packaging-for-catheter-syringe-bags\" },\n  \"author\" : { \"name\" : \"Fruth Custom Packaging\", \"url\" : \"https://www.fruth.com/learning-center/author/fruth-custom-packaging\", \"@type\" : \"Person\" },\n  \"headline\" : \"Custom Medical Packaging for Catheter Syringe Bags\",\n  \"datePublished\" : \"2023-08-16T12:45:00.000Z\",\n  \"dateModified\" : \"2026-02-17T16:18:24.429Z\",\n  \"publisher\" : { \"name\" : \"Garlock Flexibles\", \"logo\" : { \"url\" : \"https://www.fruth.com/hubfs/Garlock%20Logo.jpg\", \"@type\" : \"ImageObject\" }, \"@type\" : \"Organization\" },\n  \"@context\" : \"https://schema.org\",\n  \"@type\" : \"BlogPosting\",\n  \"image\" : [ \"https://www.fruth.com/hubfs/Fruth%20Custom%20Packaging/Products/custom-catheter-bag%20(1).jpg\" ]\n}\n</script>\n\nCORRECTED (paste this instead):\n<script type=\"application/ld+json\">\n{\n  \"mainEntityOfPage\" : { \"@type\" : \"WebPage\", \"@id\" : \"https://www.fruth.com/learning-center/custom-medical-packaging-for-catheter-syringe-bags\" },\n  \"author\" : { \"name\" : \"Fruth Custom Packaging\", \"url\" : \"https://www.fruth.com/learning-center/author/fruth-custom-packaging\", \"@type\" : \"Person\" },\n  \"headline\" : \"Custom Medical Packaging for Catheter Syringe Bags\",\n  \"datePublished\" : \"2023-08-16T12:45:00.000Z\",\n  \"dateModified\" : \"2026-02-17T16:18:24.429Z\",\n  \"publisher\" : { \"name\" : \"Fruth Custom Packaging\", \"logo\" : { \"url\" : \"https://www.fruth.com/logo.png\", \"@type\" : \"ImageObject\" }, \"@type\" : \"Organization\" },\n  \"@context\" : \"https://schema.org\",\n  \"@type\" : \"BlogPosting\",\n  \"image\" : [ \"https://www.fruth.com/hubfs/Fruth%20Custom%20Packaging/Products/custom-catheter-bag%20(1).jpg\" ]\n}\n</script>",
      "editorUrl": "https://app.hubspot.com/",
      "audited": "2026-09-25",
      "primaryKw": "",
      "secondaryKw": ""
    }
  ]
};
