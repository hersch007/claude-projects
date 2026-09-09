// Regenerable builder for the C-P Flexible Packaging running On-Page SEO Audit log.
// Add one object to AUDITS[] per audited page, then run:  node build-running-audit.js [optionalOutputPath]
// Global/sitewide elements (logo, GreenStream, phone, favicon, footer badges) are listed ONCE
// in the GLOBAL section below — do not repeat them in per-page image tables.
const fs = require("fs");
const docx = require("C:/Users/richa/AppData/Roaming/npm/node_modules/docx");
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  AlignmentType, HeadingLevel, BorderStyle, WidthType, ShadingType,
  VerticalAlign, PageNumber, PageBreak, Header, Footer, LevelFormat, ExternalHyperlink,
} = docx;

const BRAND = "215732";
const HDRTEXT = "FFFFFF";
const ZEBRA = "EEF3EF";

// ---------------------------------------------------------------------------
// GLOBAL / SITEWIDE ELEMENTS  (shown once — repeat on every page of the site)
// ---------------------------------------------------------------------------
const GLOBAL = [
  ["Company logo (garlock-cp-logo.png)", "garlock-cp-logo", "C-P Flexible Packaging company logo", "35", "Brand alt for header/footer logo; apply identically sitewide."],
  ["GreenStream block (greenstream-packaging-products-1.webp)", "greenstream-packaging-products-1 / (empty)", "GreenStream sustainable packaging products by C-P Flexible Packaging", "66", "Sustainability sub-brand; same alt sitewide."],
  ["Phone icon (phone.svg)", "phone / (missing)", "alt=”” (decorative)", "0", "Sits beside a visible phone number; empty alt avoids screen-reader noise."],
  ["Footer badge — SQF (footer-1.webp)", "(empty)", "SQF food safety certification badge – C-P Flexible Packaging", "59", "Global footer module — set once, propagates sitewide. CONFIRMED 2026-06-25."],
  ["Footer badge — AIB (footer-2.webp)", "(empty)", "AIB International food safety certification badge – C-P Flexible Packaging", "73", "Global footer module — set once, propagates sitewide. CONFIRMED 2026-06-25."],
  ["Footer badge — BRCGS (footer-3.webp)", "BRCGS Packaging Materials Certified...", "BRCGS Packaging Materials certification badge – C-P Flexible Packaging", "67", "Global footer module — set once, propagates sitewide. CONFIRMED via brcgs.com link 2026-06-25."],
  ["Favicon (garlock-cp-favicon.png)", "garlock-cp-favicon / (empty)", "alt=”” (decorative)", "0", "Favicons are not content; no alt needed."],
];

// ---------------------------------------------------------------------------
// AUDIT DATA — per-page UNIQUE content images only (no global elements)
// ---------------------------------------------------------------------------
const AUDITS = [
  {
    name: "Aerospace Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets/aerospace-packaging/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212697483948",
    audited: "2026-06-23",
    status: "IMPLEMENTED in HubSpot 2026-06-24 (title, meta, alt text)",
    primary: "aerospace packaging",
    secondary: "ESD packaging, flame-retardant film, cleanroom packaging, barrier packaging, aviation component protection",
    title: "Aerospace Packaging | ESD, Flame-Retardant & Cleanroom Films",
    titleChars: 60,
    meta: "Protect aerospace parts with custom flexible packaging—ESD, flame-retardant & cleanroom films built for aviation. Talk to C-P Flexible Packaging today.",
    metaChars: 151,
    images: [
      ["Hero aerospace shot (aerospace-market-1.webp)", "(empty)", "Custom flexible packaging protecting aerospace and aviation components in transit", "80", "Primary keyword + context on the lead image."],
      ["Flame-retardant film (Flame-Retardant-Polyethylene.webp)", "(empty)", "Flame-retardant polyethylene film for aerospace parts packaging and protection", "78", "Targets the “flame-retardant film” secondary keyword."],
      ["Cleanroom packaging (cleanroom-aerospace-1.webp)", "(empty)", "Cleanroom-grade flexible packaging for sensitive aerospace electronic components", "79", "Captures “cleanroom packaging” + ESD intent."],
    ],
    schema: `{
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
}`,
    schemaType: "Service",
    schemaNote: "Production URLs (gcpflexpack.com) — confirm the path stays /markets/aerospace-packaging/ at launch. Service schema only (not a rich-result type; no visible UI needed). Breadcrumb skipped — no visible breadcrumb on page. Paste the script block below into HubSpot Settings → Advanced → Head HTML.",
    notes: [],
  },
  {
    name: "Autoclave Bags",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/bags/autoclave-bags",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771003966",
    audited: "2026-06-23",
    status: "IMPLEMENTED in HubSpot 2026-06-24 (title, meta, alt text)",
    primary: "autoclave bags",
    secondary: "steam sterilization, polypropylene bags, biohazard/medical waste, ETO sterilization, custom autoclavable bags, ISO 9001:2015",
    title: "Autoclave Bags | Steam-Sterilizable Polypropylene Bags",
    titleChars: 54,
    meta: "Custom autoclave bags in polypropylene for steam & ETO sterilization of medical instruments and biohazard waste. Get a quote from C-P Flexible Packaging.",
    metaChars: 153,
    images: [
      ["Waste disposal bag (disposing-waste-autoclave-bag.webp)", "autoclave bag for disposing products", "Autoclave bag for safe disposal of sterilized biohazard medical waste", "68", "Adds intent keywords (biohazard, medical waste)."],
      ["Autoclaved bag (autoclave-bag.webp)", "autoclaved bag", "Polypropylene autoclave bag for steam sterilization of medical instruments", "73", "Targets “steam sterilization” + material."],
      ["Customization (autoclave-customization.webp)", "autoclave bag manufacturer", "Custom-printed autoclave bags manufactured by C-P Flexible Packaging", "62", "Ties to brand + custom capability."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Autoclave Bag Manufacturing",
  "serviceType": "Custom autoclave bags",
  "description": "Custom autoclave bags in virgin polypropylene for steam and ETO sterilization of medical instruments and safe disposal of biohazard waste.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/bags/autoclave-bags"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by existing page content (steam/ETO sterilization, polypropylene, biohazard waste). No price/reviews, so Product schema is intentionally not used. Paste the script block below into Head HTML.",
    notes: [],
  },
  {
    name: "Bags (Functional Bags & Pouches)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/bags",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771002232",
    audited: "2026-06-23",
    status: "IMPLEMENTED in HubSpot 2026-06-24 (title, meta, alt text)",
    primary: "functional bags & pouches",
    secondary: "autoclave bags, cleanroom bags, EMI static-shielding bags, nylon bags, poly bags, medical/electronics/aerospace packaging",
    title: "Functional Bags & Pouches | Cleanroom, Static-Shielding & Poly Bags",
    titleChars: 67,
    meta: "Custom bags & pouches—autoclave, cleanroom, EMI static-shielding, nylon & poly for medical, electronics & aerospace. Contact C-P Flexible Packaging.",
    metaChars: 148,
    images: [
      ["Autoclave thumb (autoclave-bags-thumb.webp)", "autoclave-bags-thumb", "Autoclave bags for steam sterilization and medical waste disposal", "64", "Descriptive + keyword; replaces filename."],
      ["Cleanroom thumb (cleanroom-bags-thumb.webp)", "cleanroom-bags-thumb", "Cleanroom bags for contamination-free electronics and medical packaging", "71", "Targets “cleanroom bags”."],
      ["Static shielding (static-shielding-bags.webp)", "static-shielding-bags", "EMI static-shielding bags protecting sensitive electronic components", "67", "Targets “EMI static-shielding”."],
      ["Nylon bags (nylon-bags.webp)", "nylon-bags", "Durable nylon bags for high-barrier flexible packaging applications", "66", "Adds barrier context."],
      ["Poly bags (poly-bags-thumb.webp)", "poly-bags-thumb", "Custom poly bags for food, medical and industrial packaging", "58", "Application keywords."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Functional Bags & Pouches",
  "description": "Custom functional bags and pouches—autoclave, cleanroom, EMI static-shielding, nylon, and poly—for medical, electronics, aerospace, and food applications.",
  "url": "https://gcpflexpack.com/products/bags",
  "isPartOf": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "CollectionPage",
    schemaNote: "Category/hub page → CollectionPage (Product schema not used: no price/reviews, and the page lists 5 bag types). Optional later upgrade: an ItemList linking each bag sub-page once those URLs are confirmed. Paste the script block below into Head HTML.",
    notes: [
      "Category/hub page — add internal links from each thumbnail/CTA to its product page.",
      "Reuse these thumbnail alt texts on the child product pages.",
    ],
  },
  {
    name: "Laser Die-Cut Window Rollstock",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/laser-die-cut-window-rollstock",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841340082",
    audited: "2026-06-23",
    status: "IMPLEMENTED in HubSpot 2026-06-24 (title, meta, alt text)",
    primary: "laser die-cut window rollstock (DCRS)",
    secondary: "product visibility, shelf life, VFFS/HFFS, snacks, bakery, cured meats, brand improvement",
    title: "Laser Die-Cut Window Rollstock | Product Visibility Packaging",
    titleChars: 61,
    meta: "Patented laser die-cut window rollstock adds product visibility & shelf life for snacks, bakery & meats. Discover DCRS from C-P Flexible Packaging.",
    metaChars: 147,
    images: [
      ["Shopper/window (window-die-cut-rollstock.webp)", "Woman looking at package with window", "Shopper viewing product through laser die-cut window rollstock packaging", "71", "Adds primary keyword; keeps human context."],
      ["Die-cut bag (Die-cut-rollstock-3-222x300.webp)", "Bag with die-cut window", "Flexible package with laser die-cut window showing product inside", "64", "Reinforces keyword + visibility benefit."],
      ["Cured meats flow-wrap (flow-wrap-packaging-meats-179x300.webp)", "die-cut rollstock for cured meats", "Die-cut window rollstock flow-wrap packaging for cured meats", "59", "Application keyword; cleaner phrasing."],
    ],
    notes: [
      "Page references an Elevation Meats case study — link it internally for added authority.",
    ],
  },
  {
    name: "Child-Resistant Flexible Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/child-resistant-flexible-packaging",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841756264",
    audited: "2026-06-23",
    status: "IMPLEMENTED in HubSpot 2026-06-24 (title, meta, alt text)",
    primary: "child-resistant flexible packaging",
    secondary: "child-resistant packaging film, ASTM D3475, senior-friendly closures, cannabis/CBD/THC, medications, household chemicals",
    title: "Child-Resistant Flexible Packaging | ASTM D3475 Pouches & Film",
    titleChars: 62,
    meta: "ASTM D3475-compliant child-resistant flexible packaging for cannabis, medications & chemicals—secure yet senior-friendly. Talk to C-P Flexible Packaging.",
    metaChars: 153,
    images: [
      ["Red rollstock (child-resistant-rollstock-red-300x300.webp)", "(empty)", "Child-resistant flexible packaging rollstock for medications and cannabis", "72", "Primary keyword + use case on the main product image."],
    ],
    notes: [
      "Emphasize ASTM D3475 in body copy + image caption — strong trust signal.",
      "Rename child-resistant-rollstock-red-300x300.webp → child-resistant-flexible-packaging-rollstock.webp.",
    ],
  },
  {
    name: "Laser-Scored Pouches (Easy-Open Packaging)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/laser-scored-pouches-easy-open-packaging",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771149912",
    audited: "2026-06-24",
    status: "IMPLEMENTED in HubSpot 2026-06-25 (title, meta, alt text)",
    primary: "laser-scored pouches, easy-open packaging",
    secondary: "laser scoring technology, controlled tear patterns, senior-friendly packaging, premade/stand-up pouches, easy-open convenience",
    title: "Laser-Scored Pouches | Easy-Open Flexible Packaging",
    titleChars: 51,
    meta: "Laser-scored pouches deliver easy-open, tear-anywhere convenience without sacrificing barrier or seal strength. Talk to C-P Flexible Packaging today.",
    metaChars: 149,
    images: [
      ["Pouch mockup (laser-scored-pouch-mockup-1-201x300.webp)", "laser-scored-pouch", "Laser-scored stand-up pouch with easy-open tear feature", "55", "Adds primary keyword + benefit; replaces fragment."],
      ["Score-line close-up (laser_scored_mockup_closeup-300x148.webp)", "Laser-scored pouch closeup", "Close-up of laser score line on an easy-open flexible pouch", "59", "Describes the actual feature (the score line)."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Laser-Scored Easy-Open Pouches",
  "serviceType": "Laser-scored flexible pouches",
  "description": "Laser-scored flexible pouches with controlled easy-open tear patterns that maintain barrier protection and seal strength for consumer-friendly packaging.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/laser-scored-pouches-easy-open-packaging"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by existing page content (laser scoring, easy-open tear, barrier/seal). Distinct from the Laser Die-Cut Window Rollstock page. Paste the script block below into Head HTML.",
    notes: [
      "Distinct product from the Laser Die-Cut Window Rollstock page — this is laser scoring for easy-open tear lines.",
      "Internally link to related pouch pages (stand-up, zipper, slider, stick packs).",
    ],
  },
  {
    name: "Cleanroom Bags",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/bags/cleanroom-bags",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212770783859",
    audited: "2026-06-24",
    status: "IMPLEMENTED in HubSpot 2026-06-25 (title, meta, alt text)",
    primary: "cleanroom bags",
    secondary: "cleanroom packaging, FDA-compliant, anti-static bags, medical device packaging, semiconductor packaging, polyethylene bags",
    title: "Cleanroom Bags | FDA-Compliant Medical & Electronics Packaging",
    titleChars: 62,
    meta: "Cleanroom bags made in contamination-controlled facilities to protect medical devices & electronics. Talk to C-P Flexible Packaging today.",
    metaChars: 138,
    images: [
      ["Cleanroom production (cleanroom-packaging-1.webp)", "packaging in cleanroom facility", "Flexible packaging produced in a contamination-controlled cleanroom facility", "76", "Adds keyword + process context."],
      ["Medical bag (medical-equipment-packaging.webp)", "cleanroom medical bag", "Cleanroom bag protecting sterile medical equipment from contamination", "68", "Application + benefit."],
      ["Lay-flat bag (Cleanroom-Bag.webp)", "Lay flat cleanroom bag protecting medical device (blood pressure cuff)", "Lay-flat cleanroom bag protecting a sterile medical device", "57", "Tightens existing alt; keeps keyword."],
      ["Electronics bag (electronic-device.webp)", "Anti-static cleanroom bag for electronic components", "Anti-static cleanroom bag protecting sensitive electronic components", "67", "Targets anti-static + electronics."],
      ["Facility (cleanroom-facility.webp)", "cleanroom", "Inside a controlled-environment cleanroom manufacturing facility", "62", "Replaces one-word alt with descriptive context."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Cleanroom Bags",
  "serviceType": "Cleanroom flexible packaging",
  "description": "Cleanroom bags manufactured in contamination-controlled facilities to protect medical devices, pharmaceuticals, and sensitive electronic components.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/bags/cleanroom-bags"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by page content (contamination-controlled, FDA-compliant, medical/electronics). Paste the script block below into Head HTML.",
    notes: [],
  },
  {
    name: "Coffee & Beverage Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets/coffee",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212697247491",
    audited: "2026-06-24",
    status: "IMPLEMENTED in HubSpot 2026-06-24 (title, meta, alt text)",
    primary: "coffee packaging, beverage packaging",
    secondary: "retail coffee bags, pod overwraps, shrink sleeves, stretch sleeves, roll-fed labels, sustainable/compostable coffee packaging",
    title: "Coffee & Beverage Packaging | Bags, Pods, Sleeves & Labels",
    titleChars: 58,
    meta: "Custom coffee & beverage packaging—retail bags, pod overwraps, shrink sleeves & roll-fed labels, with sustainable options. Contact C-P Flexible Packaging.",
    metaChars: 154,
    images: [
      ["Retail coffee bag (coffee-packaging.webp)", "Man passing retail paper coffee bag with window", "Custom retail paper coffee bag with a clear product window", "57", "Keeps product focus; cleaner phrasing."],
      ["Retail packs (Coffee-retail-packs-300x300.webp)", "Coffee Retail Package", "Custom retail coffee packaging in multiple bag formats", "54", "Adds format/keyword detail."],
      ["GreenStream pouch (greenstream-pouch-mockup-300x300.webp)", "GreenStream sustainable pouch", "GreenStream compostable coffee pouch for sustainable packaging", "62", "Page-specific mockup; ties sustainability + coffee."],
      ["Roll-fed label (roll-fed-label-bottle-300x300.webp)", "(empty)", "Roll-fed shrink sleeve label applied to a beverage bottle", "57", "Fills empty alt with format keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Coffee & Beverage Packaging",
  "serviceType": "Coffee and beverage flexible packaging",
  "description": "Custom coffee and beverage packaging including retail bags, pod overwraps, shrink and stretch sleeves, and roll-fed labels, with sustainable and compostable options.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/markets/coffee"
}`,
    schemaType: "Service",
    schemaNote: "Market/capability page → Service. Paste the script block below into Head HTML.",
    notes: [
      "Strong internal-link hub to coffee formats and the compostable-coffee pages.",
    ],
  },
  {
    name: "Cold-Seal Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/cold-seal-packaging",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771088402",
    audited: "2026-06-24",
    status: "IMPLEMENTED in HubSpot 2026-06-25 (title, meta, alt text)",
    primary: "cold-seal packaging",
    secondary: "heat-sensitive products, protein/breakfast bars, form/fill/seal, bar wraps, energy efficiency, confectionery",
    title: "Cold-Seal Packaging | Heat-Sensitive Bar & Snack Wraps",
    titleChars: 54,
    meta: "Cold-seal packaging protects heat-sensitive bars and snacks at high line speeds without heat. Get the co-packer guide from C-P Flexible Packaging.",
    metaChars: 146,
    images: [
      ["Bar mockup (snack-bar-with-cold-seal-packaging-mockup-1-240x300.webp)", "snack-bar-with-cold-seal-packaging", "Protein snack bar in cold-seal flow-wrap packaging", "50", "Replaces filename alt with product description."],
      ["Guide thumbnail (CPF-Cold-Seal-Guide-thumbnail.webp)", "CPF-Cold-Seal-Guide-thumbnail", "Cold-seal packaging guide for co-packers cover thumbnail", "56", "Describes the downloadable resource."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Cold-Seal Packaging",
  "serviceType": "Cold-seal flexible packaging",
  "description": "Cold-seal flexible packaging for heat-sensitive products such as protein and breakfast bars, sealing at high line speeds without heat for energy savings and product protection.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/cold-seal-packaging"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by page content (heat-sensitive, line speed, energy). Page has a downloadable co-packer guide — could add a separate resource later. Paste the script block below into Head HTML.",
    notes: [
      "Page offers a co-packer guide download — strong conversion asset; keep CTA prominent.",
    ],
  },
  {
    name: "Compostable Flexible Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/compostable-flexible-packaging",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841757309",
    audited: "2026-06-24",
    status: "IMPLEMENTED in HubSpot 2026-06-25 (title, meta, alt text)",
    primary: "compostable flexible packaging",
    secondary: "BPI certified, industrial composting, bioplastic/plant-based, coffee & snack applications, GreenStream",
    title: "Compostable Flexible Packaging | BPI-Certified Sustainable Films",
    titleChars: 64,
    meta: "BPI-certified compostable flexible packaging—films, pouches, bags & overwraps that break down cleanly. Explore GreenStream by C-P Flexible Packaging.",
    metaChars: 149,
    images: [
      ["BPI certification (bpi-certified-packaging-1-300x297.png)", "bpi certified compostable packaging", "BPI-certified compostable flexible packaging material", "53", "Tightens alt; keeps certification keyword."],
      ["Compostable K-Cup (compostable-k-cup-300x280.webp)", "man placing compostable k-cup into keurig machine", "Compostable K-Cup coffee pod placed into a single-serve brewer", "62", "Cleaner phrasing; keeps application."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Compostable Flexible Packaging",
  "serviceType": "Compostable flexible packaging",
  "description": "BPI-certified compostable flexible packaging—films, pouches, bags, and overwraps made from plant-based materials that break down in industrial composting.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/compostable-flexible-packaging"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by page content (BPI certified, industrial composting, plant-based). Paste the script block below into Head HTML.",
    notes: [
      "BPI certification is a strong trust signal — keep the badge + mention in copy.",
    ],
  },
  {
    name: "Compostable Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/compostable-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771116896",
    audited: "2026-06-24",
    status: "IMPLEMENTED in HubSpot 2026-06-25 (title, meta, alt text)",
    primary: "compostable pouches",
    secondary: "plant-based/PLA, stand-up pouches, coffee frac packs, snack packaging, detergent pods, barrier performance",
    title: "Compostable Pouches | Plant-Based Stand-Up & Frac Packs",
    titleChars: 55,
    meta: "100% compostable pouches in plant-based films for coffee, snacks & home care—stand-up, box & frac packs. Talk to C-P Flexible Packaging today.",
    metaChars: 142,
    images: [
      ["Pouch lineup (compostable-pouches.webp)", "compostable stand-up pouches, box pouches, frac packs", "Plant-based compostable stand-up, box and frac-pack pouches", "59", "Adds material keyword; keeps formats."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Compostable Pouches",
  "serviceType": "Compostable flexible pouches",
  "description": "100% compostable pouches in plant-based films for coffee, snacks, and home-care products, available as stand-up, box, and frac-pack formats.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/compostable-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by page content (100% compostable, plant-based, formats). Paste the script block below into Head HTML.",
    notes: [
      "Internally link to Compostable Flexible Packaging and coffee/snack market pages.",
    ],
  },
  {
    name: "Confectionery Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets/confectionery",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212697249792",
    audited: "2026-06-25",
    status: "IMPLEMENTED in HubSpot 2026-06-25 (title, meta, alt text)",
    primary: "confectionery packaging",
    secondary: "candy & sweets packaging, stand-up pouches, rollstock, shrink sleeves, cold-seal, die-cut windows, sustainable options",
    title: "Confectionery Packaging | Custom Pouches, Rollstock & Shrink Sleeves",
    titleChars: 68,
    meta: "Custom confectionery packaging for candy & sweets—stand-up pouches, rollstock, shrink sleeves & cold-seal. Contact C-P Flexible Packaging.",
    metaChars: 137,
    images: [
      ["Confectionery group shot (confectionery-group-shot.webp)", "(empty)", "Custom flexible packaging for a range of confectionery products", "62", "Fills empty alt with primary keyword."],
      ["Cold-seal thumbnail (cold-seal-packaging-thumbnail-2-e1596174268592.webp)", "(empty)", "Cold-seal flow-wrap packaging for confectionery bars", "52", "Describes format + application."],
      ["GreenStream pouch (greenstream-pouch-mockup-300x300.webp)", "(empty)", "GreenStream compostable pouch for sustainable confectionery packaging", "68", "Sustainability + market keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Confectionery Packaging",
  "serviceType": "Confectionery flexible packaging",
  "description": "Custom flexible packaging for candy and confectionery, including stand-up pouches, printed rollstock, shrink sleeves, and cold-seal, with sustainable options.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/markets/confectionery"
}`,
    schemaType: "Service",
    schemaNote: "Market page → Service. Paste the script block below into Head HTML.",
    notes: [
      "Descriptive alt text added to all content images for accessibility and search visibility.",
    ],
  },
  {
    name: "Cookie & Bakery Product Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets/cookies-bakery-products/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212697561679",
    audited: "2026-06-25",
    status: "IMPLEMENTED in HubSpot 2026-06-25 (title, meta, alt text)",
    primary: "cookie & bakery packaging",
    secondary: "reclose/resealable packaging, stand-up pouches, slug packs, flow wrap, cold-seal, HD flexographic printing",
    title: "Cookie & Bakery Packaging | Resealable Pouches, Slug Packs & Flow Wrap",
    titleChars: 70,
    meta: "Cookie & bakery packaging with reclose technology—stand-up pouches, slug packs & flow wrap that protect freshness. Talk to C-P Flexible Packaging.",
    metaChars: 146,
    images: [
      ["Reclose formats (standup-pouch-slug-pack-flow-wrap-reclose.webp)", "(empty)", "Resealable stand-up pouch, slug pack and flow wrap for cookies", "61", "Captures reclose + format keywords."],
      ["Glossy snack pack (glossy-snack-pack-300x166.webp)", "(empty)", "Glossy printed flexible snack pack for bakery products", "53", "Adds print + application context."],
      ["Product shot (IMG_2405-crop.webp)", "(empty)", "Flexible packaging for cookies and bakery products", "50", "Generic-but-accurate; replaces empty alt."],
      ["Cold-seal thumbnail (cold-seal-packaging-thumbnail-2-e1596174268592.webp)", "(empty)", "Cold-seal flow-wrap packaging for bakery snacks", "48", "Format + application."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Cookie & Bakery Product Packaging",
  "serviceType": "Cookie and bakery flexible packaging",
  "description": "Flexible packaging for cookies and bakery products with reclose technology, including stand-up pouches, slug packs, and flow wrap that protect freshness.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/markets/cookies-bakery-products/"
}`,
    schemaType: "Service",
    schemaNote: "Market page → Service. Reclose technology is the differentiator. Paste the script block below into Head HTML.",
    notes: [
      "Reclose technology is the differentiator — lead with it in copy and internal links.",
    ],
  },
  {
    name: "Custom Shrink Bands",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/shrink-bands",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212860442496",
    audited: "2026-06-25",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text)",
    primary: "shrink bands, custom shrink bands",
    secondary: "PVC shrink bands, PETG shrink bands, tamper-evident sealing, preformed shrink bands, high shrink ratios, bundling/upselling",
    title: "Shrink Bands | Custom PVC & PETG Tamper-Evident Bands",
    titleChars: 53,
    meta: "Custom PVC & PETG shrink bands for tamper-evident sealing, bundling & upselling—printed or clear, high shrink ratios. Contact C-P Flexible Packaging.",
    metaChars: 149,
    images: [
      ["Printed bands (shrink-bands-1 (1).webp)", "shrink-bands-1 (1)", "Custom printed PVC shrink bands for product packaging", "52", "Replaces filename alt with keyword."],
      ["Water bottle band (shrink-band-water-bottle.webp)", "(empty)", "Clear shrink band sealing a water bottle for tamper evidence", "59", "Application + benefit keyword."],
      ["Preformed bands (preformed-shrink-bands.webp)", "(empty)", "Preformed shrink bands ready for high-speed application", "54", "Captures 'preformed' secondary keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Custom Shrink Bands",
  "serviceType": "Custom shrink bands",
  "description": "Custom PVC and PETG shrink bands for tamper-evident sealing, product bundling, and upselling, available printed or clear with high shrink ratios.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/shrink-bands"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by page content (PVC/PETG, tamper-evident, preformed). Paste the script block below into Head HTML.",
    notes: [
      "Well-structured page (good H2/H3 hierarchy) — strong on-page foundation.",
    ],
  },
  {
    name: "Cold-Seal Packaging Guide (Download)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/cold-seal-packaging-guide",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212915848026",
    audited: "2026-06-25",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta)",
    primary: "cold-seal packaging guide",
    secondary: "co-packer solutions, short-run packaging, supplier vetting, cold-seal technology",
    title: "Cold-Seal Packaging Guide | Free Download for Co-Packers",
    titleChars: 56,
    meta: "Download the free cold-seal packaging guide—benefits, short-run feasibility & how to vet a supplier. Get your copy from C-P Flexible Packaging.",
    metaChars: 143,
    images: [
      ["No page-specific content images (lead-gen landing page)", "—", "Phone icon is decorative (alt=\"\"); no content images to optimize here", "0", "Focus is the title, meta, and the download form CTA."],
    ],
    notes: [
      "Lead-generation landing page — the focus is the download form, not on-page imagery.",
      "Make sure the form and value proposition are above the fold.",
    ],
  },
  {
    name: "Flat-Bottom Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/flat-bottom-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771152024",
    audited: "2026-06-25",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text)",
    primary: "flat-bottom pouches",
    secondary: "BOX POUCHES, quad-seal pouches, shelf stability, billboard effect, pet food/coffee/snacks, five-panel print",
    title: "Flat-Bottom Pouches | Box & Quad-Seal Pouches with Shelf Appeal",
    titleChars: 63,
    meta: "Flat-bottom pouches—BOX POUCHES® & quad-seal—for standout shelf presence and stability across coffee, snacks & pet food. Talk to C-P Flexible Packaging.",
    metaChars: 152,
    images: [
      ["Flat-bottom pouch (flat-bottom-pouch-v2 (1).webp)", "Flat bottom pouch", "Flat-bottom box pouch with billboard shelf presence", "51", "Adds benefit keyword (shelf/billboard)."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Flat-Bottom Pouches",
  "serviceType": "Flat-bottom and box pouches",
  "description": "Flat-bottom pouches including BOX POUCHES and quad-seal formats that maximize shelf presence and stability for coffee, snacks, candy, and pet food.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/flat-bottom-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by page content (box/quad-seal, shelf stability). Paste the script block below into Head HTML.",
    notes: [
      "BOX POUCHES® is a registered mark — keep the ® in visible copy.",
    ],
  },
  {
    name: "Flexible Package Design & Consultation",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/capabilities/flexible-package-design-and-consultation",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212723132984",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "flexible packaging design, package consultation",
    secondary: "packaging engineering, prototyping, barrier testing, pouch sizing, shrink distortion testing, custom packaging solutions",
    title: "Flexible Package Design & Consultation | Custom Packaging Engineering",
    titleChars: 69,
    meta: "Flexible package design & consultation—from concept to prototype with barrier testing and material expertise. Talk to C-P Flexible Packaging today.",
    metaChars: 147,
    images: [
      ["Concept to prototype (concept-to-prototype.webp)", "(empty)", "Flexible packaging design from concept to prototype", "51", "Fills empty alt on the only content image."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Flexible Package Design & Consultation",
  "serviceType": "Flexible package design and consultation",
  "description": "Flexible packaging design and consultation from concept to prototype, including barrier testing, material selection, and pouch sizing for custom packaging.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/capabilities/flexible-package-design-and-consultation"
}`,
    schemaType: "Service",
    schemaNote: "Capability page → Service. Paste the script block below into Head HTML.",
    notes: [
      "Consider adding visuals of the design and prototyping process to strengthen the page.",
    ],
  },
  {
    name: "Flexible Packaging Prepress Services",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/capabilities/flexible-packaging-prepress-services/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212726050189",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "flexible packaging prepress services",
    secondary: "flexographic plates, in-house platemaking, HD flexographic printing, plate imagers, color consistency, lead times",
    title: "Flexible Packaging Prepress Services | In-House Platemaking",
    titleChars: 59,
    meta: "In-house flexible packaging prepress & platemaking for greater control, color consistency & faster lead times. Talk to C-P Flexible Packaging today.",
    metaChars: 148,
    images: [
      ["Platemaking (capabilities-prepress-platemaking.webp)", "(empty)", "In-house flexographic platemaking for flexible packaging prepress", "64", "Adds keyword + capability context."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Flexible Packaging Prepress Services",
  "serviceType": "Flexible packaging prepress services",
  "description": "In-house prepress and flexographic platemaking for flexible packaging, delivering greater quality control, color consistency, and faster lead times.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/capabilities/flexible-packaging-prepress-services/"
}`,
    schemaType: "Service",
    schemaNote: "Capability page → Service. Paste the script block below into Head HTML.",
    notes: [
      "In-house platemaking is the differentiator — strong angle for the meta.",
    ],
  },
  {
    name: "GreenStream Sustainable Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/sustainable-packaging/greenstream-products/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212770973196",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "sustainable packaging, GreenStream",
    secondary: "recyclable, post-consumer recycled (PCR), compostable, bio-sourced, paper structures, store drop-off, How2Recycle",
    title: "GreenStream Sustainable Packaging | Recyclable, PCR & Compostable",
    titleChars: 65,
    meta: "GreenStream sustainable packaging—recyclable, PCR, compostable, bio-sourced & paper structures with How2Recycle support. Talk to C-P Flexible Packaging.",
    metaChars: 152,
    images: [
      ["Hero (greenstream-sustainable-packaging-1.webp)", "sustainable packaging options", "GreenStream sustainable flexible packaging options by C-P Flexible Packaging", "75", "Adds brand + sub-brand to a weak alt."],
      ["Store drop-off (store-drop-off-recycling-150x150.webp)", "(empty)", "Store drop-off recycling label for recyclable flexible packaging", "63", "Targets store drop-off keyword."],
      ["How2Recycle (how2recycle-166x300.webp)", "(empty)", "How2Recycle store drop-off label on flexible packaging", "54", "Names the certification label."],
      ["PCR (pcr-150x150-1.webp)", "plastic bottles used for wipes packaging", "Post-consumer recycled (PCR) plastic used in flexible packaging", "63", "Targets PCR keyword; clearer than current."],
      ["Bio-sourced (sugar-cane-biopolymer-150x150.webp)", "packaging made from bioplastics", "Sugarcane bio-sourced biopolymer for flexible packaging", "55", "Specific material keyword."],
      ["Paper structures (greenstream-paper-recyclable-768x256.webp)", "(empty)", "Recyclable paper-based flexible packaging structures", "52", "Fills empty alt; paper keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "GreenStream Sustainable Packaging",
  "serviceType": "Sustainable flexible packaging",
  "description": "GreenStream sustainable flexible packaging including recyclable, post-consumer recycled (PCR), compostable, bio-sourced, and paper-based structures.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/sustainable-packaging/greenstream-products/"
}`,
    schemaType: "Service",
    schemaNote: "Sustainability sub-brand hub → Service. This is the canonical GreenStream page (the global GreenStream block links here). Paste the script block below into Head HTML.",
    notes: [
      "GreenStream™ is trademarked — keep the ™ in visible copy.",
      "Strong sustainability keyword coverage across the page's product images.",
    ],
  },
  {
    name: "Health & Wellness Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets/health-wellness-packaging/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212702549149",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "health & wellness packaging",
    secondary: "vitamins, supplements, personal care, plant-based, organic, better-for-you foods, sustainable packaging",
    title: "Health & Wellness Packaging | Supplements, Personal Care & More",
    titleChars: 63,
    meta: "Flexible packaging for health & wellness products—vitamins, supplements, personal care & better-for-you foods. Talk to C-P Flexible Packaging today.",
    metaChars: 148,
    images: [
      ["Market shot (health-wellness-markets (1).webp)", "(empty)", "Flexible packaging for health and wellness product categories", "60", "Primary keyword on the lead image."],
      ["Healthy trays (healthy-trays-300x224.webp)", "(empty)", "Packaging for healthy fresh-food trays and meals", "48", "Application context."],
      ["Study chart (mckinsey-study.JPG.webp)", "(empty)", "McKinsey study chart on consumer health and wellness demand", "58", "Describes the data graphic for screen readers."],
      ["Cold-seal (cold-seal-packaging.webp)", "(empty)", "Cold-seal flow-wrap packaging for wellness bars and snacks", "58", "Format + application."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Health & Wellness Packaging",
  "serviceType": "Health and wellness flexible packaging",
  "description": "Flexible packaging for health and wellness products including vitamins, supplements, personal care, and better-for-you foods, with sustainable options.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/markets/health-wellness-packaging/"
}`,
    schemaType: "Service",
    schemaNote: "Market page → Service. Paste the script block below into Head HTML.",
    notes: [
      "The consumer-demand chart should include a visible caption and source citation to strengthen E-E-A-T.",
    ],
  },
  {
    name: "HFFS and VFFS Rollstock",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/hffs-vffs-rollstock",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841334536",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "HFFS and VFFS rollstock",
    secondary: "form/fill/seal films, machinability, sealing properties, flow wrappers, sachets, stick packs, cold-seal",
    title: "HFFS & VFFS Rollstock | Form/Fill/Seal Packaging Films",
    titleChars: 54,
    meta: "HFFS & VFFS rollstock engineered for machinability and consistent seals on your form/fill/seal lines. Talk to C-P Flexible Packaging today.",
    metaChars: 139,
    images: [
      ["Color variant (HFFS-VFFS-color-variant-300x300.webp)", "Sachet and stick pack lamination", "HFFS and VFFS rollstock film for sachets and stick packs", "56", "Adds primary keyword; keeps application."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "HFFS and VFFS Rollstock",
  "serviceType": "HFFS and VFFS rollstock films",
  "description": "HFFS and VFFS rollstock films engineered for machinability and consistent sealing on horizontal and vertical form/fill/seal packaging lines.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/hffs-vffs-rollstock"
}`,
    schemaType: "Service",
    schemaNote: "Service schema; backed by page content (HFFS/VFFS, machinability, sealing). Paste the script block below into Head HTML.",
    notes: [],
  },
  {
    name: "High Burst Strength Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/high-burst-strength-flexible-packaging",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212874726802",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "high burst strength packaging, over-the-mountain (OTM) packaging",
    secondary: "seal strength, hermetic seal, burst prevention, altitude/pressure resistance, snack applications (popcorn, chips, pretzels)",
    title: "High Burst Strength Packaging | OTM Laminations for Snacks",
    titleChars: 58,
    meta: "High burst strength flexible packaging (OTM) with superior seal strength to survive altitude and shipping—ideal for snacks. Talk to C-P Flexible Packaging.",
    metaChars: 155,
    images: [
      ["Lamination (high-seal-laminations-color-variant-300x300.png)", "High seal strength lamination packaging", "High burst strength lamination film for over-the-mountain shipping", "64", "Adds OTM keyword + benefit."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "High Burst Strength Packaging",
  "serviceType": "High burst strength flexible packaging",
  "description": "High burst strength flexible packaging (over-the-mountain laminations) with superior seal strength to resist altitude and shipping pressure, ideal for snacks.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/high-burst-strength-flexible-packaging"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Over-the-mountain (OTM) seal strength is the differentiator — strong angle for snacks shipped at altitude.",
    ],
  },
  {
    name: "High-Barrier Flexible Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/high-barrier-flexible-packaging",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841335178",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "high-barrier flexible packaging",
    secondary: "oxygen barrier, moisture barrier, clear high-barrier, MVTR, foil alternatives, shelf-life extension",
    title: "High-Barrier Flexible Packaging | Oxygen & Moisture Barrier Films",
    titleChars: 65,
    meta: "High-barrier flexible packaging films that block oxygen and moisture to extend shelf life—clear or foil-free options. Talk to C-P Flexible Packaging today.",
    metaChars: 155,
    images: [
      ["Layers diagram (high-barrier-layers-diagram.webp)", "High barrier flexible packaging layers", "Diagram of high-barrier flexible packaging film layers", "54", "Tightens alt; keeps keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "High-Barrier Flexible Packaging",
  "serviceType": "High-barrier flexible packaging",
  "description": "High-barrier flexible packaging films that block oxygen and moisture to extend shelf life, available in clear and foil-free options for sensitive products.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/high-barrier-flexible-packaging"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Clear high-barrier as a foil alternative is a compelling, sustainable selling point.",
    ],
  },
  {
    name: "HD Flexographic Printing",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/capabilities/high-definition-flexographic-printing/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212729735948",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "HD flexographic printing, extended gamut printing",
    secondary: "color fidelity, print quality, multi-SKU efficiency, spectrophotometer testing, matte/gloss effects",
    title: "HD Flexographic Printing | Extended Gamut Packaging Printing",
    titleChars: 60,
    meta: "HD flexographic printing with extended gamut for vivid, consistent color at lower cost across multiple SKUs. Talk to C-P Flexible Packaging today.",
    metaChars: 146,
    images: [
      ["Auto-registration (auto-registration-1.jpg)", "(empty)", "Automatic registration on a high-definition flexographic press", "61", "Fills empty alt with capability keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "HD Flexographic Printing",
  "serviceType": "High-definition flexographic printing",
  "description": "High-definition flexographic printing with extended gamut for vivid, consistent color at lower cost across multiple SKUs on flexible packaging.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/capabilities/high-definition-flexographic-printing/"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "H1 is currently the generic “Capabilities” — change it to “HD Flexographic Printing” for a stronger on-page signal.",
    ],
  },
  {
    name: "Inno-Lok® Pre-Zippered Rollstock",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/inno-lok-pre-zippered-rollstock",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841338279",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text, schema)",
    primary: "Inno-Lok pre-zippered rollstock, resealable packaging",
    secondary: "VFFS compatibility, no equipment modification, speed-to-market, frozen/pet food/snacks/cereals",
    title: "Inno-Lok® Pre-Zippered Rollstock | Resealable VFFS Packaging",
    titleChars: 60,
    meta: "Inno-Lok® pre-zippered rollstock adds a resealable zipper on your existing VFFS line—no equipment changes. Talk to C-P Flexible Packaging today.",
    metaChars: 144,
    images: [
      ["Zipper pouch (innolok-zipper.jpg)", "inno-lok resealable zipper pouch", "Inno-Lok pre-zippered resealable pouch with press-to-close zipper", "64", "Adds detail to existing alt."],
      ["Machine (innolok-machine.jpg)", "inno-lok packaging machine", "Inno-Lok rollstock running on a vertical form/fill/seal machine", "62", "Adds VFFS context."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Inno-Lok Pre-Zippered Rollstock",
  "serviceType": "Pre-zippered resealable rollstock",
  "description": "Inno-Lok pre-zippered rollstock adds a resealable zipper to packaging on existing vertical form/fill/seal lines with no equipment modifications.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/inno-lok-pre-zippered-rollstock"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Inno-Lok® is a registered mark — keep the ® in visible copy.",
      "Lead with “runs on your existing VFFS line, no equipment changes” — the strongest buyer benefit.",
    ],
  },
  {
    name: "Internal Recycling Facility",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/sustainable-packaging/internal-recycling-facility/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212780725876",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-26 (title, meta, alt text)",
    primary: "internal recycling facility, PE scrap recycling",
    secondary: "PE/LDPE scrap, waste diversion, post-industrial plastic, landfill reduction, sustainability commitment",
    title: "Internal Recycling Facility | PE Scrap Recycling & Waste Diversion",
    titleChars: 66,
    meta: "Inside C-P's internal recycling facility—diverting millions of pounds of PE scrap from landfill each year. Explore our sustainability commitment.",
    metaChars: 146,
    images: [
      ["Recycling process (recycling-process-pe-768x256.webp)", "(empty)", "C-P Flexible Packaging internal PE scrap recycling process", "57", "Fills empty alt with brand + process keyword."],
    ],
    notes: [
      "Informational sustainability page — no service/product schema applies.",
      "Strong proof points (millions of pounds of PE diverted from landfill) — feature them prominently for trust.",
    ],
  },
  {
    name: "Lidding Films",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/lidding-films",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841360809",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-28 (title, meta, alt text, schema)",
    primary: "lidding films",
    secondary: "peelable lidding, high-barrier films, modified atmosphere packaging (MAP), HPP-compatible, produce/meat/dairy/prepared meals, Affirm",
    title: "Lidding Films | Peelable & High-Barrier Films for Rigid Containers",
    titleChars: 66,
    meta: "Affirm™ lidding films—peelable, lockdown & peel-reseal—for produce, meats, dairy & prepared meals. Talk to C-P Flexible Packaging today.",
    metaChars: 136,
    images: [
      ["Affirm films (affirm-lidding-films-.webp)", "lidding films for produce, meat, cheese, meals", "Affirm lidding films for produce, meat, cheese and prepared meals", "64", "Adds brand line; keeps applications."],
      ["Affirm films wide (Affirm-lidding-films-768x471.webp)", "lidding film for produce, meals, snacks", "Affirm lidding film sealing produce, meals and snack containers", "62", "Cleaner phrasing + keyword."],
      ["Dips/salsa (salsa-dip-guac-lidding-film.webp)", "family with dips salsa packaging", "Lidding film sealing salsa, dip and guacamole containers", "56", "Describes the actual product/application."],
      ["General purpose (general-purpose-lidding-films.webp)", "general purpose lidding films", "General-purpose peelable lidding films for rigid containers", "58", "Adds peelable + container keyword."],
      ["High performance (high-performance-lidding-films.webp)", "films for cheese, meats, guacamole", "High-performance lidding films for cheese, meats and guacamole", "61", "Adds product-tier keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Lidding Films",
  "serviceType": "Lidding films",
  "description": "Peelable and high-barrier lidding films for rigid containers, including Affirm films for produce, meats, dairy, and prepared meals with freshness and tamper evidence.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/lidding-films"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Affirm™ is a trademarked product line — keep the ™ in visible copy.",
      "Strong image set (five application shots) — good keyword coverage across produce, meats, and dairy.",
    ],
  },
  {
    name: "Markets (Hub)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212656433732",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-28 (title, meta, alt text, schema)",
    primary: "flexible packaging markets",
    secondary: "snack, cookie & bakery, coffee, pet food, confectionery, health & wellness, medical & electronics, aerospace",
    title: "Markets | Flexible Packaging for Snacks, Coffee, Pet Food & More",
    titleChars: 64,
    meta: "Flexible packaging expertise across snacks, bakery, coffee, pet food, confectionery, health, medical & aerospace markets. Talk to C-P Flexible Packaging.",
    metaChars: 153,
    images: [
      ["Snacks (CPF-market-pages-snacks.webp)", "CPF-market-pages-snacks", "Flexible packaging for the snack food market", "44", "Replaces filename alt with keyword."],
      ["Cookies (CPF-market-pages-cookies.webp)", "CPF-market-pages-cookies", "Flexible packaging for cookies and bakery products", "50", "Replaces filename alt."],
      ["Coffee (CPF-market-pages-coffee (2).webp)", "CPF-market-pages-coffee (2)", "Flexible packaging for the coffee and beverage market", "53", "Replaces filename alt."],
      ["Pet food (CPF-market-pages-pet-food (1).webp)", "CPF-market-pages-pet-food (1)", "Flexible packaging for the pet food market", "42", "Replaces filename alt."],
      ["Confectionery (CPF-market-pages-confectionery.webp)", "CPF-market-pages-confectionery", "Flexible packaging for the confectionery market", "47", "Replaces filename alt."],
      ["Health & wellness (health-wellness-markets.webp)", "health-wellness-markets", "Flexible packaging for health and wellness products", "51", "Replaces filename alt."],
      ["Medical & electronics (medical-electronics-market.webp)", "medical-electronics-market", "Flexible packaging for medical and electronics cleanroom", "56", "Replaces filename alt."],
      ["Aerospace (aerospace-market.webp)", "aerospace-market", "Flexible packaging for the aerospace market", "43", "Replaces filename alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Markets",
  "description": "Industry-specific flexible packaging for snack, cookie and bakery, coffee, pet food, confectionery, health and wellness, medical and electronics, and aerospace markets.",
  "url": "https://gcpflexpack.com/markets",
  "isPartOf": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "CollectionPage",
    schemaNote: "Hub page → CollectionPage.",
    notes: [
      "Markets hub — the eight thumbnails feed the individual market pages; reuse these alt texts on the matching market pages for consistency.",
      "All eight thumbnails used filename-style alt — a clean, high-value batch fix.",
    ],
  },
  {
    name: "Matte & Gloss Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/matte-and-gloss-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212780755229",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-28 (title, meta, alt text, schema)",
    primary: "matte and gloss pouches",
    secondary: "stand-up pouches, registered matte/gloss effects, shelf impact, brand recognition, premade pouches",
    title: "Matte & Gloss Pouches | High-Impact Stand-Up Pouch Finishes",
    titleChars: 59,
    meta: "Matte & gloss stand-up pouches with registered finish effects for massive shelf impact and brand standout. Talk to C-P Flexible Packaging today.",
    metaChars: 144,
    images: [
      ["Pouch (matte-gloss-pouch-241x300.webp)", "Pouch with matte and gloss finish", "Stand-up pouch with contrasting matte and gloss finish", "54", "Adds format + benefit detail."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Matte & Gloss Pouches",
  "serviceType": "Matte and gloss stand-up pouches",
  "description": "Premade matte and gloss stand-up pouches with registered finish effects that create high shelf impact and brand recognition in retail.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/matte-and-gloss-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Premade stand-up pouch format. Distinct from the Matte/Gloss rollstock page — cross-link the two and keep their angles separate to avoid competing for the same term.",
    ],
  },
  {
    name: "Matte/Gloss Flexible Packaging (Rollstock)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/matte-gloss-packaging",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841371249",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-28 (title, meta, alt text, schema)",
    primary: "matte/gloss flexible packaging",
    secondary: "registered matte/gloss effects, shelf impact, three-dimensional finish, brand recognition, rollstock",
    title: "Matte/Gloss Flexible Packaging | Registered Finish Effects",
    titleChars: 58,
    meta: "Matte/gloss flexible packaging with registered finish effects that add 3D shelf impact and brand recognition. Talk to C-P Flexible Packaging today.",
    metaChars: 147,
    images: [
      ["Rollstock (matte-gloss-packaging-241x300.webp)", "matte-gloss-packaging", "Flexible packaging rollstock with registered matte and gloss finish", "67", "Replaces filename alt; adds format keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Matte/Gloss Flexible Packaging",
  "serviceType": "Matte/gloss flexible packaging",
  "description": "Matte and gloss flexible packaging rollstock with registered finish effects that add three-dimensional shelf impact and brand recognition.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/matte-gloss-packaging"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Rollstock/printing format. Distinct from the Matte & Gloss Pouches page — differentiate (rollstock vs premade pouches) and cross-link to avoid two pages competing for the same keyword.",
    ],
  },
  {
    name: "Medical & Electronics Cleanroom Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets/medical-electronics-cleanroom-packaging/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212702608394",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-29 (title, meta, alt text, schema)",
    primary: "medical cleanroom packaging, electronics cleanroom packaging",
    secondary: "antistatic/ESD, ISO class 5, bio-pharmaceutical, catheter packaging, contamination control, largest cleanroom manufacturer",
    title: "Medical & Electronics Cleanroom Packaging | ISO-Class Films",
    titleChars: 59,
    meta: "Cleanroom packaging for medical devices and electronics—antistatic, ISO-class films built for contamination control. Talk to C-P Flexible Packaging.",
    metaChars: 148,
    images: [
      ["Medical cleanroom (medical-packaging-cleanroom.webp)", "(empty)", "Cleanroom flexible packaging for sterile medical devices", "56", "Primary keyword on lead image."],
      ["Manufacturing (largest-cleanroom-manufacturer.JPG.webp)", "(empty)", "C-P Flexible Packaging cleanroom manufacturing operation", "55", "Brand + authority context."],
      ["Medical market (medical-market.webp)", "(empty)", "Flexible packaging for the medical and bio-pharmaceutical market", "63", "Market keyword."],
      ["Electronics market (electronic-market.webp)", "(empty)", "Flexible packaging for the electronics market", "45", "Market keyword."],
      ["Conductive bag (black-conductive-packaging-300x300.webp)", "(empty)", "Black conductive antistatic bag for electronic components", "57", "ESD/antistatic keyword."],
      ["Components (electronic-components-packaging.webp)", "(empty)", "Static-control packaging protecting electronic components", "57", "Static-control keyword."],
      ["Electronics cleanroom (electronic-packaging-cleanroom.webp)", "(empty)", "Cleanroom packaging for sensitive electronic components", "55", "Cleanroom + electronics."],
      ["Certified (cleanroom-certified-300x280.webp)", "(empty)", "Cleanroom-certified packaging manufacturing badge", "49", "Trust signal."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Medical & Electronics Cleanroom Packaging",
  "serviceType": "Medical and electronics cleanroom packaging",
  "description": "Cleanroom flexible packaging for medical devices, bio-pharmaceuticals, and electronics, including antistatic and ISO-class films for contamination control.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/markets/medical-electronics-cleanroom-packaging/"
}`,
    schemaType: "Service",
    schemaNote: "Market page → Service.",
    notes: [
      "Eight content images, all with empty alt — a high-value accessibility and SEO fix.",
      "Lead with “largest cleanroom packaging manufacturer” for authority.",
    ],
  },
  {
    name: "Nylon Bags",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/bags/nylon-bag",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212770974979",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-29 (title, meta, alt text, schema)",
    primary: "nylon bags",
    secondary: "cleanroom, medical/lab packaging, electronics/semiconductors, abrasion-resistant, chemical, high-barrier",
    title: "Nylon Bags | Tough, Abrasion-Resistant Cleanroom & Lab Packaging",
    titleChars: 64,
    meta: "Tough, abrasion-resistant nylon bags with high-barrier protection for cleanroom, medical, lab and chemical use. Talk to C-P Flexible Packaging today.",
    metaChars: 149,
    images: [
      ["Nylon packaging (nylon-packaging.webp)", "medical syringe packaging", "Nylon bag packaging for medical syringes and lab supplies", "57", "Adds material keyword to existing alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Nylon Bags",
  "serviceType": "Nylon bags",
  "description": "Tough, abrasion-resistant nylon bags with high-barrier protection for cleanroom, medical, laboratory, electronics, and chemical applications.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/bags/nylon-bag"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Part of the cleanroom/medical bag family — internally link to Cleanroom Bags and the Bags hub.",
    ],
  },
  {
    name: "Our Companies",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/about-us/our-companies",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212632378720",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-29 (title, meta, alt text)",
    primary: "C-P Flexible Packaging family of companies",
    secondary: "Cleanroom Film & Bags, Fruth Custom Packaging, Preferred Packaging, Bass Flexible Packaging, Intelligraphix Systems",
    title: "Our Companies | The C-P Flexible Packaging Family of Brands",
    titleChars: 59,
    meta: "Meet the C-P Flexible Packaging family—Cleanroom Film & Bags, Fruth, Preferred Packaging, Bass Flexible Packaging and Intelligraphix.",
    metaChars: 133,
    images: [
      ["Cleanroom Film & Bags (cfb-c-p.webp)", "cfb-c-p", "Cleanroom Film & Bags, a C-P Flexible Packaging company", "55", "Replaces filename alt with the brand name."],
      ["Fruth (fruth-c-p.webp)", "fruth-c-p", "Fruth Custom Packaging, a C-P Flexible Packaging company", "56", "Brand name alt."],
      ["Preferred (preferred-packaging-c-p-300x200.webp)", "preferred-packaging-c-p-300x200", "Preferred Packaging, a C-P Flexible Packaging company", "53", "Brand name alt."],
      ["Bass (bass-flexible-packaging-c-p-300x200.webp)", "bass-flexible-packaging-c-p-300x200", "Bass Flexible Packaging, a C-P Flexible Packaging company", "57", "Brand name alt."],
      ["Intelligraphix (intelligraphix-300x200.webp)", "intelligraphix-300x200", "Intelligraphix Systems, a C-P Flexible Packaging company", "56", "Brand name alt."],
    ],
    notes: [
      "Corporate “about” page — no service/product schema applies.",
      "Replace the five filename-style alts with the actual company names for brand clarity and accessibility.",
    ],
  },
  {
    name: "Our Flexible Packaging Technology",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/flexible-packaging-technology",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212632377765",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-29 (title, meta, alt text, schema)",
    primary: "flexible packaging technology",
    secondary: "reclose, extended-gamut HD printing, die-cut window rollstock, Inno-Lok, laser scoring/perforating, coextrusion lamination, HPP lidstock",
    title: "Flexible Packaging Technology | Reclose, HD Print, Laser & More",
    titleChars: 63,
    meta: "Explore C-P's flexible packaging technology—reclose, extended-gamut HD print, die-cut windows, Inno-Lok®, laser scoring & HPP lidstock.",
    metaChars: 135,
    images: [
      ["Reclose (reclose-technology.webp)", "reclose-technology", "Reclose peel-and-reseal flexible packaging technology", "52", "Replaces filename alt."],
      ["Extended gamut (extended-gamut-2.webp)", "extended-gamut-2", "Extended-gamut HD flexographic printing technology", "50", "Replaces filename alt."],
      ["Die-cut window (laser-die-cut-window-technology.webp)", "laser-die-cut-window-technology", "Laser die-cut window rollstock technology", "41", "Replaces filename alt."],
      ["Inno-Lok (innolok-bag.webp)", "innolok-bag", "Inno-Lok pre-zippered resealable bag technology", "47", "Replaces filename alt."],
      ["Laser scoring (laser-scored-pouch.webp)", "laser-scored-pouch", "Laser-scored easy-open pouch technology", "39", "Replaces filename alt."],
      ["Coex lamination (coex-lamination.webp)", "coex-lamination", "Coextrusion lamination technology for barrier films", "51", "Replaces filename alt."],
      ["HPP lidstock (magic-sauce-hpp-lidstock.webp)", "magic-sauce-hpp-lidstock", "Proprietary HPP lidstock for high-pressure-processed foods", "58", "Replaces filename alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Flexible Packaging Technology",
  "serviceType": "Flexible packaging technology",
  "description": "Proprietary flexible packaging technologies including reclose, extended-gamut HD printing, die-cut window rollstock, Inno-Lok, laser scoring, coextrusion lamination, and HPP lidstock.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/flexible-packaging-technology"
}`,
    schemaType: "Service",
    schemaNote: "Capabilities/technology overview → Service.",
    notes: [
      "H1 is the generic “Technology” — change it to “Flexible Packaging Technology” for a stronger on-page signal.",
      "Seven technology images had filename-style alt; these double as internal links to the related product/capability pages.",
    ],
  },
  {
    name: "Our Flexible Packaging Products (Hub)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212637871214",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-06-29 (title, meta, alt text, schema)",
    primary: "flexible packaging products",
    secondary: "premade pouches, printed rollstock, roll-fed labels, shrink/stretch sleeves, cold-seal, functional bags, cleanroom",
    title: "Flexible Packaging Products | Pouches, Rollstock, Labels & Bags",
    titleChars: 63,
    meta: "Explore the full range of flexible packaging products—premade pouches, printed rollstock, labels, sleeves, cold-seal & bags. Talk to C-P Flexible Packaging.",
    metaChars: 156,
    images: [
      ["Premade pouches (premade-pouches-group.webp)", "premade-pouches-group", "Group of premade flexible pouches in multiple formats", "53", "Replaces filename alt."],
      ["Rollstock (rollstock-products-2.webp)", "rollstock-products-2", "Printed flexible packaging rollstock products", "45", "Replaces filename alt."],
      ["Roll-fed label (roll-fed-label-bottle.webp)", "roll-fed-label-bottle", "Roll-fed shrink sleeve label on a bottle", "40", "Replaces filename alt."],
      ["Shrink sleeves (shrink-sleeve-products.webp)", "shrink-sleeve-products", "Full-body printed shrink sleeve packaging products", "50", "Replaces filename alt."],
      ["Stretch sleeve (jug-with-stretch-sleeve.webp)", "jug-with-stretch-sleeve", "Stretch sleeve label applied to a plastic jug", "45", "Replaces filename alt."],
      ["Cold-seal (snack-bar-with-cold-seal-packaging.webp)", "snack-bar-with-cold-seal-packaging", "Snack bar in cold-seal flow-wrap packaging", "42", "Replaces filename alt."],
      ["Bags (bags-group.webp)", "bags-group", "Group of functional flexible packaging bags", "43", "Replaces filename alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Flexible Packaging Products",
  "description": "The full range of flexible packaging products: premade pouches and bags, printed rollstock, roll-fed labels, shrink and stretch sleeves, cold-seal, and cleanroom packaging.",
  "url": "https://gcpflexpack.com/products",
  "isPartOf": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "CollectionPage",
    schemaNote: "Products hub → CollectionPage.",
    notes: [
      "H1 is the generic “Products” — change it to “Flexible Packaging Products” for a stronger signal.",
      "Hub page — the seven category images had filename-style alt; reuse these alts on the matching category pages.",
    ],
  },
  {
    name: "Package Finishing & Pouch Converting",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/capabilities/finishing-slitting-flexible-packaging/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212722190259",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-07-08 (title, meta, alt text, schema)",
    primary: "package finishing, slitting, pouch converting",
    secondary: "shrink/stretch sleeve seaming, roll-fed labels, laser scoring, Inno-Lok integration, peel-and-reseal, quality control",
    title: "Package Finishing & Pouch Converting | Slitting & Reseal Integration",
    titleChars: 68,
    meta: "In-house finishing, slitting and pouch converting—plus reseal and sleeve integration—for finished flexible packaging. Talk to C-P Flexible Packaging.",
    metaChars: 149,
    images: [
      ["Finishing (capabilities-printing-1.webp)", "(empty)", "In-house finishing and slitting of flexible packaging rollstock", "62", "Fills empty alt with capability keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Package Finishing & Pouch Converting",
  "serviceType": "Package finishing and pouch converting",
  "description": "In-house finishing, slitting, and pouch converting including reseal and shrink/stretch sleeve integration to turn wide-web film into finished flexible packaging.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/capabilities/finishing-slitting-flexible-packaging/"
}`,
    schemaType: "Service",
    schemaNote: "Capability page → Service.",
    notes: [
      "H1 is the generic “Capabilities” — change it to “Package Finishing & Pouch Converting” for a stronger on-page signal.",
    ],
  },
  {
    name: "Paper Flexible Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/paper-flexible-packaging/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212870492281",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-07-08 (title, meta, alt text, schema)",
    primary: "paper flexible packaging",
    secondary: "GreenStream Paper, recyclable, compostable, plant-based, ASTM D6400/D6868, BPI, overwraps",
    title: "Paper Flexible Packaging | Recyclable & Compostable GreenStream™ Paper",
    titleChars: 70,
    meta: "Paper-based flexible packaging with an eco-conscious feel—recyclable and compostable GreenStream™ Paper structures. Talk to C-P Flexible Packaging.",
    metaChars: 146,
    images: [
      ["GreenStream paper (greenstream-paper-300x226.webp)", "greenstream-paper-300x226", "GreenStream paper-based flexible packaging", "42", "Replaces filename alt."],
      ["Recyclable (greenstream-paper-recyclable-1.webp)", "greenstream-paper-recyclable-1", "Recyclable GreenStream paper flexible packaging", "47", "Replaces filename alt."],
      ["Compostable (greenstream-paper-compostable.webp)", "(empty)", "Compostable GreenStream paper flexible packaging", "48", "Fills empty alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Paper Flexible Packaging",
  "serviceType": "Paper flexible packaging",
  "description": "Paper-based flexible packaging with an eco-conscious feel, including recyclable and compostable GreenStream Paper structures for sustainable brands.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/paper-flexible-packaging/"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "GreenStream™ is trademarked — keep the ™ in visible copy.",
      "Distinct from Paper Pouches (premade) — cross-link the two and keep their angles separate.",
    ],
  },
  {
    name: "Paper Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/paper-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212780726292",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-07-09 (title, meta, alt text, schema)",
    primary: "paper pouches",
    secondary: "GreenStream Paper, recyclable, compostable, PFAS-free, barrier options, form-fill-seal, circular economy",
    title: "Paper Pouches | Recyclable & Compostable GreenStream™ Paper Pouches",
    titleChars: 67,
    meta: "Recyclable and compostable paper pouches in GreenStream™ Paper—PFAS-free, barrier options for a circular economy. Talk to C-P Flexible Packaging.",
    metaChars: 145,
    images: [
      ["Paper pouch (greenstream-paper-300x226.webp)", "(empty)", "GreenStream recyclable paper pouch packaging", "44", "Fills empty alt."],
      ["Recycling chart (msw-recycling-by-material.webp)", "(empty)", "Municipal solid waste recycling rates by material chart", "55", "Describes the data graphic."],
      ["Recyclable (greenstream-paper-recyclable-1.webp)", "recyclable paper pouch being recycled", "Recyclable paper pouch entering the recycling stream", "52", "Tightens existing alt."],
      ["Compostable (greenstream-paper-compostable-768x512.webp)", "flexible paper packaging being composted", "Compostable paper pouch breaking down in composting", "51", "Clearer product phrasing."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Paper Pouches",
  "serviceType": "Paper pouches",
  "description": "Recyclable and compostable paper pouches in GreenStream Paper, PFAS-free with barrier options, designed for a circular economy.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/paper-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Premade pouch format. Distinct from Paper Flexible Packaging (rollstock) — cross-link and differentiate to avoid competing for the same term.",
      "Keep GreenStream™ trademark in copy.",
    ],
  },
  {
    name: "Peel & Reseal Rollstock",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/peel-reseal-rollstock",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212867484272",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-07-09 (title, meta, alt text, schema)",
    primary: "peel and reseal rollstock, reclose technology",
    secondary: "bottom-applied seamless reclose, consumer-friendly closures, cookie & snack packaging, tamper evidence, freshness",
    title: "Peel & Reseal Rollstock | Award-Winning Reclose Technology",
    titleChars: 58,
    meta: "Peel & reseal rollstock with seamless, bottom-applied reclose technology consumers love—ideal for cookies & snacks. Talk to C-P Flexible Packaging.",
    metaChars: 147,
    images: [
      ["Resealable (reclose-packaging-resealable.webp)", "(empty)", "Resealable peel-and-reseal flexible packaging with reclose feature", "65", "Primary keyword on lead image."],
      ["Snack pack (glossy-snack-pack-300x166.webp)", "Reclose packaging", "Glossy snack pack with peel-and-reseal reclose feature", "53", "Adds keyword to weak alt."],
      ["Application (IMG_2405-crop.webp)", "(empty)", "Peel-and-reseal cookie and snack packaging", "42", "Fills empty alt with application."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Peel & Reseal Rollstock",
  "serviceType": "Peel and reseal rollstock",
  "description": "Peel and reseal rollstock with seamless, bottom-applied reclose technology for consumer-friendly, resealable cookie and snack packaging with tamper evidence.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/peel-reseal-rollstock"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Reclose technology is the differentiator — cross-link to the Cookie & Bakery page, which shares the reclose theme.",
    ],
  },
  {
    name: "Pet Food Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets/pet-foods/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212702633299",
    audited: "2026-06-26",
    status: "IMPLEMENTED in HubSpot 2026-07-10 (title, meta, alt text, schema)",
    primary: "pet food packaging",
    secondary: "stand-up pouches, pet treats, resealable pouches, die-cut windows, VFFS/HFFS rollstock, matte/gloss, sustainable options",
    title: "Pet Food Packaging | Stand-Up Pouches for Food & Treats",
    titleChars: 55,
    meta: "Custom pet food packaging—stand-up pouches, resealable bags & rollstock for pet food and treats, with sustainable options. Talk to C-P Flexible Packaging.",
    metaChars: 154,
    images: [
      ["Pet food group (Pet-food-group-300x231.webp)", "(empty)", "Stand-up pouch packaging for pet food and treats", "48", "Primary keyword on lead image."],
      ["GreenStream pouch (greenstream-pouch-mockup-300x300.webp)", "(empty)", "GreenStream sustainable pouch for pet food packaging", "52", "Sustainability + market keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Pet Food Packaging",
  "serviceType": "Pet food flexible packaging",
  "description": "Custom pet food packaging including stand-up pouches, resealable bags, and rollstock for pet food and treats, with sustainable options.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/markets/pet-foods/"
}`,
    schemaType: "Service",
    schemaNote: "Market page → Service.",
    notes: [
      "Strong internal-link hub — links out to pouch formats, die-cut windows, matte/gloss, and sustainable options.",
    ],
  },
  {
    name: "Poly Bags",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/bags/poly-bags",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771053407",
    audited: "2026-07-10",
    status: "IMPLEMENTED in HubSpot 2026-07-13 (title, meta, alt text, schema)",
    primary: "poly bags, polyethylene bags",
    secondary: "custom printed bags, wicketed/gusseted/flip-top/die-cut handle formats, roll stock film, quality lab, retail & industrial",
    title: "Poly Bags | Custom-Printed Polyethylene Bags & Roll Stock",
    titleChars: 58,
    meta: "High-performance poly bags in every format—wicketed, gusseted, flip-top, die-cut handle & roll stock—custom printed. Talk to C-P Flexible Packaging.",
    metaChars: 148,
    images: [
      ["Shopper (poly-bags-food-1.webp)", "Woman shopping for food in poly bags at the store", "Shopper selecting food packaged in poly bags at a store", "54", "Tightens existing alt."],
      ["Product (c-p-poly-bags-768x512-1.webp)", "C-P Flexible Packaging Poly Bags", "Custom polyethylene poly bags by C-P Flexible Packaging", "54", "Adds material keyword."],
      ["Formats (poly-bag-types.webp)", "poly bag types - flip top, die-cut handle, header, wicketed, bottom gusset, bag on a roll", "Poly bag formats: flip-top, die-cut handle, wicketed, gusset & roll", "66", "Condenses long alt."],
      ["Crimp bottom (crimp-bottom-bags.webp)", "crimp bottom premade bags", "Crimp-bottom premade polyethylene bags", "38", "Adds material keyword."],
      ["Roll stock (Custom-Printed-Roll-Stock.webp)", "custom printed roll stock", "Custom-printed polyethylene roll stock film", "43", "Adds material keyword."],
      ["Pantone (Pantone-book.webp)", "(empty)", "Pantone color book for custom poly bag printing", "47", "Fills empty alt."],
      ["QA lab (Quality-Analysis-Lab.webp)", "c-p quality analysis lab", "C-P Flexible Packaging quality analysis lab", "43", "Expands brand abbreviation."],
      ["Manufacturing (poly-bag-dept.webp)", "poly bag supplier", "In-house poly bag manufacturing department", "42", "More descriptive than 'supplier'."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Poly Bags",
  "serviceType": "Poly bags",
  "description": "High-performance custom-printed polyethylene poly bags in every format—wicketed, gusseted, flip-top, die-cut handle, and roll stock—for retail and industrial use.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/bags/poly-bags"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Strong, keyword-rich page with eight content images — solid on-page foundation.",
    ],
  },
  {
    name: "Pouches With Handles",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/pouches-with-handles",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771039519",
    audited: "2026-07-10",
    status: "IMPLEMENTED in HubSpot 2026-07-13 (title, meta, alt text, schema)",
    primary: "pouches with handles",
    secondary: "stand-up pouches with handles, die-cut handles, ergonomic handles, pouring convenience, pet food & beverage packaging",
    title: "Pouches With Handles | Easy-Carry Stand-Up Pouch Packaging",
    titleChars: 58,
    meta: "Pouches with handles for easy carrying, pouring and dispensing—die-cut and ergonomic handles for beverages, pet food & more. Talk to C-P Flexible Packaging.",
    metaChars: 156,
    images: [
      ["Handle pouch (pouch-with-handle-blue-300x300.webp)", "Pouch with handle", "Stand-up pouch with a die-cut carry handle", "42", "Adds format + handle-type detail."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Pouches With Handles",
  "serviceType": "Pouches with handles",
  "description": "Flexible pouches with die-cut and ergonomic handles for easy carrying, pouring, and dispensing across beverages, pet food, detergents, and food service.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/pouches-with-handles"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Internally link to related pouch formats (stand-up, spouted, zipper). Handle convenience is the differentiator.",
    ],
  },
  {
    name: "Privacy Policy",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/privacy-policy",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212643322246",
    audited: "2026-07-15",
    status: "IMPLEMENTED in HubSpot 2026-07-24 (title, meta)",
    primary: "privacy policy",
    secondary: "California employee/applicant privacy notice, cookies policy, compliance",
    title: "Privacy Policy | C-P Flexible Packaging",
    titleChars: 39,
    meta: "C-P Flexible Packaging privacy policy, including California employee/applicant notice and our cookies policy.",
    metaChars: 109,
    images: [
      ["No page-specific content images (legal page)", "—", "No content images to optimize on this page", "0", "Focus is a clean title and meta."],
    ],
    notes: [
      "Legal/compliance page — no content images and no schema applies. Title and meta only.",
    ],
  },
  {
    name: "Recyclable Flexible Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/recyclable-flexible-packaging",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212867483399",
    audited: "2026-07-15",
    status: "IMPLEMENTED in HubSpot 2026-07-24 (title, meta, alt text, schema)",
    primary: "recyclable flexible packaging",
    secondary: "mono-material PE, post-consumer recycled (PCR) resin, PET 90–100% PCR, source reduction, renewable, compostable",
    title: "Recyclable Flexible Packaging | Mono-Material & PCR Films",
    titleChars: 57,
    meta: "Recyclable flexible packaging in mono-material PE and up to 100% post-consumer recycled (PCR) films. Talk to C-P Flexible Packaging today.",
    metaChars: 138,
    images: [
      ["Rollstock (recycable-rollstock-300x300.webp)", "(empty)", "Recyclable mono-material flexible packaging rollstock", "52", "Fills empty alt with primary keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Recyclable Flexible Packaging",
  "serviceType": "Recyclable flexible packaging",
  "description": "Recyclable flexible packaging built from mono-material polyethylene and post-consumer recycled (PCR) resins, including PET structures with up to 100% PCR content.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/recyclable-flexible-packaging"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Rollstock format. Distinct from Recyclable Pouches (premade) — cross-link the two and keep their angles separate.",
    ],
  },
  {
    name: "Recyclable Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/recyclable-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771038866",
    audited: "2026-07-15",
    status: "IMPLEMENTED in HubSpot 2026-07-24 (title, meta, alt text, schema)",
    primary: "recyclable pouches",
    secondary: "mono-material PE, PCR resin, compostable pouches, store drop-off recycling, source reduction, lightweighting",
    title: "Recyclable Pouches | Mono-Material & PCR Stand-Up Pouches",
    titleChars: 57,
    meta: "Recyclable pouches in mono-material PE and PCR resins—up to 60% less plastic than rigid containers. Talk to C-P Flexible Packaging today.",
    metaChars: 137,
    images: [
      ["Pouch mockup (recyclable-pouch-glossy-mockup-300x300.webp)", "Recyclable pouch", "Recyclable mono-material stand-up pouch with glossy finish", "57", "Adds material + format detail."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Recyclable Pouches",
  "serviceType": "Recyclable pouches",
  "description": "Recyclable stand-up pouches made from mono-material polyethylene and post-consumer recycled resins, using up to 60% less plastic than rigid containers.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/recyclable-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Premade pouch format. Distinct from Recyclable Flexible Packaging (rollstock) — cross-link and differentiate.",
      "Strong proof point: up to 60% less plastic than rigid containers — feature it prominently.",
    ],
  },
  {
    name: "Request a Consultation",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/request-a-consultation",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212915823435",
    audited: "2026-07-15",
    status: "IMPLEMENTED in HubSpot 2026-07-24 (title, meta)",
    primary: "request a consultation, flexible packaging consultation",
    secondary: "packaging supplier switch, delivery/quality/cost pain points, sustainable packaging, resealable packaging",
    title: "Request a Consultation | Flexible Packaging Experts",
    titleChars: 51,
    meta: "Struggling with delivery, quality or cost from your packaging supplier? Schedule a consultation with C-P Flexible Packaging.",
    metaChars: 124,
    images: [
      ["No page-specific content images (lead-gen landing page)", "—", "No content images to optimize on this page", "0", "Focus is the title, meta, and the consultation form."],
    ],
    notes: [
      "H1 is the sitewide CTA heading “Let's talk flexible packaging” — change it to “Request a Consultation” so the page has a distinct on-page signal.",
      "Lead-generation landing page — no schema applies. Keep the form and pain-point copy above the fold.",
    ],
  },
  {
    name: "Roll-Fed Labels",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/roll-fed-labels",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841399876",
    audited: "2026-07-15",
    status: "IMPLEMENTED in HubSpot 2026-07-24 (title, meta, alt text, schema)",
    primary: "roll-fed labels, wraparound labels",
    secondary: "360-degree labeling, film labels, no adhesive, extended gamut printing, beverage & household product labels",
    title: "Roll-Fed Labels | 360° Wraparound Film Labels for Bottles & Jars",
    titleChars: 64,
    meta: "Roll-fed wraparound labels deliver bold 360° branding without adhesive, with extended gamut printing. Talk to C-P Flexible Packaging today.",
    metaChars: 139,
    images: [
      ["Label roll (roll-fed-labels.webp)", "(empty)", "Roll-fed wraparound film labels on a production roll", "52", "Fills empty alt with primary keyword."],
      ["Bottle (roll-fed-label-bottle-300x300.webp)", "(empty)", "Roll-fed wraparound label applied to a beverage bottle", "54", "Adds application context."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Roll-Fed Labels",
  "serviceType": "Roll-fed wraparound labels",
  "description": "Roll-fed wraparound film labels delivering bold 360-degree branding without adhesive, printed with extended gamut for beverages and household products.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/roll-fed-labels"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Extended gamut printing and no-adhesive application are the differentiators — lead with both.",
    ],
  },
  {
    name: "Shrink Sleeves",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/shrink-sleeves",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841369512",
    audited: "2026-07-24",
    status: "IMPLEMENTED in HubSpot 2026-07-24 (title, meta, alt text, schema)",
    primary: "shrink sleeves, shrink sleeve labels",
    secondary: "360-degree labeling, PETG/OPS/PLA/PVC films, grid distortion testing, tamper-evident perforations, recyclable sleeves, HD flexographic",
    title: "Shrink Sleeves | 360° Shrink Sleeve Labels & Sustainable Options",
    titleChars: 64,
    meta: "Full-body shrink sleeves with distortion-tested graphics for maximum shelf impact—plus recyclable options. Talk to C-P Flexible Packaging today.",
    metaChars: 144,
    images: [
      ["Sleeve labels (shrink-sleeve-labels.webp)", "(empty)", "Printed shrink sleeve labels for containers", "43", "Fills empty alt with primary keyword."],
      ["Jar (jar-with-shrink-sleeve-300x300.webp)", "(empty)", "Jar with a full-body printed shrink sleeve label", "48", "Application + format."],
      ["Sprayer bottle (sprayer-bottle-shrink-sleeve-300x300.webp)", "sprayer-bottle-shrink-sleeve-300x300", "Sprayer bottle with a 360-degree shrink sleeve label", "52", "Replaces filename alt."],
      ["Markets (markets.webp)", "(empty)", "Shrink sleeve applications across product markets", "49", "Fills empty alt."],
      ["Sustainable sleeve (shrink-sleeve-greenstream-151x300.webp)", "(empty)", "Recyclable GreenStream shrink sleeve option", "43", "Sustainability keyword."],
      ["Icon (2705.svg)", "(empty)", "alt=”” (decorative)", "0", "Decorative icon — empty alt is correct."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Shrink Sleeves",
  "serviceType": "Shrink sleeve labels",
  "description": "Full-body 360-degree shrink sleeve labels with distortion-tested graphics, tamper-evident perforations, and recyclable film options for maximum shelf impact.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/shrink-sleeves"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Distortion-tested graphics and in-house production are the differentiators.",
      "Sustainable/recyclable sleeve options are a growing search angle — keep that section prominent.",
    ],
  },
  {
    name: "Slider Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/slider-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212770788657",
    audited: "2026-07-24",
    status: "IMPLEMENTED in HubSpot 2026-07-24 (title, meta, alt text, schema)",
    primary: "slider pouches, slider zipper pouches",
    secondary: "resealable packaging, low-profile/high-retention/child-resistant sliders, hooded sliders, tamper-evident, confectionery & pet food",
    title: "Slider Pouches | Resealable Slider Zipper Pouch Packaging",
    titleChars: 57,
    meta: "Slider pouches with easy-open, resealable zippers—low-profile, high-retention and child-resistant options. Talk to C-P Flexible Packaging today.",
    metaChars: 144,
    images: [
      ["Slider pouch (slider-fresh-lock-pouch-1-300x300.webp)", "Slider pouch", "Stand-up pouch with a resealable slider zipper", "46", "Adds format + closure detail."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Slider Pouches",
  "serviceType": "Slider zipper pouches",
  "description": "Stand-up pouches with user-friendly resealable slider zippers, including low-profile, high-retention, hooded, and child-resistant options.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/slider-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Slider variety (low-profile, high-retention, child-resistant) is the differentiator — internally link to related pouch formats.",
    ],
  },
  {
    name: "Snack Food Packaging",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/markets/snack-products/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212712018012",
    audited: "2026-07-24",
    status: "IMPLEMENTED in HubSpot 2026-07-27 (title, meta, alt text, schema)",
    primary: "snack food packaging",
    secondary: "pillow bags, flow wrap, stand-up pouches, rollstock, chips/crackers/nuts/bars, peel-and-reseal, Inno-Lok, cold-seal, sustainable options",
    title: "Snack Food Packaging | Pillow Bags, Flow Wrap & Stand-Up Pouches",
    titleChars: 64,
    meta: "Custom snack food packaging—pillow bags, flow wrap, stand-up pouches & rollstock with easy-open and resealable options. Talk to C-P Flexible Packaging.",
    metaChars: 151,
    images: [
      ["Snack formats (Pillow-bag-flow-wrap-standup-pouch.webp)", "Pillow bags, flow wrap packaging and stand-up pouches are popular choices for snack foods.", "Pillow bags, flow wrap and stand-up pouches for snack foods", "59", "Condenses an overly long alt."],
      ["Cold-seal (cold-seal-packaging-thumbnail-2-e1596174268592.webp)", "(empty)", "Cold-seal flow-wrap packaging for snack bars", "44", "Fills empty alt."],
      ["GreenStream pouch (greenstream-pouch-mockup-300x300.webp)", "(empty)", "GreenStream sustainable pouch for snack packaging", "49", "Sustainability + market keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Snack Food Packaging",
  "serviceType": "Snack food flexible packaging",
  "description": "Custom snack food packaging including pillow bags, flow wrap, stand-up pouches, and printed rollstock with easy-open, resealable, and sustainable options.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/markets/snack-products/"
}`,
    schemaType: "Service",
    schemaNote: "Market page → Service.",
    notes: [
      "Strong market page — internally link to the snack-relevant formats (pillow bags, flow wrap, cold-seal, Inno-Lok).",
    ],
  },
  {
    name: "Solar-Powered Manufacturing",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/sustainable-packaging/solar-powered-manufacturing",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212770977807",
    audited: "2026-07-24",
    status: "IMPLEMENTED in HubSpot 2026-07-27 (title, meta, alt text)",
    primary: "solar-powered manufacturing, renewable energy",
    secondary: "solar panels, carbon footprint reduction, cleanroom facility, kilowatt-hours, CO2 offset, sustainable manufacturing",
    title: "Solar-Powered Manufacturing | Renewable Energy at C-P",
    titleChars: 53,
    meta: "C-P Flexible Packaging powers its cleanroom facility with on-site solar, cutting carbon emissions and energy costs. Explore our sustainability commitment.",
    metaChars: 154,
    images: [
      ["Solar power (c-p-flexible-packaging-solar-power (1).webp)", "(empty)", "C-P Flexible Packaging solar power installation", "47", "Fills empty alt with brand + topic."],
      ["Panels (solar-panels-building.webp)", "Solar panels on top of cleanroom facility", "Solar panels on the roof of the cleanroom facility", "50", "Minor clarity improvement."],
    ],
    notes: [
      "Informational sustainability page — no service/product schema applies.",
      "Feature the solar output and CO2-offset figures prominently — strong trust and ESG signals.",
    ],
  },
  {
    name: "Spouted Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/spouted-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212770786167",
    audited: "2026-07-24",
    status: "IMPLEMENTED in HubSpot 2026-07-27 (title, meta, alt text, schema)",
    primary: "spouted pouches",
    secondary: "spout fitments, stand-up pouches, lightweight & portable, ecommerce packaging, beverages, health & beauty, household cleaners",
    title: "Spouted Pouches | Stand-Up Pouches With Spout Fitments",
    titleChars: 54,
    meta: "Spouted pouches deliver easy pouring and portability—a lightweight alternative to rigid bottles for beverages & more. Talk to C-P Flexible Packaging.",
    metaChars: 149,
    images: [
      ["Spouted pouch (spouted-pouch-blue-203x300.webp)", "Spouted pouch", "Stand-up spouted pouch with a screw-cap fitment", "47", "Adds format + fitment detail."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Spouted Pouches",
  "serviceType": "Spouted pouches",
  "description": "Stand-up spouted pouches with spout and cap fitments that deliver easy pouring, portability, and a lightweight alternative to rigid bottles.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/spouted-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Rigid-to-flexible migration is the key angle — lightweight, portable, and ecommerce-friendly.",
    ],
  },
  {
    name: "Stand-Up Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/stand-up-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771153725",
    audited: "2026-07-27",
    status: "IMPLEMENTED in HubSpot 2026-07-27 (title, meta, alt text, schema)",
    primary: "stand-up pouches",
    secondary: "custom/premade pouches, zipper/slider/spouted/flat-bottom pouches, recyclable & compostable options, K-seal gussets, ecommerce",
    title: "Stand-Up Pouches | Custom Premade Pouches With Any Closure",
    titleChars: 58,
    meta: "Custom stand-up pouches with any closure—zipper, slider, spout or resealable—plus recyclable & compostable options. Talk to C-P Flexible Packaging.",
    metaChars: 147,
    images: [
      ["Pouch group (standup-pouches-group-1.webp)", "Group shot of standup pouches", "Group of custom stand-up pouches in various sizes", "49", "Tightens existing alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Stand-Up Pouches",
  "serviceType": "Stand-up pouches",
  "description": "Custom stand-up pouches with any closure—zipper, slider, spout, or resealable—plus recyclable and compostable options for retail and ecommerce.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/stand-up-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Pouch-format hub — internally link to zipper, slider, spouted, flat-bottom, recyclable, and compostable pouch pages.",
    ],
  },
  {
    name: "EMI Static Shielding Bags",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/bags/emi-static-shielding-bags",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212770788262",
    audited: "2026-07-27",
    status: "IMPLEMENTED in HubSpot 2026-07-27 (title, meta, alt text, schema)",
    primary: "EMI static shielding bags, ESD protection",
    secondary: "electrostatic discharge, Faraday cage, electronic components packaging, custom manufacturing, ISO 9001:2015",
    title: "EMI Static Shielding Bags | ESD Protection for Electronics",
    titleChars: 58,
    meta: "EMI static shielding bags with multilayer Faraday-cage construction to protect electronics from static and ESD. Talk to C-P Flexible Packaging today.",
    metaChars: 149,
    images: [
      ["Chip bag (computer-chip-static-shielding-bag.webp)", "static shielding bag for electronics", "EMI static shielding bag protecting a computer chip", "51", "Adds specificity."],
      ["Bags (emi-static-shielding-bags.webp)", "emi static shielding pouches", "EMI static shielding bags for sensitive electronic components", "60", "Adds application keyword."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "EMI Static Shielding Bags",
  "serviceType": "EMI static shielding bags",
  "description": "EMI static shielding bags with multilayer Faraday-cage construction that protect sensitive electronic components from static electricity and electrostatic discharge.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/bags/emi-static-shielding-bags"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "ISO 9001:2015 and Faraday-cage construction are strong trust and technical signals.",
    ],
  },
  {
    name: "Stick Packs",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/stick-pack-film-laminations",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212851126309",
    audited: "2026-07-27",
    status: "IMPLEMENTED in HubSpot 2026-07-27 (title, meta, alt text, schema)",
    primary: "stick packs, stick pack film laminations",
    secondary: "single-serve packaging, VFFS, high-slip sealant, portable, puncture resistance, moisture/oxygen barrier",
    title: "Stick Packs | Single-Serve Stick Pack Film Laminations",
    titleChars: 54,
    meta: "Stick pack film laminations for single-serve, on-the-go portability—engineered for high-speed VFFS lines. Talk to C-P Flexible Packaging today.",
    metaChars: 143,
    images: [
      ["Lamination (stick-pack-lamination-300x122.webp)", "stick-pack-lamination-300x122", "Stick pack film lamination for single-serve packaging", "53", "Replaces filename alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Stick Packs",
  "serviceType": "Stick pack film laminations",
  "description": "Stick pack film laminations for single-serve, on-the-go packaging, engineered with high-slip sealants and barrier layers for high-speed VFFS lines.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/printed-rollstock/stick-pack-film-laminations"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Single-serve / VFFS angle is the differentiator — internally link to HFFS/VFFS rollstock.",
    ],
  },
  {
    name: "Stretch Sleeves",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/stretch-sleeves",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841802657",
    audited: "2026-07-27",
    status: "IMPLEMENTED in HubSpot 2026-07-28 (title, meta, alt text, schema)",
    primary: "stretch sleeves, recyclable labels",
    secondary: "360-degree branding, LLDPE, mono-material PE, C-FiT sleeves, bottle/jug labeling, tamper-evidence",
    title: "Stretch Sleeves | Recyclable 360° Mono-Material Labels",
    titleChars: 54,
    meta: "Recyclable stretch sleeves in mono-material PE for 360° branding on bottles and jugs—no adhesive, no distortion. Talk to C-P Flexible Packaging.",
    metaChars: 144,
    images: [
      ["Sleeves (stretch-sleeves.webp)", "(empty)", "Printed stretch sleeve labels for containers", "44", "Fills empty alt."],
      ["Jug (jug-with-stretch-sleeve-300x300.webp)", "(empty)", "Plastic jug with a full-body stretch sleeve label", "49", "Application context."],
      ["Sustainable (shrink-sleeve-greenstream-151x300.webp)", "(empty)", "Recyclable GreenStream stretch sleeve option", "44", "Sustainability keyword."],
      ["C-FiT (c-fit-sleeve-300x300.webp)", "(empty)", "C-FiT recyclable stretch sleeve label", "37", "Names the C-FiT product."],
      ["Propane (propane-tanks-mockup-2-300x300.webp)", "(empty)", "Stretch sleeve labels on propane tanks", "38", "Industrial application."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Stretch Sleeves",
  "serviceType": "Stretch sleeve labels",
  "description": "Recyclable stretch sleeves in mono-material polyethylene for 360-degree branding on bottles and jugs, with no adhesive and distortion-tested graphics.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/stretch-sleeves"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Mono-material recyclability and C-FiT™ are the differentiators — keep the ™ and cross-link to Shrink Sleeves.",
      "Five content images all had empty alt — a clean accessibility + SEO win.",
    ],
  },
  {
    name: "Sustainable Packaging (Hub)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/sustainable-packaging/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212770786443",
    audited: "2026-07-27",
    status: "IMPLEMENTED in HubSpot 2026-07-28 (title, meta, alt text, schema)",
    primary: "sustainable packaging",
    secondary: "recyclable, post-consumer recycled (PCR), compostable, renewable, source reduction, GreenStream, solar, internal recycling",
    title: "Sustainable Packaging | Recyclable, Compostable & PCR Solutions",
    titleChars: 63,
    meta: "Sustainable flexible packaging—recyclable, compostable, PCR and renewable materials, plus source reduction and GreenStream™. Talk to C-P Flexible Packaging.",
    metaChars: 156,
    images: [
      ["GreenStream pouch (greenstream-pouch-mockup-300x300.webp)", "(empty)", "GreenStream sustainable flexible packaging pouch", "48", "Fills empty alt."],
      ["Solar (solar-panels-building.webp)", "Solar panels on top of cleanroom facility", "Solar panels powering the cleanroom manufacturing facility", "57", "Minor clarity improvement."],
      ["Recycling (recycling-process-pe.webp)", "(empty)", "Internal PE recycling process at C-P Flexible Packaging", "54", "Fills empty alt with brand + topic."],
      ["Options (greenstream-sustainable-packaging-1.webp)", "sustainable packaging options", "GreenStream sustainable packaging options by C-P Flexible Packaging", "66", "Adds brand + sub-brand."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Sustainable Packaging",
  "description": "Sustainable flexible packaging solutions including recyclable, compostable, post-consumer recycled (PCR), and renewable materials, source reduction, GreenStream, solar-powered manufacturing, and internal recycling.",
  "url": "https://gcpflexpack.com/sustainable-packaging/",
  "isPartOf": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "CollectionPage",
    schemaNote: "Sustainability hub → CollectionPage.",
    notes: [
      "Top sustainability hub — links to GreenStream, Solar, Internal Recycling, Compostable, and Recyclable pages. Keep GreenStream™ trademark.",
    ],
  },
  {
    name: "Thermoformed Food Trays",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/thermoformed-food-trays/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212841799969",
    audited: "2026-07-27",
    status: "IMPLEMENTED in HubSpot 2026-07-28 (title, meta, alt text, schema)",
    primary: "thermoformed food trays",
    secondary: "retail food packaging, institutional meal trays, produce/meat trays, microwavable, Meals on Wheels, Preferred Packaging",
    title: "Thermoformed Food Trays | Retail & Institutional Meal Trays",
    titleChars: 59,
    meta: "Thermoformed food trays for retail produce and meat plus institutional meal service—custom-converted and microwavable. Talk to C-P Flexible Packaging.",
    metaChars: 150,
    images: [
      ["Food trays (food-trays.webp)", "(empty)", "Thermoformed food trays for retail and institutional use", "56", "Primary keyword on lead image."],
      ["Retail (retail-food-trays.webp)", "(empty)", "Thermoformed trays for retail produce and meat", "47", "Market keyword."],
      ["Institutional (institutional-food-trays-1.webp)", "(empty)", "Thermoformed meal trays for institutional food service", "54", "Market keyword."],
      ["Division logo (Preferred-Packaging-C-P-Logo-300x113.webp)", "(empty)", "Preferred Packaging, a C-P Flexible Packaging company", "53", "Names the division."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Thermoformed Food Trays",
  "serviceType": "Thermoformed food trays",
  "description": "Thermoformed food trays for retail produce and meat and for institutional meal service, custom-converted and available in microwavable formats.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/thermoformed-food-trays/"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Dual-market (retail + institutional). Preferred Packaging division heritage is a trust signal worth naming.",
    ],
  },
  {
    name: "Zipper Pouches",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/zipper-pouches",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212771156613",
    audited: "2026-07-27",
    status: "IMPLEMENTED in HubSpot 2026-07-28 (title, meta, alt text, schema)",
    primary: "zipper pouches, resealable packaging",
    secondary: "press-to-close, double-lock, child-resistant zippers, Powder-Zip, stand-up pouches, value-added features",
    title: "Zipper Pouches | Cost-Effective Resealable Premade Pouches",
    titleChars: 58,
    meta: "Zipper pouches with press-to-close, double-lock and child-resistant closures—the most cost-effective resealable pouch. Talk to C-P Flexible Packaging.",
    metaChars: 150,
    images: [
      ["Zipper pouch (glossy-zipper-pouch-300x300.webp)", "glossy-zipper-pouch", "Glossy resealable zipper pouch with press-to-close seal", "54", "Replaces filename alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Zipper Pouches",
  "serviceType": "Zipper pouches",
  "description": "Cost-effective resealable zipper pouches with press-to-close, double-lock, and child-resistant closures, plus value-added features like windows and handles.",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "url": "https://gcpflexpack.com/products/premade-pouches/zipper-pouches"
}`,
    schemaType: "Service",
    schemaNote: "Service schema.",
    notes: [
      "Cost-effective resealable is the hook. Keep the Powder-Zip™ mark and link to related pouch formats.",
    ],
  },
  {
    name: "Lakeville, MN Location",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/locations/lakeville-minnesota/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-07-28",
    primary: "Lakeville MN flexible packaging, C-P Flexible Packaging Lakeville",
    secondary: "Minnesota flexible packaging manufacturer, custom packaging, lidding, die-cut rollstock, premade pouches, local plant",
    h1Current: "C-P Lakeville",
    h1Rec: "C-P Flexible Packaging — Lakeville, MN",
    title: "Lakeville, MN Flexible Packaging Plant | C-P Flexible Packaging",
    titleChars: 63,
    meta: "Visit C-P Flexible Packaging's Lakeville, MN plant—custom flexible packaging, lidding, rollstock & pouches. 8235 220th St W. Call (800) 328-4556.",
    metaChars: 145,
    images: [
      ["Facility (lakeville.webp)", "lakeville", "C-P Flexible Packaging manufacturing plant in Lakeville, Minnesota", "64", "Adds brand + location context to a one-word alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "C-P Flexible Packaging — Lakeville",
  "url": "https://gcpflexpack.com/locations/lakeville-minnesota/",
  "telephone": "+1-800-328-4556",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "8235 220th Street West",
    "addressLocality": "Lakeville",
    "addressRegion": "MN",
    "postalCode": "55044",
    "addressCountry": "US"
  },
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "09:00",
    "closes": "17:00"
  }],
  "parentOrganization": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "LocalBusiness",
    schemaNote: "Location page → LocalBusiness schema with NAP (name, address, phone) and hours — different from the Service pattern used on product pages.",
    notes: [
      "H1 is a weak “C-P Lakeville” — change it to “C-P Flexible Packaging — Lakeville, MN” for a stronger local-search signal.",
      "Keep the NAP (8235 220th Street West, Lakeville, MN 55044 · (800) 328-4556) identical to the Google Business Profile for local SEO consistency.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "C-P York, PA Location",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/locations/c-p-york/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-07-28",
    primary: "York PA flexible packaging, C-P Flexible Packaging York",
    secondary: "Pennsylvania flexible packaging manufacturer, HD flexographic printing, premade pouches, lamination, laser scoring, SQF facility",
    h1Current: "C-P York",
    h1Rec: "C-P Flexible Packaging — York, PA",
    title: "York, PA Flexible Packaging Plant | C-P Flexible Packaging",
    titleChars: 57,
    meta: "C-P Flexible Packaging's York, PA facilities—240,000 sq ft of SQF-certified space for HD flexo printing, pouches & lamination. Call (800) 815-0667.",
    metaChars: 146,
    images: [
      ["Headquarters (1headquarters.webp)", "(none)", "C-P Flexible Packaging headquarters and manufacturing facility in York, Pennsylvania", "64", "Image currently has NO alt text — add descriptive alt with brand + location."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "C-P Flexible Packaging — York",
  "url": "https://gcpflexpack.com/locations/c-p-york/",
  "telephone": "+1-800-815-0667",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "15 Grumbacher Road",
    "addressLocality": "York",
    "addressRegion": "PA",
    "postalCode": "17406",
    "addressCountry": "US"
  },
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "09:00",
    "closes": "17:00"
  }],
  "parentOrganization": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "LocalBusiness",
    schemaNote: "Location page → LocalBusiness schema with NAP (name, address, phone) and hours — same pattern as the other plant pages.",
    notes: [
      "H1 is a weak “C-P York” — change it to “C-P Flexible Packaging — York, PA” for a stronger local-search signal.",
      "Headquarters image (1headquarters.webp) has NO alt text — creative team should add the recommended alt.",
      "Keep the NAP (15 Grumbacher Road, York, PA 17406 · (800) 815-0667) identical to the Google Business Profile for local SEO consistency.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Preferred Packaging (Norcross, GA) Location",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/locations/preferred-packaging/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-07-28",
    primary: "Preferred Packaging Norcross GA, C-P Flexible Packaging Georgia",
    secondary: "Georgia flexible packaging manufacturer, Atlanta metro packaging, custom pouches, local plant",
    h1Current: "Preferred Packaging",
    h1Rec: "Preferred Packaging — a C-P Flexible Packaging Company",
    title: "Preferred Packaging — Norcross, GA | C-P Flexible Packaging",
    titleChars: 58,
    meta: "Preferred Packaging, a C-P Flexible Packaging company in Norcross, GA—custom flexible packaging serving the Atlanta metro. Call (770) 416-0088.",
    metaChars: 140,
    images: [
      ["Facility (preferred-packaging.webp)", "preferred-packaging", "Preferred Packaging, a C-P Flexible Packaging facility in Norcross, Georgia", "74", "Replaces filename-style alt with descriptive brand + location text."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Preferred Packaging — a C-P Flexible Packaging Company",
  "url": "https://gcpflexpack.com/locations/preferred-packaging/",
  "telephone": "+1-770-416-0088",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "5903 Peachtree Industrial Blvd Ste. C",
    "addressLocality": "Norcross",
    "addressRegion": "GA",
    "postalCode": "30092",
    "addressCountry": "US"
  },
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "09:00",
    "closes": "17:00"
  }],
  "parentOrganization": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "LocalBusiness",
    schemaNote: "Location page → LocalBusiness schema with NAP and hours; models Preferred Packaging as a C-P company via parentOrganization.",
    notes: [
      "H1 is a generic “Preferred Packaging” — consider “Preferred Packaging — a C-P Flexible Packaging Company” to tie the sub-brand to the parent.",
      "Image alt is filename-style (“preferred-packaging”) — replace with the recommended descriptive alt.",
      "Keep the NAP (5903 Peachtree Industrial Blvd Ste. C, Norcross, GA 30092 · (770) 416-0088) identical to the Google Business Profile for local SEO consistency.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "C-P Buffalo, NY Location",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/locations/c-p-buffalo/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-07-30",
    primary: "Buffalo NY flexible packaging, C-P Flexible Packaging Buffalo",
    secondary: "New York flexible packaging manufacturer, HD flexographic printing, lamination, pouch converting, cold-seal, AIB certified",
    h1Current: "C-P Buffalo",
    h1Rec: "C-P Flexible Packaging — Buffalo, NY",
    title: "Buffalo, NY Flexible Packaging Plant | C-P Flexible Packaging",
    titleChars: 60,
    meta: "C-P Flexible Packaging's Buffalo, NY plant—AIB-certified 10-color HD flexo printing, lamination, pouches & cold-seal. Call (716) 825-7710.",
    metaChars: 135,
    images: [
      ["Facility (c-p-buffalo-location.webp)", "c-p-buffalo-location", "C-P Flexible Packaging manufacturing plant in Buffalo, New York", "61", "Replaces filename-style alt with descriptive brand + location text."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "C-P Flexible Packaging — Buffalo",
  "url": "https://gcpflexpack.com/locations/c-p-buffalo/",
  "telephone": "+1-716-825-7710",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "28 Wasson Street",
    "addressLocality": "Buffalo",
    "addressRegion": "NY",
    "postalCode": "14210",
    "addressCountry": "US"
  },
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "09:00",
    "closes": "17:00"
  }],
  "parentOrganization": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "LocalBusiness",
    schemaNote: "Location page → LocalBusiness schema with NAP and hours — same pattern as the other plant pages.",
    notes: [
      "H1 is a weak “C-P Buffalo” — change it to “C-P Flexible Packaging — Buffalo, NY” for a stronger local-search signal.",
      "Facility image (c-p-buffalo-location.webp) alt is filename-style — replace with the recommended descriptive alt.",
      "Keep the NAP (28 Wasson Street, Buffalo, NY 14210 · (716) 825-7710) identical to the Google Business Profile for local SEO consistency.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "C-P Aurora, ON Location (Canada)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/locations/aurora-ontario-canada/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-07-30",
    primary: "Aurora Ontario flexible packaging, C-P Flexible Packaging Canada",
    secondary: "Canadian flexible packaging manufacturer, Ontario packaging plant, custom rollstock, pouches, local plant",
    h1Current: "C-P Aurora",
    h1Rec: "C-P Flexible Packaging — Aurora, ON",
    title: "Aurora, ON Flexible Packaging Plant | C-P Flexible Packaging",
    titleChars: 59,
    meta: "C-P Flexible Packaging's Aurora, Ontario plant—custom printed flexible packaging, rollstock & pouches for Canadian brands. Call (800) 565-3407.",
    metaChars: 141,
    images: [
      ["Facility (c-p-aurora.webp)", "c-p-aurora", "C-P Flexible Packaging manufacturing plant in Aurora, Ontario, Canada", "68", "Replaces filename-style alt with descriptive brand + location text."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "C-P Flexible Packaging — Aurora",
  "url": "https://gcpflexpack.com/locations/aurora-ontario-canada/",
  "telephone": "+1-800-565-3407",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "285 Industrial Parkway South",
    "addressLocality": "Aurora",
    "addressRegion": "ON",
    "postalCode": "L4G 3V8",
    "addressCountry": "CA"
  },
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "09:00",
    "closes": "17:00"
  }],
  "parentOrganization": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "LocalBusiness",
    schemaNote: "Location page → LocalBusiness schema. Canadian facility — addressCountry is CA and postalCode uses the Canadian format.",
    notes: [
      "H1 is a weak “C-P Aurora” — change it to “C-P Flexible Packaging — Aurora, ON” for a stronger local-search signal.",
      "Only Canadian plant — worth its own Google Business Profile (Canada) and consistent NAP for local visibility in Ontario.",
      "Facility image (c-p-aurora.webp) alt is filename-style — replace with the recommended descriptive alt.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "C-P Bristol, PA Location",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/locations/c-p-bristol/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-07-30",
    primary: "Bristol PA flexible packaging, C-P Flexible Packaging Bristol",
    secondary: "Pennsylvania flexible packaging manufacturer, platemaking, printing, converting, finishing, SQF BRCGS certified",
    h1Current: "C-P Bristol",
    h1Rec: "C-P Flexible Packaging — Bristol, PA",
    title: "Bristol, PA Flexible Packaging Plant | C-P Flexible Packaging",
    titleChars: 60,
    meta: "C-P Flexible Packaging's Bristol, PA plant—125,000 sq ft of SQF-certified platemaking, printing, converting & finishing. Call (215) 860-7676.",
    metaChars: 140,
    images: [
      ["Facility (c-p-bristol.webp)", "(none)", "C-P Flexible Packaging manufacturing facility in Bristol, Pennsylvania", "68", "Image currently has NO alt text — add descriptive alt with brand + location."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "C-P Flexible Packaging — Bristol",
  "url": "https://gcpflexpack.com/locations/c-p-bristol/",
  "telephone": "+1-215-860-7676",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "181 Rittenhouse Circle",
    "addressLocality": "Bristol",
    "addressRegion": "PA",
    "postalCode": "19007",
    "addressCountry": "US"
  },
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "09:00",
    "closes": "17:00"
  }],
  "parentOrganization": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "LocalBusiness",
    schemaNote: "Location page → LocalBusiness schema with NAP and hours — same pattern as the other plant pages.",
    notes: [
      "H1 is a weak “C-P Bristol” — change it to “C-P Flexible Packaging — Bristol, PA” for a stronger local-search signal.",
      "Facility image (c-p-bristol.webp) has NO alt text — creative team should add the recommended alt.",
      "Keep the NAP (181 Rittenhouse Circle, Bristol, PA 19007 · (215) 860-7676) identical to the Google Business Profile for local SEO consistency.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "C-P Fond du Lac, WI Location",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/locations/fond-du-lac-wisconsin/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-07-30",
    primary: "Fond du Lac WI flexible packaging, C-P Flexible Packaging Wisconsin",
    secondary: "Wisconsin flexible packaging manufacturer, flexographic printing, laminating, custom slitting, prepress, SQF BRCGS certified",
    h1Current: "C-P Fond du Lac",
    h1Rec: "C-P Flexible Packaging — Fond du Lac, WI",
    title: "Fond du Lac, WI Flexible Packaging Plant | C-P Flexible Packaging",
    titleChars: 64,
    meta: "C-P Flexible Packaging's Fond du Lac, WI plant—10-color flexo printing, laminating, custom slitting & prepress. Call (920) 922-8212.",
    metaChars: 131,
    images: [
      ["Facility (c-p-fond-du-lac.webp)", "c-p-fond-du-lac", "C-P Flexible Packaging manufacturing plant in Fond du Lac, Wisconsin", "66", "Replaces filename-style alt with descriptive brand + location text."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "C-P Flexible Packaging — Fond du Lac",
  "url": "https://gcpflexpack.com/locations/fond-du-lac-wisconsin/",
  "telephone": "+1-920-922-8212",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "741 Morris Street",
    "addressLocality": "Fond du Lac",
    "addressRegion": "WI",
    "postalCode": "54936",
    "addressCountry": "US"
  },
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "09:00",
    "closes": "17:00"
  }],
  "parentOrganization": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "LocalBusiness",
    schemaNote: "Location page → LocalBusiness schema with NAP and hours — same pattern as the other plant pages.",
    notes: [
      "H1 is a weak “C-P Fond du Lac” — change it to “C-P Flexible Packaging — Fond du Lac, WI” for a stronger local-search signal.",
      "Facility image (c-p-fond-du-lac.webp) alt is filename-style — replace with the recommended descriptive alt.",
      "Keep the NAP (741 Morris Street, Fond du Lac, WI 54936 · (920) 922-8212) identical to the Google Business Profile for local SEO consistency.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Fruth Custom Packaging (Placentia, CA) Location",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/locations/placentia-california/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-07-30",
    primary: "Fruth Custom Packaging Placentia CA, C-P Flexible Packaging California",
    secondary: "California flexible packaging manufacturer, medical pharmaceutical electronics aerospace cleanroom, blown film extrusion, specialty bags",
    h1Current: "Fruth Custom Packaging",
    h1Rec: "Fruth Custom Packaging — a C-P Flexible Packaging Company",
    title: "Fruth Custom Packaging — Placentia, CA | C-P Flexible Packaging",
    titleChars: 62,
    meta: "Fruth Custom Packaging, a C-P Flexible Packaging company in Placentia, CA—blown film, flexo printing & specialty bags. Call (714) 993-9955.",
    metaChars: 137,
    images: [
      ["Building (fruth-building.webp)", "(none)", "Fruth Custom Packaging building, a C-P Flexible Packaging facility in Placentia, California", "89", "Image currently has NO alt text — add descriptive alt with brand + location."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Fruth Custom Packaging — a C-P Flexible Packaging Company",
  "url": "https://gcpflexpack.com/locations/placentia-california/",
  "telephone": "+1-714-993-9955",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "701 S. Richfield Road",
    "addressLocality": "Placentia",
    "addressRegion": "CA",
    "postalCode": "92870",
    "addressCountry": "US"
  },
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "09:00",
    "closes": "17:00"
  }],
  "parentOrganization": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  }
}`,
    schemaType: "LocalBusiness",
    schemaNote: "Location page → LocalBusiness schema; models Fruth as a C-P company via parentOrganization.",
    notes: [
      "H1 is a generic “Fruth Custom Packaging” — consider “Fruth Custom Packaging — a C-P Flexible Packaging Company” to tie the sub-brand to the parent.",
      "Building image (fruth-building.webp) has NO alt text — creative team should add the recommended alt.",
      "Keep the NAP (701 S. Richfield Rd., Placentia, CA 92870 · (714) 993-9955) identical to the Google Business Profile for local SEO consistency.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Extrusion & Adhesive Lamination (Capability)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/capabilities/extrusion-adhesive-lamination/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-08-14",
    status: "Review — pending implementation",
    primary: "extrusion lamination, adhesive lamination flexible packaging",
    secondary: "multilayer film lamination, solventless lamination, barrier films, downgauging, C-P Flexible Packaging capabilities",
    h1Current: "Extrusion Lamination and Adhesive Lamination",
    h1Rec: "Extrusion & Adhesive Lamination (keep — already keyword-rich; H1 is fine)",
    title: "Extrusion & Adhesive Lamination | C-P Flexible Packaging",
    titleChars: 56,
    meta: "C-P Flexible Packaging engineers multilayer films with extrusion & adhesive lamination—custom barrier, puncture resistance & downgauging. Learn more.",
    metaChars: 148,
    images: [
      ["Process diagram (technology-coex-lamination.webp)", "(none)", "Diagram of C-P Flexible Packaging's coextrusion and adhesive lamination process for multilayer films", "99", "Image currently has NO alt text — add descriptive alt."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "Service",
  "serviceType": "Extrusion & Adhesive Lamination",
  "provider": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/"
  },
  "areaServed": "US",
  "description": "Extrusion and adhesive lamination of multilayer flexible packaging films — engineered for barrier, puncture resistance, machinability and downgauging.",
  "url": "https://gcpflexpack.com/capabilities/extrusion-adhesive-lamination/"
}`,
    schemaType: "Service",
    schemaNote: "Capability page → Service schema. Completes the capabilities set (5/5).",
    notes: [
      "H1 is already strong (“Extrusion Lamination and Adhesive Lamination”) — no change needed; recommended title just tightens the browser-tab version.",
      "Process diagram (technology-coex-lamination.webp) has NO alt text — creative team should add the recommended alt.",
      "Internal-link this page from the product pages it enables (high-barrier, lidding, stick-pack laminations).",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Premade Pouches (Category Index)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/premade-pouches/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-08-14",
    status: "Review — pending implementation",
    primary: "premade pouches, flexible packaging pouches",
    secondary: "stand-up pouches, zipper pouches, spouted pouches, flat-bottom pouches, recyclable & compostable pouches",
    h1Current: "Printed rollstock",
    h1Rec: "Premade Pouches",
    title: "Premade Pouches | Stand-Up, Zipper & Spouted | C-P Flexible Packaging",
    titleChars: 68,
    meta: "Explore C-P Flexible Packaging's premade pouches—stand-up, zipper, slider, spouted, flat-bottom & more, in recyclable & compostable options.",
    metaChars: 138,
    images: [
      ["(No page-specific content images)", "—", "—", "—", "Category/index page — thumbnails are pulled from child product pages; alt text handled on those pages."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Premade Pouches",
  "url": "https://gcpflexpack.com/products/premade-pouches/",
  "isPartOf": { "@type": "WebSite", "name": "C-P Flexible Packaging", "url": "https://gcpflexpack.com/" },
  "about": "Premade flexible packaging pouches",
  "mainEntity": {
    "@type": "ItemList",
    "itemListElement": [
      {"@type":"ListItem","position":1,"name":"Stand-Up Pouches","url":"https://gcpflexpack.com/products/premade-pouches/stand-up-pouches"},
      {"@type":"ListItem","position":2,"name":"Zipper Pouches","url":"https://gcpflexpack.com/products/premade-pouches/zipper-pouches"},
      {"@type":"ListItem","position":3,"name":"Slider Pouches","url":"https://gcpflexpack.com/products/premade-pouches/slider-pouches"},
      {"@type":"ListItem","position":4,"name":"Laser-Scored Pouches","url":"https://gcpflexpack.com/products/premade-pouches/laser-scored-pouches-easy-open-packaging"},
      {"@type":"ListItem","position":5,"name":"Spouted Pouches","url":"https://gcpflexpack.com/products/premade-pouches/spouted-pouches"},
      {"@type":"ListItem","position":6,"name":"Flat-Bottom Pouches","url":"https://gcpflexpack.com/products/premade-pouches/flat-bottom-pouches"},
      {"@type":"ListItem","position":7,"name":"Pouches With Handles","url":"https://gcpflexpack.com/products/premade-pouches/pouches-with-handles"},
      {"@type":"ListItem","position":8,"name":"Matte & Gloss Pouches","url":"https://gcpflexpack.com/products/premade-pouches/matte-and-gloss-pouches"},
      {"@type":"ListItem","position":9,"name":"Paper Pouches","url":"https://gcpflexpack.com/products/premade-pouches/paper-pouches"},
      {"@type":"ListItem","position":10,"name":"Compostable Pouches","url":"https://gcpflexpack.com/products/premade-pouches/compostable-pouches"},
      {"@type":"ListItem","position":11,"name":"Recyclable Pouches","url":"https://gcpflexpack.com/products/premade-pouches/recyclable-pouches"}
    ]
  }
}`,
    schemaType: "CollectionPage",
    schemaNote: "Category/hub page → CollectionPage schema with an ItemList of the child pouch pages.",
    notes: [
      "⚠ CRITICAL: the on-page H1 currently reads “Printed rollstock” — a copy/paste error from the sibling page. Change it to “Premade Pouches”. This is the top-priority fix for this page.",
      "No page-specific content images to alt-text (thumbnails come from child pages).",
      "Strong internal-linking hub — ensure every child pouch page links back here and the anchor text is descriptive.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Printed Rollstock (Category Index)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/products/printed-rollstock/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-08-14",
    status: "Review — pending implementation",
    primary: "printed rollstock, flexible packaging rollstock",
    secondary: "high-barrier film, lidding films, peel-reseal rollstock, HFFS VFFS rollstock, shrink bands, recyclable films",
    h1Current: "Printed rollstock",
    h1Rec: "Printed Rollstock (title-case the heading)",
    title: "Printed Rollstock | Lidding, Barrier & More | C-P Flexible Packaging",
    titleChars: 67,
    meta: "Explore C-P Flexible Packaging's printed rollstock films—high-barrier, lidding, peel-reseal, shrink bands & recyclable options for form-fill-seal lines.",
    metaChars: 150,
    images: [
      ["(No page-specific content images)", "—", "—", "—", "Category/index page — thumbnails are pulled from child product pages; alt text handled on those pages."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Printed Rollstock",
  "url": "https://gcpflexpack.com/products/printed-rollstock/",
  "isPartOf": { "@type": "WebSite", "name": "C-P Flexible Packaging", "url": "https://gcpflexpack.com/" },
  "about": "Printed flexible packaging rollstock films",
  "mainEntity": {
    "@type": "ItemList",
    "itemListElement": [
      {"@type":"ListItem","position":1,"name":"High-Barrier Flexible Packaging","url":"https://gcpflexpack.com/products/printed-rollstock/high-barrier-flexible-packaging"},
      {"@type":"ListItem","position":2,"name":"Matte/Gloss Packaging","url":"https://gcpflexpack.com/products/printed-rollstock/matte-gloss-packaging"},
      {"@type":"ListItem","position":3,"name":"Compostable Flexible Packaging","url":"https://gcpflexpack.com/products/printed-rollstock/compostable-flexible-packaging"},
      {"@type":"ListItem","position":4,"name":"Recyclable Flexible Packaging","url":"https://gcpflexpack.com/products/printed-rollstock/recyclable-flexible-packaging"},
      {"@type":"ListItem","position":5,"name":"Stick Pack Film Laminations","url":"https://gcpflexpack.com/products/printed-rollstock/stick-pack-film-laminations"},
      {"@type":"ListItem","position":6,"name":"Lidding Films","url":"https://gcpflexpack.com/products/printed-rollstock/lidding-films"},
      {"@type":"ListItem","position":7,"name":"Peel & Reseal Rollstock","url":"https://gcpflexpack.com/products/printed-rollstock/peel-reseal-rollstock"},
      {"@type":"ListItem","position":8,"name":"Shrink Bands","url":"https://gcpflexpack.com/products/printed-rollstock/shrink-bands"},
      {"@type":"ListItem","position":9,"name":"HFFS & VFFS Rollstock","url":"https://gcpflexpack.com/products/printed-rollstock/hffs-vffs-rollstock"},
      {"@type":"ListItem","position":10,"name":"Inno-Lok Pre-Zippered Rollstock","url":"https://gcpflexpack.com/products/printed-rollstock/inno-lok-pre-zippered-rollstock"},
      {"@type":"ListItem","position":11,"name":"Laser Die-Cut Window Rollstock","url":"https://gcpflexpack.com/products/printed-rollstock/laser-die-cut-window-rollstock"},
      {"@type":"ListItem","position":12,"name":"Paper Flexible Packaging","url":"https://gcpflexpack.com/products/printed-rollstock/paper-flexible-packaging"},
      {"@type":"ListItem","position":13,"name":"High Burst Strength Flexible Packaging","url":"https://gcpflexpack.com/products/printed-rollstock/high-burst-strength-flexible-packaging"},
      {"@type":"ListItem","position":14,"name":"Child-Resistant Flexible Packaging","url":"https://gcpflexpack.com/products/printed-rollstock/child-resistant-flexible-packaging"}
    ]
  }
}`,
    schemaType: "CollectionPage",
    schemaNote: "Category/hub page → CollectionPage schema with an ItemList of the child rollstock pages.",
    notes: [
      "H1 “Printed rollstock” is correct but lowercase — title-case it to “Printed Rollstock” for consistency.",
      "No page-specific content images to alt-text (thumbnails come from child pages).",
      "Strong internal-linking hub — ensure every child rollstock page links back here with descriptive anchor text.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Home (Homepage)",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212643437655/content",
    audited: "2026-08-14",
    status: "IMPLEMENTED in HubSpot — verified live 2026-09-07 (title, meta, 9/10 alt text, schema); hero alt + logo alt still to correct",
    primary: "flexible packaging, custom flexible packaging & printing",
    secondary: "printed rollstock, premade pouches, labels, sustainable packaging, HD flexographic printing, food/medical/aerospace packaging",
    h1Current: "Inspired Packaging for Today's Lifestyles",
    h1Rec: "No change — homepage H1 tagline is fine (SEO carried by the title + Organization schema)",
    title: "Custom Flexible Packaging & Printing | C-P Flexible Packaging",
    titleChars: 60,
    meta: "C-P Flexible Packaging delivers custom printed rollstock, pouches, labels & sustainable packaging—60 years of HD flexo expertise. Explore our range.",
    metaChars: 146,
    images: [
      ["Hero — sustainability (sustainable-paper-packaging.webp)", "(verify)", "Sustainable paper-based flexible packaging from C-P Flexible Packaging", "69", "Descriptive, brand + topic."],
      ["Cleanroom (cleanroom-medical-packaging.webp)", "(verify)", "Medical and electronics cleanroom packaging by C-P Flexible Packaging", "68", "Brand + market."],
      ["Food (food-packaging.webp)", "(verify)", "Custom printed food packaging by C-P Flexible Packaging", "54", "Brand + market."],
      ["Recloseable (recloseable-packaging-market-circle.webp)", "(verify)", "Consumer-friendly recloseable flexible packaging from C-P Flexible Packaging", "75", "Brand + feature."],
      ["Health & wellness (health-wellness-shrink-sleeve.webp)", "(verify)", "Health and wellness product with a C-P Flexible Packaging shrink sleeve", "70", "Brand + product type."],
      ["Product portfolio (products-groupshot-1.webp)", "(verify)", "Group shot of C-P Flexible Packaging's flexible packaging product portfolio", "74", "Descriptive."],
      ["Process (concept-to-prototype.webp)", "(verify)", "C-P Flexible Packaging concept-to-prototype design process", "57", "Describes the graphic."],
      ["Sustainability (sustainability.webp)", "(verify)", "C-P Flexible Packaging sustainability and recycling commitment", "61", "Brand + topic."],
      ["Experience (six-decades-of-experience.webp)", "(verify)", "C-P Flexible Packaging — six decades of flexible packaging experience", "68", "Reinforces E-E-A-T."],
      ["Service (about-us.webp)", "(verify)", "C-P Flexible Packaging customer service and support team", "56", "Brand + topic."],
    ],
    schema: `{
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
}`,
    schemaType: "Organization",
    schemaNote: "Homepage → Organization schema (name, logo, founding, HQ, phone, LinkedIn). The single most important schema on the site.",
    notes: [
      "Update the logo URL in the schema to the real HubSpot-hosted logo file before publishing.",
      "10 content images need alt text — confirm current alt in HubSpot and apply the recommendations (several are likely filename-style).",
      "H1 tagline is fine; keyword relevance is carried by the title tag and Organization schema.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "About Us",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/about-us",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212656334125/content",
    audited: "2026-08-14",
    status: "IMPLEMENTED in HubSpot — verified live 2026-09-07 (title, meta, alt text, AboutPage schema)",
    primary: "about C-P Flexible Packaging, flexible packaging company",
    secondary: "flexible packaging converter, since 1958, top 25 US converter, SQF-certified, company history",
    h1Current: "We're your start-to-finish expert for all things flexible packaging",
    h1Rec: "No change — H1 is strong and descriptive",
    title: "About C-P Flexible Packaging | 60+ Years of Expertise",
    titleChars: 53,
    meta: "Since 1958, C-P Flexible Packaging has grown into a top-25 U.S. converter—800,000+ sq ft of SQF-certified space across nine facilities. Our story.",
    metaChars: 145,
    images: [
      ["About (about-image.webp)", "about-image", "C-P Flexible Packaging team and York, PA headquarters", "53", "Replaces filename-style alt with descriptive text."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "AboutPage",
  "name": "About C-P Flexible Packaging",
  "url": "https://gcpflexpack.com/about-us",
  "mainEntity": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "url": "https://gcpflexpack.com/",
    "foundingDate": "1958",
    "description": "Top-25 U.S. flexible packaging converter with 800,000+ sq ft of SQF-certified space across nine facilities."
  }
}`,
    schemaType: "AboutPage",
    schemaNote: "About page → AboutPage schema wrapping the Organization (founding 1958, scale). Complements the homepage Organization block.",
    notes: [
      "Strong E-E-A-T page — the 1958 founding story, top-25 ranking and 800,000+ sq ft are trust signals; keep them prominent.",
      "About image (about-image.webp) alt is filename-style — replace with the recommended descriptive alt.",
    ],
  },
  {
    name: "Contact Us",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/contact/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212726038965/content",
    audited: "2026-08-14",
    status: "IMPLEMENTED in HubSpot — verified live 2026-09-07 (title, meta, H1, schema)",
    primary: "contact C-P Flexible Packaging, flexible packaging contact",
    secondary: "flexible packaging locations, York PA headquarters, request a quote, packaging experts",
    h1Current: "Contact us",
    h1Rec: "Contact C-P Flexible Packaging",
    title: "Contact C-P Flexible Packaging | 9 Plant Locations",
    titleChars: 50,
    meta: "Contact C-P Flexible Packaging—9 manufacturing locations across the US & Canada. Call (800) 815-0667 or message our packaging experts online.",
    metaChars: 139,
    images: [
      ["(No page-specific content images)", "—", "—", "—", "Contact page — no content images to alt-text."],
    ],
    schema: `{
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
}`,
    schemaType: "ContactPage",
    schemaNote: "Contact page → ContactPage schema wrapping the Organization contact point.",
    notes: [
      "H1 “Contact us” is generic — change to “Contact C-P Flexible Packaging” for a branded, keyword-bearing heading.",
      "NAP QA opportunity: this page lists all 9 locations — verify every address/phone matches the individual location pages AND each Google Business Profile exactly.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Premade Pouches Glossary",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/glossary/pouch/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212725949338/settings",
    audited: "2026-08-14",
    status: "IMPLEMENTED in HubSpot 2026-09-07 (title, meta, H1, DefinedTermSet schema)",
    primary: "premade pouches glossary, pouch packaging terms",
    secondary: "pouch types, Doyen seal, K-seal, press-to-close zipper, laser scoring, burst testing, flexible packaging terminology",
    h1Current: "Premade pouches glossary",
    h1Rec: "Premade Pouches Glossary (title-case the heading)",
    title: "Premade Pouches Glossary | C-P Flexible Packaging",
    titleChars: 48,
    meta: "A–Z glossary of premade pouch terms from C-P Flexible Packaging—pouch types, seals, zippers, laser scoring & testing. Learn the terminology.",
    metaChars: 139,
    images: [
      ["(No page-specific content images)", "—", "—", "—", "Glossary page — text definitions only."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "DefinedTermSet",
  "name": "Premade Pouches Glossary",
  "url": "https://gcpflexpack.com/glossary/pouch/",
  "description": "Definitions of premade pouch and flexible packaging terms — pouch types, seals, zippers, laser scoring and testing methods.",
  "publisher": { "@type": "Organization", "name": "C-P Flexible Packaging", "url": "https://gcpflexpack.com/" }
}`,
    schemaType: "DefinedTermSet",
    schemaNote: "Glossary → DefinedTermSet schema. ~40 defined terms — strong topical-authority asset; internal-link entries to the matching product pages.",
    notes: [
      "H1 is fine but lowercase — title-case to “Premade Pouches Glossary”.",
      "High topical-authority page — link each glossary entry to its related product page (e.g. spouted pouches, laser-scored pouches).",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Flexible Packaging Films Glossary",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/glossary/film/",
    editorUrl: "https://app.hubspot.com/pages/24024882/",
    audited: "2026-08-14",
    status: "Review — pending implementation",
    primary: "flexible packaging films glossary, packaging film terms",
    secondary: "polyethylene, polyester, nylon, OPP, extrusion lamination, HFFS, VFFS, gauge, seal bond, coefficient of friction",
    h1Current: "Flexible packaging films glossary",
    h1Rec: "Flexible Packaging Films Glossary (title-case the heading)",
    title: "Flexible Packaging Films Glossary | C-P Flexible Packaging",
    titleChars: 57,
    meta: "A–Z glossary of flexible packaging film terms from C-P Flexible Packaging—film types, lamination, HFFS/VFFS, gauge & seal bond. Learn the terms.",
    metaChars: 141,
    images: [
      ["(No page-specific content images)", "—", "—", "—", "Glossary page — text definitions only."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "DefinedTermSet",
  "name": "Flexible Packaging Films Glossary",
  "url": "https://gcpflexpack.com/glossary/film/",
  "description": "Definitions of flexible packaging film terms — film types, lamination, form-fill-seal equipment and quality measurements.",
  "publisher": { "@type": "Organization", "name": "C-P Flexible Packaging", "url": "https://gcpflexpack.com/" }
}`,
    schemaType: "DefinedTermSet",
    schemaNote: "Glossary → DefinedTermSet schema. Pairs with the pouch glossary as a topical-authority hub.",
    notes: [
      "Current title has no brand and is lowercase — use the recommended title-cased, branded version.",
      "H1 lowercase — title-case to “Flexible Packaging Films Glossary”.",
      "Cross-link film terms to the rollstock/lamination pages they relate to.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Flexible Packaging Calculator",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/flexible-packaging-calculator/",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212796891913/settings",
    audited: "2026-08-14",
    status: "IMPLEMENTED in HubSpot — verified live 2026-09-07 (title, meta, H1, WebApplication schema)",
    primary: "flexible packaging calculator, packaging conversions",
    secondary: "MSI to square meters, basis weight, yield, microns to mils, roll length, packaging unit conversions",
    h1Current: "Flexible packaging calculator",
    h1Rec: "Flexible Packaging Calculator (title-case the heading)",
    title: "Flexible Packaging Calculator | C-P Flexible Packaging",
    titleChars: 53,
    meta: "Free flexible packaging calculator from C-P Flexible Packaging—convert MSI, basis weight, yield, microns to mils & more. Try the tool.",
    metaChars: 132,
    images: [
      ["(No page-specific content images)", "—", "—", "—", "Interactive tool — no content images."],
    ],
    schema: `{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "Flexible Packaging Calculator",
  "url": "https://gcpflexpack.com/flexible-packaging-calculator/",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web",
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD" },
  "publisher": { "@type": "Organization", "name": "C-P Flexible Packaging", "url": "https://gcpflexpack.com/" }
}`,
    schemaType: "WebApplication",
    schemaNote: "Interactive tool → WebApplication schema (free BusinessApplication). Good linkable asset — promote from product/rollstock pages.",
    notes: [
      "H1 lowercase — title-case to “Flexible Packaging Calculator”.",
      "Strong linkable asset — earn links by promoting it from product pages and the learning center.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
  {
    name: "Careers",
    liveUrl: "https://gcpflexpack-24024882.hs-sites.com/careers",
    editorUrl: "https://app.hubspot.com/pages/24024882/editor/212650470796/settings",
    audited: "2026-08-14",
    status: "IMPLEMENTED in HubSpot — verified live 2026-09-07 (title, meta, 10/12 alt text); 2 filename alts remain",
    primary: "C-P Flexible Packaging careers, flexible packaging jobs",
    secondary: "packaging manufacturing jobs, careers York PA Buffalo Bristol, benefits, company culture",
    h1Current: "Come add your value with a career at C-P Flexible Packaging",
    h1Rec: "No change — H1 is strong and on-brand",
    title: "Careers at C-P Flexible Packaging | Join Our Team",
    titleChars: 49,
    meta: "Build your career at C-P Flexible Packaging—9 U.S. & Canada locations, medical/dental/401(k) match & a family-first culture. View openings.",
    metaChars: 138,
    images: [
      ["Careers hero (career-in-packaging.webp)", "(none)", "Team members building a career in flexible packaging at C-P Flexible Packaging", "77", "Add descriptive alt — image currently has none."],
      ["Working at C-P (working-at-c-p-thumbnail-300x161.webp)", "(none)", "Employees working at a C-P Flexible Packaging manufacturing facility", "67", "Add descriptive alt."],
      ["Careers (careers.webp)", "(none)", "Careers and workplace culture at C-P Flexible Packaging", "55", "Add descriptive alt."],
      ["Facility card — York (c-p-york-*.webp)", "c-p-york-300x172", "C-P Flexible Packaging facility in York, Pennsylvania", "52", "Replaces filename-style alt on the location Flexi Card."],
      ["Facility card — Buffalo (c-p-buffalo-*.webp)", "c-p-buffalo-*", "C-P Flexible Packaging facility in Buffalo, New York", "51", "Replaces filename-style alt."],
      ["Facility card — Bristol (c-p-bristol-*.webp)", "c-p-bristol-*", "C-P Flexible Packaging facility in Bristol, Pennsylvania", "55", "Replaces filename-style alt."],
      ["Facility card — Lakeville (c-p-lakeville-*.webp)", "c-p-lakeville-*", "C-P Flexible Packaging facility in Lakeville, Minnesota", "54", "Replaces filename-style alt."],
      ["Facility card — Aurora (c-p-aurora-*.webp)", "c-p-aurora-*", "C-P Flexible Packaging facility in Aurora, Ontario, Canada", "57", "Replaces filename-style alt."],
      ["Facility card — Fond du Lac (c-p-fond-du-lac-*.webp)", "c-p-fond-du-lac-*", "C-P Flexible Packaging facility in Fond du Lac, Wisconsin", "56", "Replaces filename-style alt."],
      ["Facility card — Fruth (fruth-*.webp)", "fruth-*", "Fruth Custom Packaging facility in Placentia, California", "56", "Replaces filename-style alt."],
      ["Facility card — Cleanroom F&B (cleanroom-*.webp)", "cleanroom-*", "Cleanroom Film & Bags facility in Placentia, California", "55", "Replaces filename-style alt."],
      ["Facility card — Preferred (preferred-*.webp)", "preferred-*", "Preferred Packaging facility in Norcross, Georgia", "49", "Replaces filename-style alt."],
    ],
    schemaType: "N/A",
    schemaNote: "No schema now — add JobPosting schema per role only when specific openings are listed on-page (JobPosting requires real job data).",
    notes: [
      "H1 is strong and on-brand — no change needed.",
      "Three content images have NO alt text — creative team should add the recommended alts (Web Graphics / AISEO).",
      "Add JobPosting schema later, per opening, if/when specific roles are posted on the page.",
      "Editor link is a placeholder (HubSpot pages dashboard) — swap in the specific page's editor URL when available.",
    ],
  },
];

// ---------------------------------------------------------------------------
// Rendering helpers
// ---------------------------------------------------------------------------
const COLW = [1700, 1500, 2400, 700, 3060];
const cellBorder = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: cellBorder, bottom: cellBorder, left: cellBorder, right: cellBorder };

function hCell(text, w) {
  return new TableCell({
    borders, width: { size: w, type: WidthType.DXA },
    shading: { fill: BRAND, type: ShadingType.CLEAR },
    margins: { top: 60, bottom: 60, left: 100, right: 100 },
    verticalAlign: VerticalAlign.CENTER,
    children: [new Paragraph({ children: [new TextRun({ text, bold: true, color: HDRTEXT, size: 17 })] })],
  });
}
function bCell(text, w, fill) {
  return new TableCell({
    borders, width: { size: w, type: WidthType.DXA },
    shading: fill ? { fill, type: ShadingType.CLEAR } : undefined,
    margins: { top: 50, bottom: 50, left: 100, right: 100 },
    verticalAlign: VerticalAlign.CENTER,
    children: [new Paragraph({ children: [new TextRun({ text, size: 17 })] })],
  });
}
function imageTable(images, col1Header) {
  const head = new TableRow({
    tableHeader: true,
    children: [col1Header, "Current Alt", "Suggested Alt Text", "Chars", "Rationale & SEO Benefit"]
      .map((t, i) => hCell(t, COLW[i])),
  });
  const rows = images.map((r, idx) => {
    const fill = idx % 2 ? ZEBRA : undefined;
    return new TableRow({ children: r.map((c, i) => bCell(c, COLW[i], fill)) });
  });
  return new Table({ width: { size: 9360, type: WidthType.DXA }, columnWidths: COLW, rows: [head, ...rows] });
}
function label(text) {
  return new Paragraph({ spacing: { before: 160, after: 40 }, children: [new TextRun({ text, bold: true, color: BRAND })] });
}
function mono(text) {
  return new Paragraph({ spacing: { after: 60 }, shading: { fill: "F4F6F4", type: ShadingType.CLEAR },
    children: [new TextRun({ text, font: "Consolas", size: 19 })] });
}
function codeBlock(text) {
  return text.split("\n").map(line => new Paragraph({
    spacing: { after: 0 }, shading: { fill: "F4F6F4", type: ShadingType.CLEAR },
    children: [new TextRun({ text: line || " ", font: "Consolas", size: 17 })],
  }));
}

// ---------------------------------------------------------------------------
const today = new Date().toISOString().slice(0, 10);
const body = [];

// Cover
body.push(new Paragraph({ spacing: { before: 1200, after: 0 }, alignment: AlignmentType.CENTER,
  children: [new TextRun({ text: "C-P Flexible Packaging", bold: true, size: 56, color: BRAND })] }));
body.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 120 },
  children: [new TextRun({ text: "On-Page SEO Audit — Running Log", size: 32 })] }));
body.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 40 },
  children: [new TextRun({ text: "Image alt text, page titles, meta descriptions & schema", italics: true, size: 22, color: "666666" })] }));
body.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 40 },
  children: [new TextRun({ text: `Last updated: ${today}   •   Pages audited: ${AUDITS.length}`, size: 20, color: "666666" })] }));

// Standard tips
body.push(new Paragraph({ heading: HeadingLevel.HEADING_2, pageBreakBefore: true,
  children: [new TextRun("Standard HubSpot Image SEO Guidelines")] }));
[
  "Rename image files to keyword-friendly slugs before upload — HubSpot uses filenames as a ranking signal.",
  "Keep WebP format and enable lazy loading on below-the-fold images for Core Web Vitals.",
  "Set explicit width/height to prevent layout shift (CLS).",
  "Enter alt text in HubSpot’s image-module alt field, not hard-coded HTML, so it survives template changes.",
  "Alt text: 50–125 characters, descriptive, natural, one relevant keyword — never keyword-stuffed.",
  "Use alt=”” for purely decorative icons (e.g., phone icons beside visible numbers).",
].forEach(t => body.push(new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 40 }, children: [new TextRun(t)] })));

// GLOBAL / sitewide elements (once)
body.push(new Paragraph({ heading: HeadingLevel.HEADING_2, spacing: { before: 240 },
  children: [new TextRun("Global / Sitewide Elements (apply once)")] }));
body.push(new Paragraph({ spacing: { after: 80 }, children: [new TextRun({
  text: "These elements repeat on every page (logo, GreenStream block, phone icon, footer certification badges, favicon). Set their alt text once and apply sitewide — they are not repeated in the per-page tables below.",
  italics: true, color: "555555" })] }));
body.push(imageTable(GLOBAL, "Sitewide Element"));

// Per-page sections
AUDITS.forEach((a) => {
  body.push(new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true, children: [new TextRun(a.name)] }));
  if (a.status) body.push(new Paragraph({ spacing: { after: 40 },
    children: [new TextRun({ text: a.status, bold: true, color: BRAND })] }));
  body.push(new Paragraph({ spacing: { after: 20 }, children: [
    new TextRun({ text: "Live URL: ", bold: true }),
    new ExternalHyperlink({ link: a.liveUrl, children: [new TextRun({ text: a.liveUrl, style: "Hyperlink", size: 18 })] }) ]}));
  body.push(new Paragraph({ spacing: { after: 20 }, children: [
    new TextRun({ text: "HubSpot editor: ", bold: true }),
    new ExternalHyperlink({ link: a.editorUrl, children: [new TextRun({ text: a.editorUrl, style: "Hyperlink", size: 18 })] }) ]}));
  body.push(new Paragraph({ spacing: { after: 20 }, children: [
    new TextRun({ text: "Audited: ", bold: true }), new TextRun({ text: a.audited }),
    new TextRun({ text: "      Primary keyword: ", bold: true }), new TextRun({ text: a.primary }) ]}));
  body.push(new Paragraph({ spacing: { after: 40 }, children: [
    new TextRun({ text: "Secondary keywords: ", bold: true }), new TextRun({ text: a.secondary }) ]}));

  body.push(label(`Recommended Page Title (${a.titleChars} chars)`));
  body.push(mono(a.title));
  body.push(label(`Recommended Meta Description (${a.metaChars} chars)`));
  body.push(mono(a.meta));

  if (a.h1Rec) {
    body.push(label("Recommended H1 (on-page heading)"));
    body.push(new Paragraph({ spacing: { after: 80 }, children: [
      new TextRun({ text: "Current: ", bold: true }),
      new TextRun({ text: a.h1Current || "—", strike: true, color: "B00000" }),
      new TextRun({ text: "     →     Update to: ", bold: true }),
      new TextRun({ text: a.h1Rec, bold: true, color: BRAND }),
    ]}));
  }

  body.push(label("Image + Alt Text Audit (page-specific content images)"));
  body.push(imageTable(a.images, "Image & Context"));

  if (a.schema) {
    body.push(label(`Recommended Structured Data (${a.schemaType || "Service"} schema)`));
    codeBlock('<script type="application/ld+json">\n' + a.schema + '\n</script>').forEach(p => body.push(p));
  }

  if (a.notes && a.notes.length) {
    body.push(label("Notes & Follow-ups"));
    a.notes.forEach(n => body.push(new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 30 }, children: [new TextRun(n)] })));
  }
});

// ---------------------------------------------------------------------------
const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 32, bold: true, font: "Arial", color: BRAND },
        paragraph: { spacing: { before: 120, after: 160 }, outlineLevel: 0,
          border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: BRAND, space: 4 } } } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 26, bold: true, font: "Arial", color: BRAND },
        paragraph: { spacing: { before: 120, after: 120 }, outlineLevel: 1 } },
    ],
  },
  numbering: { config: [ { reference: "bullets", levels: [{ level: 0, format: LevelFormat.BULLET, text: "•",
    alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 540, hanging: 280 } } } }] } ] },
  sections: [{
    properties: { page: { size: { width: 12240, height: 15840 }, margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 } } },
    headers: { default: new Header({ children: [new Paragraph({ alignment: AlignmentType.RIGHT,
      children: [new TextRun({ text: "C-P Flexible Packaging — SEO Audit Running Log", size: 16, color: "999999" })] })] }) },
    footers: { default: new Footer({ children: [new Paragraph({ alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: "Page ", size: 16, color: "999999" }), new TextRun({ children: [PageNumber.CURRENT], size: 16, color: "999999" })] })] }) },
    children: body,
  }],
});

const out = process.argv[2] || "C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Garlock/Garlock-On-Page-SEO-Audit-Running-Log.docx";
Packer.toBuffer(doc).then(b => { fs.writeFileSync(out, b); console.log("WROTE " + out + " (" + AUDITS.length + " page[s] + global section)"); });
