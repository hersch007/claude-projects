const {
  Document, Packer, Paragraph, TextRun,
  HeadingLevel, AlignmentType, BorderStyle, WidthType, ShadingType,
  LevelFormat, PageNumber, Header, Footer
} = require("docx");
const fs = require("fs");

// ── BRAND COLORS ───────────────────────────────────────────────────────────
const FRUTH_RED  = "C41230";
const YELLOW     = "FFFF00";
const DARK_BLUE  = "1F4E79";
const MID_BLUE   = "2E75B6";
const BLACK      = "000000";
const GRAY_BG    = "555555";
const CODE_BG    = "F4F4F4";

let _pageUrl = null; // set by pageHeader(), consumed by makeDoc()

// ── IMPLEMENTATION TRACKER ─────────────────────────────────────────────────
// Add date (MM/DD/YYYY) when a page is implemented in HubSpot
const implemented = {
  "https://www.fruth.com/products/bags/autoclave-bags":  "07/07/2026",
  "https://www.fruth.com/products/bags/bakery-bags":     "07/07/2026",
  "https://www.fruth.com/products/bags":                         "07/07/2026",
  "https://www.fruth.com/products/films/black-conductive-film":  "07/07/2026",
  "https://www.fruth.com/products/bags/bottom-seal-bags":        "07/07/2026",
  "https://www.fruth.com/products/bags/cleanroom-bags":              "07/07/2026",
  "https://www.fruth.com/products/barrier-films/ffp-barrier-film":  "07/08/2026",
  "https://www.fruth.com/products/bags/foam-bags":                   "07/08/2026",
  "https://www.fruth.com/products/bags/fresh-produce-bags":          "07/08/2026",
  "https://www.fruth.com/products/bags/grow-bags":                   "07/08/2026",
  "https://www.fruth.com/products/bags/gusset-bags":                "07/08/2026",
  "https://www.fruth.com/products/bags/header-bags":               "07/08/2026",
  "https://www.fruth.com/products/barrier-films/kraft-foil-barrier-film": "07/13/2026",
  "https://www.fruth.com/products/bags/lay-flat-bags":             "07/13/2026",
};

// ── SHARED DOC FACTORY ─────────────────────────────────────────────────────
function makeDoc(pageTitle, children) {
  const url = _pageUrl;
  _pageUrl = null;
  const allChildren = [...children, ...schemaSection(url)];
  return new Document({
    numbering: {
      config: [{
        reference: "bullets",
        levels: [{ level: 0, format: LevelFormat.BULLET, text: "•", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } }]
      }]
    },
    styles: {
      default: { document: { run: { font: "Arial", size: 22 } } },
      paragraphStyles: [
        { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
          run: { size: 32, bold: true, font: "Arial", color: BLACK },
          paragraph: { spacing: { before: 240, after: 60 }, outlineLevel: 0 } },
        { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
          run: { size: 26, bold: true, font: "Arial", color: MID_BLUE },
          paragraph: { spacing: { before: 200, after: 100 }, outlineLevel: 1 } },
        { id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickFormat: true,
          run: { size: 22, bold: true, font: "Arial", color: "333333" },
          paragraph: { spacing: { before: 160, after: 80 }, outlineLevel: 2 } },
      ]
    },
    sections: [{
      properties: {
        page: {
          size: { width: 12240, height: 15840 },
          margin: { top: 1080, right: 1080, bottom: 1080, left: 1080 }
        }
      },
      headers: {
        default: new Header({
          children: [new Paragraph({
            children: [
              new TextRun({ text: "SEO / AI Content Expansion  |  Fruth Custom Packaging", bold: true, color: DARK_BLUE, size: 20 }),
            ],
            border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: FRUTH_RED, space: 1 } }
          })]
        })
      },
      footers: {
        default: new Footer({
          children: [new Paragraph({
            children: [
              new TextRun({ text: pageTitle + "  |  ", color: "888888", size: 18 }),
              new TextRun({ text: "Page ", color: "888888", size: 18 }),
              new TextRun({ children: [PageNumber.CURRENT], color: "888888", size: 18 }),
            ]
          })]
        })
      },
      children: allChildren
    }]
  });
}

// ── ELEMENT HELPERS ────────────────────────────────────────────────────────
function h1(text)  { return new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun(text)] }); }
function h2(text)  { return new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun(text)] }); }
function h3(text)  { return new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun(text)] }); }
function p(text, opts = {}) { return new Paragraph({ children: [new TextRun({ text, size: 22, ...opts })] }); }
function spacer()  { return new Paragraph({ children: [new TextRun("")] }); }
function bullet(text) {
  return new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [new TextRun({ text, size: 22 })] });
}
function ctaP(text) {
  return new Paragraph({
    shading: { fill: YELLOW, type: ShadingType.CLEAR },
    children: [new TextRun({ text, bold: true, size: 22 })]
  });
}
function labelBanner(label, color) {
  return new Paragraph({
    shading: { fill: color, type: ShadingType.CLEAR },
    spacing: { before: 120, after: 120 },
    children: [new TextRun({ text: "  " + label + "  ", bold: true, color: "FFFFFF", size: 22 })]
  });
}
function note(text) { return spacer(); }
function divider() {
  return new Paragraph({
    border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: FRUTH_RED, space: 1 } },
    children: [new TextRun("")]
  });
}
function urlLine(url) {
  return new Paragraph({ children: [new TextRun({ text: url, size: 22, color: BLACK })] });
}
function pageHeader(title, url) {
  _pageUrl = url;
  const implDate = implemented[url];
  const dateRow = implDate
    ? new Paragraph({
        children: [new TextRun({ text: "✓ Implemented in HubSpot: " + implDate, bold: true, color: "2E7D32", size: 20 })]
      })
    : new Paragraph({ children: [new TextRun({ text: "Pending Implementation", italics: true, color: "999999", size: 20 })] });
  return [
    h1("SEO / AI Content Expansion"),
    urlLine(url),
    dateRow,
    spacer(),
    note("Items highlighted in YELLOW are CTAs that should link to the existing contact form."),
    spacer(),
    divider(),
    spacer(),
  ];
}

// ── SAVE HELPER ────────────────────────────────────────────────────────────
function save(doc, filename) {
  Packer.toBuffer(doc).then(buf => {
    fs.writeFileSync(filename, buf);
    console.log("Created:", filename);
  });
}

// ══════════════════════════════════════════════════════════════════════════
// SCHEMA LIBRARY DATA
// (all schema JSON stored here — referenced by schema doc builder at bottom)
// ══════════════════════════════════════════════════════════════════════════
const schemaEntries = [
  {
    title: "Vacuum Seal Bags",
    url: "https://www.fruth.com/products/bags/vacuum-seal-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What materials are Fruth vacuum seal bags made from?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth manufactures vacuum seal bags from polyethylene (PE), nylon/poly laminates, and multi-layer high-barrier films. FDA-compliant and ESD-safe material options are available." }
    },
    {
      "@type": "Question",
      "name": "Can you produce vacuum seal bags in custom sizes?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. All bags are manufactured to your specified dimensions. We are not limited to pre-set catalog sizes." }
    },
    {
      "@type": "Question",
      "name": "How do I get a quote?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Contact us or call (714) 993-9955. Provide your size, material, quantity, and special requirements and we will respond within one business day." }
    }
  ]
}
</script>`
  },
  {
    title: "Black Conductive Film",
    url: "https://www.fruth.com/products/films/black-conductive-film",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is black conductive film used for?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Black conductive film protects items sensitive to electrostatic discharge (ESD) including electronics, semiconductors, and explosive or flammable materials. The carbon-loaded polyethylene construction dissipates static charge and provides a non-sparking surface." }
    },
    {
      "@type": "Question",
      "name": "Does Fruth black conductive film meet military specifications?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth black conductive film complies with MIL DTL 82646, MIL DTL 82647, and NFPA-56A standards. Performance is consistent regardless of humidity or aging conditions." }
    },
    {
      "@type": "Question",
      "name": "What formats is black conductive film available in?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Black conductive film is available as bags, tubing, or sheeting on continuous rolls. Custom sizes and configurations are available on request." }
    }
  ]
}
</script>`
  },
  {
    title: "Zipper Bags",
    url: "https://www.fruth.com/products/bags/zipper-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What materials are Fruth zipper bags made from?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth zipper bags are constructed from high-density polyethylene (HDPE) and are recyclable. LDPE and poly blend options are also available depending on application requirements." }
    },
    {
      "@type": "Question",
      "name": "Can zipper bags be custom printed?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth offers custom print options for branding, product identification, and handling instructions in any color or print configuration." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth zipper bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth zipper bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications." }
    }
  ]
}
</script>`
  },
  {
    title: "Lip & Tape Bags",
    url: "https://www.fruth.com/products/bags/lip-and-tape-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is the difference between permanent and resealable lip and tape bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Permanent tape bags form a tamper-evident seal — once closed the bag must be torn or damaged to open, making tampering visible. Resealable bags use a repositionable adhesive that can be opened and resealed multiple times without losing adhesion." }
    },
    {
      "@type": "Question",
      "name": "Are lip and tape bags tamper evident?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Permanent closure versions are tamper evident. Once sealed the adhesive will not release cleanly so any attempt to open the bag leaves visible evidence of tampering." }
    },
    {
      "@type": "Question",
      "name": "Can lip and tape bags be custom printed?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces custom printed lip and tape bags for retail branding, product identification, and handling instructions. Any size, film type, or print configuration is available." }
    }
  ]
}
</script>`
  },
  {
    title: "Cleanroom Bags",
    url: "https://www.fruth.com/products/bags/cleanroom-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What ISO class are Fruth cleanroom bags manufactured in?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth manufactures cleanroom bags in an ISO 14644 certified facility. Specific ISO class documentation is available on request for regulated procurement processes." }
    },
    {
      "@type": "Question",
      "name": "Do Fruth cleanroom bags contain any additives?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "No. Fruth cleanroom bags are produced with zero additives including slip agents and anti-block compounds. Resins are pure from the start and maintained in a controlled environment through vacuum-sealed completion." }
    },
    {
      "@type": "Question",
      "name": "What industries use Fruth cleanroom bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth cleanroom bags serve microelectronics, semiconductor, aerospace, silicon manufacturing, medical device, and food sector applications." }
    }
  ]
}
</script>`
  },
  {
    title: "FFP Barrier Film",
    url: "https://www.fruth.com/products/barrier-films/ffp-barrier-film",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is FFP barrier film?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "FFP stands for Film-Foil-Poly — a three-layer laminate combining a plastic film outer layer, aluminum foil middle layer, and polyethylene inner layer providing an effective barrier against moisture vapor, oxygen, and light." }
    },
    {
      "@type": "Question",
      "name": "How does FFP barrier film differ from MIL-PRF-131K?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "FFP barrier film provides comparable moisture and oxygen barrier performance to MIL-PRF-131K type foil at a lower cost but is not DOD-compliant. It is the right choice when military specification certification is not required but high barrier performance still is." }
    },
    {
      "@type": "Question",
      "name": "Can FFP barrier film be custom sized?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces FFP barrier film in both standard and fully custom configurations. Contact us with your dimensions, thickness requirements, and application details for a quote." }
    }
  ]
}
</script>`
  },
  {
    title: "Wicketed Bags",
    url: "https://www.fruth.com/products/bags/wicketed-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What are wicketed bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Wicketed bags are poly bags mounted on a metal wire wicket that holds them in a stacked dispensable format. This allows bags to be peeled off one at a time during manual or automated filling significantly increasing packaging line speed and efficiency." }
    },
    {
      "@type": "Question",
      "name": "Are wicketed bags compatible with automated filling equipment?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth wicketed bags are designed for use with automatic and semi-automatic filling equipment. Wicket spacing can be configured to match your specific machinery requirements." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth wicketed bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth wicketed bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications including produce, bakery, and processed food packaging." }
    }
  ]
}
</script>`
  },
  {
    title: "Side Seal Bags",
    url: "https://www.fruth.com/products/bags/side-seal-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What are side seal bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Side seal bags are flexible pouches sealed on three sides with an open top or bottom for filling. The three-sided seal provides strong containment for food, liquid, powder, and retail product applications." }
    },
    {
      "@type": "Question",
      "name": "Can side seal bags include hang holes or zippers?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth side seal bags are available with hang holes for retail display and zipper closures for resealable applications." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth side seal bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth side seal bags comply with FDA and USDA food safety specifications and are suitable for direct food contact applications." }
    }
  ]
}
</script>`
  },
  {
    title: "Lay Flat Bags",
    url: "https://www.fruth.com/products/bags/lay-flat-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What are lay flat bags used for?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Lay flat bags are open-end polyethylene bags used to protect products from dust, scratches, and moisture during storage and shipping. They are used across food, industrial, retail, and medical applications." }
    },
    {
      "@type": "Question",
      "name": "What sizes are available for lay flat bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth produces lay flat bags in both standard and fully custom sizes. Provide your product dimensions and we will recommend the right width, length, and gauge for your application." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth lay flat bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth lay flat bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications." }
    }
  ]
}
</script>`
  },
  {
    title: "Nylon Film",
    url: "https://www.fruth.com/products/films/nylon-film",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is nylon film used for in packaging?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Nylon film is used in packaging applications that require toughness, cleanliness, and abrasion resistance. Common uses include medical and laboratory device packaging, electronics and semiconductor protection, cleanroom packaging, and chemical and solvent containment." }
    },
    {
      "@type": "Question",
      "name": "How does nylon film differ from standard polyethylene?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Nylon provides significantly higher puncture resistance, abrasion resistance, and barrier performance compared to standard polyethylene. It is preferred when product sharpness, particulate sensitivity, or chemical barrier requirements exceed what standard poly can deliver." }
    },
    {
      "@type": "Question",
      "name": "Is Fruth nylon film available in custom sizes?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces nylon packaging in both standard and custom configurations. Contact us with your dimensions and application requirements for a quote." }
    }
  ]
}
</script>`
  },
  {
    title: "Autoclave Bags",
    url: "https://www.fruth.com/products/bags/autoclave-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What are autoclave bags made from?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth autoclave bags are manufactured from virgin polypropylene, which provides the durability and thermal stability required for high-temperature, high-pressure steam sterilization cycles." }
    },
    {
      "@type": "Question",
      "name": "What sterilization methods are Fruth autoclave bags compatible with?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth autoclave bags are compatible with gravity steam, high-vacuum steam, ETO gas, and chemical sterilization methods." }
    },
    {
      "@type": "Question",
      "name": "Do Fruth autoclave bags meet USP 1211 specifications?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth autoclave bags meet USP 1211 specifications and are ISO 9001:2015 certified. ASTM-tested impact and tear resistance data is available on request." }
    }
  ]
}
</script>`
  },
  {
    title: "Gusset Bags",
    url: "https://www.fruth.com/products/bags/gusset-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is the difference between side gusset and bottom gusset bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Side gusset bags expand at the sides and take on a pillow-like shape when filled, commonly used for coffee, snacks, and food products. Bottom gusset bags expand at the base to create a box-like stand-up structure, ideal for retail display and products that need shelf stability." }
    },
    {
      "@type": "Question",
      "name": "Are gusseted bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth gusseted bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications." }
    },
    {
      "@type": "Question",
      "name": "Can gusset bags be custom printed?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces custom printed gusset bags for retail branding, product identification, and handling instructions. Any size, film type, or print configuration is available." }
    }
  ]
}
</script>`
  },
  {
    title: "Square Bottom Bags",
    url: "https://www.fruth.com/products/bags/square-bottom-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is a square bottom bag?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "A square bottom bag combines the expandable sides of a gusseted bag with a flat stable base that allows it to stand upright, giving maximum fill volume, shelf stability, and a large flat panel for branding and graphics." }
    },
    {
      "@type": "Question",
      "name": "Can square bottom bags include degassing valves?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth square bottom bags support degassing valve integration, making them well suited for freshly roasted coffee and other products that off-gas after sealing." }
    },
    {
      "@type": "Question",
      "name": "What lamination options are available for square bottom bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth offers foil, metallized, and clear poly lamination options for square bottom bags, providing varying levels of barrier protection, opacity, and visual finish." }
    }
  ]
}
</script>`
  },
  {
    title: "Grow Bags",
    url: "https://www.fruth.com/products/bags/grow-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What sizes do Fruth grow bags come in?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth grow bags are available from 1-gallon seed bags up to 30-gallon bags suitable for mature shrubs and trees. Custom sizes are available for commercial nursery and greenhouse operations." }
    },
    {
      "@type": "Question",
      "name": "What colors are available for grow bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Grow bags are available from clear to black. Color choice affects soil temperature — black bags absorb heat while lighter colors reflect it, making color selection important for specific crop and climate requirements." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth grow bags reusable?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth grow bags are constructed for heavy-duty reusable use, making them a cost-effective alternative to single-use rigid nursery containers over multiple growing seasons." }
    }
  ]
}
</script>`
  },
  {
    title: "MIL-PRF-131K Barrier Film",
    url: "https://www.fruth.com/products/barrier-films/mil-prf-131k-barrier-film",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What does MIL-PRF-131K Class 1 mean?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "MIL-PRF-131K is a U.S. Department of Defense performance specification for barrier materials used in military packaging. Class 1 designates the highest barrier rating, providing certified protection against moisture vapor, oxygen, and light for long-term preservation of military and industrial equipment." }
    },
    {
      "@type": "Question",
      "name": "Why does MIL-PRF-131K film contain no amines or amides?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Amines, amides, and N-Octanoic acid can react with or damage polycarbonate components commonly found in electronics and optical equipment. Fruth MIL-PRF-131K film is formulated without these compounds, making it safe for direct contact with polycarbonate parts." }
    },
    {
      "@type": "Question",
      "name": "Can Fruth produce MIL-PRF-131K film in custom sizes?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces MIL-PRF-131K barrier film in custom widths, lengths, and configurations. Contact us with your specifications and application details for a quote." }
    }
  ]
}
</script>`
  },
  {
    title: "Header Bags",
    url: "https://www.fruth.com/products/bags/header-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What are header bags used for?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Header bags are poly bags attached to a cardboard or poly header, typically hung on pegboard displays or rack systems. They are used in retail, hardware, industrial, and food service applications where organized display and easy dispensing are important." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth header bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth header bags meet FDA and USDA food safety specifications, making them suitable for food service and healthcare applications." }
    },
    {
      "@type": "Question",
      "name": "Can header bags be custom sized?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces header bags in custom sizes to match your product dimensions and display requirements. Contact us with your specifications for a quote." }
    }
  ]
}
</script>`
  },
  {
    title: "Bottom Seal Bags",
    url: "https://www.fruth.com/products/bags/bottom-seal-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What makes bottom seal bags stronger than standard bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Bottom seal bags are made from a continuous tube of film sealed at the bottom, eliminating the side seams found in standard flat bags. This construction distributes stress more evenly and reduces the risk of leaks or failures under heavy loads or internal pressure." }
    },
    {
      "@type": "Question",
      "name": "What materials are Fruth bottom seal bags available in?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth bottom seal bags are available in LDPE, FDA-approved materials for food contact, and recycled poly materials. Material selection depends on your product type, weight, and regulatory requirements." }
    },
    {
      "@type": "Question",
      "name": "Are bottom seal bags suitable for food packaging?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth bottom seal bags are available in FDA-approved materials and are used in food processing applications including grain, flour, and liquid packaging." }
    }
  ]
}
</script>`
  },
  {
    title: "Fresh Produce Bags",
    url: "https://www.fruth.com/products/bags/fresh-produce-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What makes fresh produce bags different from standard poly bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fresh produce bags are engineered with specific barrier properties — controlling oxygen transmission, moisture vapor, and UV exposure — to create an optimal environment for perishable products. Standard poly bags do not provide these barrier characteristics." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth fresh produce bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth fresh produce bags meet all FDA and USDA food safety specifications and are safe for direct contact with fresh fruits and vegetables." }
    },
    {
      "@type": "Question",
      "name": "Can fresh produce bags be custom sized?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces fresh produce bags in custom sizes to match your product dimensions, pack weights, and filling equipment requirements." }
    }
  ]
}
</script>`
  },
  {
    title: "Bakery Bags",
    url: "https://www.fruth.com/products/bags/bakery-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What material are Fruth bakery bags made from?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth bakery bags are made from high-quality, low-density polyethylene (LDPE). The material is lightweight, food-safe, recyclable, and compliant with FDA and USDA food contact regulations." }
    },
    {
      "@type": "Question",
      "name": "Can bakery bags be custom printed?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces custom printed bakery bags for retail branding, product identification, and ingredient labeling. Custom sizing, thickness, and color options are also available." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth bakery bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Our bakery bags comply with FDA and USDA food contact regulations and are produced under strict quality controls for cleanliness and strength." }
    }
  ]
}
</script>`
  },
  {
    title: "Foam Bags",
    url: "https://www.fruth.com/products/bags/foam-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What are polyethylene foam bags used for?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Polyethylene foam bags are used to protect delicate or high-value items from scratching, impact, and moisture during storage and shipping. Common uses include electronics, glassware, metal parts, medical devices, and retail product packaging." }
    },
    {
      "@type": "Question",
      "name": "What thickness options are available for foam bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth foam bags are available in 1/16 inch and 1/8 inch standard thicknesses. Custom thickness options are available for specific cushioning requirements." }
    },
    {
      "@type": "Question",
      "name": "Are foam bags water resistant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth polyethylene foam bags are water resistant, making them suitable for products that require moisture protection during storage and transportation." }
    }
  ]
}
</script>`
  },
  {
    title: "Multi-Pocket Bags",
    url: "https://www.fruth.com/products/bags/multi-pocket-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What are multi-pocket bags used for?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Multi-pocket bags are used to organize and separate multiple items within a single bag. Common applications include prescription and pharmacy packaging, medical supply kits, industrial parts kits, and retail promotional packaging." }
    },
    {
      "@type": "Question",
      "name": "Can multi-pocket bags be custom printed?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces multi-pocket bags in virtually any color and print configuration. Custom sizing and pocket layout are also available to match your specific application." }
    },
    {
      "@type": "Question",
      "name": "Are Fruth multi-pocket bags FDA compliant?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth multi-pocket bags meet FDA and USDA food and safety specifications and are manufactured under ISO 9001:2015 certified quality controls." }
    }
  ]
}
</script>`
  },
  {
    title: "Tamper Evident Bags",
    url: "https://www.fruth.com/products/bags/tamper-evident-bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "How do tamper evident bags show signs of tampering?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth tamper evident bags are engineered to resist side-breach and reseal attempts. If the bag is opened or an entry is attempted, the bag construction makes tampering visible — providing verifiable evidence of unauthorized access." }
    },
    {
      "@type": "Question",
      "name": "What industries use tamper evident bags?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Common users include pharmaceutical distributors, healthcare providers, financial institutions, legal and document management firms, and retailers who require chain-of-custody verification or loss prevention packaging." }
    },
    {
      "@type": "Question",
      "name": "Can tamper evident bags be custom sized and printed?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces tamper evident bags in custom sizes with print options for barcodes, sequential numbering, branding, and security features. Contact us with your requirements for a quote." }
    }
  ]
}
</script>`
  },
  {
    title: "Scrim Foil Barrier Film",
    url: "https://www.fruth.com/products/barrier-films/scrim-foil-barrier-film",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is scrim foil barrier film used for?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Scrim foil barrier film is a sealing laminate used on foil-faced fiberglass ductwork and sheet metal ducts in HVAC and mechanical insulation applications. It is used to seal duct seams, repair damaged insulation, and provide moisture and vapor barrier protection." }
    },
    {
      "@type": "Question",
      "name": "Does Fruth scrim foil require special tools to install?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "No. Fruth scrim foil is designed for straightforward application to fibrous and sheet metal ductwork without special tools or installation methods." }
    },
    {
      "@type": "Question",
      "name": "Is Fruth scrim foil resistant to moisture and mold?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth scrim foil barrier film is highly resistant to moisture, mold, and vapors, making it suitable for HVAC and mechanical insulation environments where long-term performance is required." }
    }
  ]
}
</script>`
  },
  {
    title: "Kraft Foil Barrier Film",
    url: "https://www.fruth.com/products/barrier-films/kraft-foil-barrier-film",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is kraft foil barrier film used for?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Kraft foil barrier film is used for single-use and single-serve flexible packaging in the food, supplement, and cosmetic industries. It provides grease resistance and moisture protection for products like snacks, seasonings, nutritional supplements, and cosmetic sachets." }
    },
    {
      "@type": "Question",
      "name": "What specification does Fruth kraft foil barrier film meet?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth kraft foil barrier film is manufactured to MIL-PRF-131 TI C2 specifications, a recognized standard for barrier material performance." }
    },
    {
      "@type": "Question",
      "name": "Can kraft foil barrier film be produced in custom sizes?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces kraft foil barrier film in custom widths, lengths, and configurations to match your packaging equipment and product requirements. Contact us for specifications and a quote." }
    }
  ]
}
</script>`
  },
  {
    title: "Bags — Product Hub",
    url: "https://www.fruth.com/products/bags",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is Fruth a U.S.-based poly bag manufacturer?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth is a domestic custom poly bag manufacturer based in Placentia, California. All bags are made in the USA under ISO 9001:2015 certified quality controls." }
    },
    {
      "@type": "Question",
      "name": "What types of custom plastic bags does Fruth produce?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Fruth produces 18 bag types including autoclave bags, cleanroom bags, vacuum seal bags, wicketed bags, gusset bags, zipper bags, tamper evident bags, and more. All are available in custom sizes, materials, and configurations." }
    },
    {
      "@type": "Question",
      "name": "Can Fruth produce bags that meet FDA or USDA requirements?",
      "acceptedAnswer": { "@type": "Answer",
        "text": "Yes. Fruth produces bags in FDA and USDA compliant materials for food, medical, and pharmaceutical applications. Cleanroom-compatible and electrostatic protection options are also available." }
    }
  ]
}
</script>`
  },
  {
    title: "Homepage",
    url: "https://www.fruth.com",
    json: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Fruth Custom Packaging",
  "url": "https://www.fruth.com",
  "logo": "https://www.fruth.com/logo.png",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "701 S. Richfield Rd.",
    "addressLocality": "Placentia",
    "addressRegion": "CA",
    "postalCode": "92870",
    "addressCountry": "US"
  },
  "telephone": "+17149939955",
  "email": "sales@fruth.com",
  "description": "U.S.-based custom packaging manufacturer producing flexible bags and barrier films for industrial, food, medical, electronics, and cleanroom applications. ISO 9001:2015 certified, FDA and USDA compliant. Made in the USA.",
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "Custom Packaging Products",
    "itemListElement": [
      { "@type": "Offer", "itemOffered": { "@type": "Product", "name": "Custom Bags" } },
      { "@type": "Offer", "itemOffered": { "@type": "Product", "name": "Barrier Films" } },
      { "@type": "Offer", "itemOffered": { "@type": "Product", "name": "Poly Films" } }
    ]
  }
}
</script>`
  },
];

// ── SCHEMA LOOKUP ──────────────────────────────────────────────────────────
const schemaByUrl = new Map(schemaEntries.map(e => [e.url, e.json]));

function schemaSection(url) {
  const json = schemaByUrl.get(url);
  if (!json) return [];
  return [
    spacer(),
    divider(),
    spacer(),
    labelBanner("SCHEMA / Technical Implementation", GRAY_BG),
    spacer(),
    ...json.split('\n').map(line => new Paragraph({
      shading: { fill: CODE_BG, type: ShadingType.CLEAR },
      children: [new TextRun({ text: line, font: "Courier New", size: 16 })]
    })),
    spacer(),
  ];
}

// ══════════════════════════════════════════════════════════════════════════
// PAGE 2 — BLACK CONDUCTIVE FILM
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Black Conductive Film", [
  ...pageHeader("Black Conductive Film", "https://www.fruth.com/products/films/black-conductive-film"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth manufactures black conductive film designed to protect items vulnerable to static electricity damage. The material features a non-sparking surface perfect for use with chemicals, powder explosives, and sensitive electronics."),
  spacer(),
  p("Constructed from carbon-loaded polyethylene in a single layer, our black conductive film complies with MIL DTL 82646, MIL DTL 82647, and NFPA-56A standards and maintains performance regardless of humidity or aging conditions."),
  spacer(),
  bullet("Temperature range: -20°F to 100°F"),
  bullet("Thickness: 20 microns"),
  bullet("Available as bags, tubing, or sheeting (continuous rolls)"),
  bullet("Good UV resistance"),
  bullet("Corrosion resistant"),
  bullet("Groundable for static dissipation"),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Anti Static & ESD Packaging for B2B Applications"),
  p("Fruth black conductive film is a trusted anti static packaging solution for B2B buyers in electronics manufacturing, defense, aerospace, and industrial sectors. When electrostatic discharge (ESD) poses a risk to your components or materials, our conductive film provides a reliable, MIL-spec compliant barrier."),
  spacer(),
  p("Common applications include:"),
  bullet("Sensitive electronics and semiconductor components"),
  bullet("Explosive or flammable powder containment"),
  bullet("Defense and aerospace component packaging"),
  bullet("Industrial parts requiring static-free storage or shipment"),
  spacer(),

  h2("Custom Configurations Available"),
  p("We produce black conductive film in bags, lay flat tubing, and sheeting to match your production or shipping workflow. Custom sizing, gauge specifications, and print options are available for OEM and distributor accounts."),
  spacer(),
  ctaP("Contact Fruth for a custom quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What is black conductive film used for?"),
  p("Black conductive film is used to protect items sensitive to electrostatic discharge (ESD) — including electronics, semiconductors, and explosive or flammable materials. The carbon-loaded polyethylene construction dissipates static charge and provides a non-sparking surface."),
  spacer(),
  h3("Does Fruth black conductive film meet military specifications?"),
  p("Yes. Our black conductive film complies with MIL DTL 82646, MIL DTL 82647, and NFPA-56A standards. Performance is consistent regardless of humidity or aging conditions."),
  spacer(),
  h3("What formats is black conductive film available in?"),
  p("We produce black conductive film as bags, tubing, or sheeting on continuous rolls. Custom sizes and configurations are available — contact us with your specifications."),

]), "CE_Fruth_Black Conductive Film v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 3 — ZIPPER BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Zipper Bags", [
  ...pageHeader("Zipper Bags", "https://www.fruth.com/products/bags/zipper-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth Custom Packaging manufactures zipper bags — one of the most sought after bags in nearly every industry. From retail and food to gift and containment applications, our zipper bags deliver reliable sealing performance with full customization options."),
  spacer(),
  bullet("Available in practically any color, print color, or thickness"),
  bullet("Side-weld seal reinforces the zipper for added durability"),
  bullet("Constructed from high-density polyethylene — recyclable"),
  bullet("Customizable sizing per client specifications"),
  bullet("Lightweight and flexible design"),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Meets FDA and USDA food safety specifications"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Zipper Bags for B2B & Industrial Applications"),
  p("Fruth produces custom zipper bags for B2B buyers across food processing, retail packaging, industrial distribution, and specialty product sectors. Every bag is manufactured to your exact specifications — no catalog limitations on size, gauge, or print."),
  spacer(),
  p("Customize by:"),
  bullet("Material — high-density polyethylene (HDPE), LDPE, or poly blends"),
  bullet("Size — custom width and length to fit your product exactly"),
  bullet("Thickness — light-duty to heavy-duty gauge options"),
  bullet("Color — clear, colored, or opaque"),
  bullet("Print — unprinted or custom branded with logo and handling instructions"),
  bullet("Closure — standard press-to-close zipper or reinforced side-weld seal"),
  spacer(),

  h2("Industries We Serve"),
  p("Our custom zipper bags support operations across a wide range of B2B sectors:"),
  bullet("Food processing and distribution — FDA and USDA compliant materials for direct food contact"),
  bullet("Retail and e-commerce — custom print for branded presentation"),
  bullet("Gift and specialty goods packaging"),
  bullet("Industrial parts and hardware — heavy-duty construction for component storage"),
  spacer(),
  ctaP("Need custom zipper bags at volume? Contact Fruth for a quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What materials are Fruth zipper bags made from?"),
  p("Our zipper bags are constructed from high-density polyethylene (HDPE) and are recyclable. We also produce zipper bags in LDPE and poly blends depending on your application requirements."),
  spacer(),
  h3("Can zipper bags be custom printed?"),
  p("Yes. We offer custom print options for branding, product identification, and handling instructions. Any color or print configuration is available."),
  spacer(),
  h3("Are your zipper bags FDA compliant?"),
  p("Yes. Our zipper bags meet FDA and USDA food safety specifications, making them suitable for direct food contact applications."),

]), "CE_Fruth_Zipper Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 4 — LIP & TAPE BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Lip & Tape Bags", [
  ...pageHeader("Lip & Tape Bags", "https://www.fruth.com/products/bags/lip-and-tape-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth Custom Packaging specializes in lip and tape bags that combine performance with affordability. These bags feature an adhesive strip on the flap, which can be folded and sealed over the bag's opening."),
  spacer(),
  bullet("Sideweld design with standard 1 inch lip"),
  bullet("Available for various films and cushion options"),
  bullet("Made in the USA"),
  spacer(),
  p("Permanent Tape:", { bold: true }),
  p("Once sealed, the adhesive will not release when pulled. This option is ideal for securing contents — excessive force will damage the bag itself rather than break the seal. Tamper-evident versions are available."),
  spacer(),
  p("Resealable Option:", { bold: true }),
  p("These bags can be opened and resealed several times without impacting the adhesion of the tape."),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Tamper Evident Bags for B2B & Commercial Applications"),
  p("Fruth lip and tape bags are a leading tamper evident packaging solution for B2B buyers across retail, e-commerce, pharmaceutical, food service, and industrial distribution. The permanent seal construction ensures contents cannot be accessed without visible evidence of tampering — critical for brand protection, compliance, and consumer trust."),
  spacer(),
  p("Common applications include:"),
  bullet("Retail and apparel — polybag sleeve packaging with tamper evidence"),
  bullet("E-commerce fulfillment — resealable or permanent seal for returns and shipments"),
  bullet("Pharmaceutical and nutraceutical — tamper-evident compliance packaging"),
  bullet("Food service and specialty goods — sealed freshness and contamination protection"),
  bullet("Industrial parts — secure component packaging for distribution"),
  spacer(),

  h2("Custom Lip & Tape Bag Specifications"),
  p("Every bag is built to your specifications. Customization options include:"),
  bullet("Lip length — standard 1 inch or custom length"),
  bullet("Material — polyethylene film, poly blends, or specialty films"),
  bullet("Size — custom width and length"),
  bullet("Closure type — permanent seal or resealable"),
  bullet("Print — clear/unprinted or custom branded"),
  spacer(),
  ctaP("Contact Fruth for a custom quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What is the difference between permanent and resealable lip and tape bags?"),
  p("Permanent tape bags form a tamper-evident seal — once closed, the bag must be torn or damaged to open, making tampering visible. Resealable bags use a repositionable adhesive that can be opened and resealed multiple times without losing adhesion."),
  spacer(),
  h3("Are lip and tape bags tamper evident?"),
  p("Yes — permanent closure versions are tamper evident. Once sealed, the adhesive will not release cleanly, so any attempt to open the bag leaves visible evidence of tampering."),
  spacer(),
  h3("Can lip and tape bags be custom printed?"),
  p("Yes. Fruth produces custom printed lip and tape bags for retail branding, product identification, and handling instructions. Any size, film type, or print configuration is available."),

]), "CE_Fruth_Lip and Tape Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 5 — CLEANROOM BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Cleanroom Bags", [
  ...pageHeader("Cleanroom Bags", "https://www.fruth.com/products/bags/cleanroom-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth manufactures cleanroom bags in an ISO 14644 certified facility. Our resins are pure from the start and contain ZERO additives like slip and anti-block. The production process maintains materials in the cleanroom environment from raw state through vacuum-sealed completion, achieving full FDA compliance."),
  spacer(),
  bullet("Vertically integrated operations"),
  bullet("ISO 14644 compliant cleanrooms"),
  bullet("Manufactured entirely in the USA"),
  bullet("Zero additive formulations"),
  spacer(),
  p("Industries served:"),
  bullet("Microelectronics"),
  bullet("Aerospace"),
  bullet("Semiconductors"),
  bullet("Silicon manufacturing"),
  bullet("Food sector"),
  bullet("Medical device markets"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("ISO Cleanroom Packaging for Medical, Semiconductor & Aerospace Applications"),
  p("Fruth cleanroom bags are engineered for applications where contamination control is non-negotiable. Our ISO 14644 certified production environment ensures every bag meets the stringent cleanliness standards required by medical device manufacturers, semiconductor fabs, aerospace suppliers, and precision electronics producers."),
  spacer(),
  p("Unlike standard poly bags, our cleanroom bags are produced with zero slip agents, anti-block additives, or processing aids that could contaminate sensitive components or violate regulatory requirements. Every batch is vacuum-sealed within the cleanroom to preserve integrity through delivery."),
  spacer(),

  h2("Custom Cleanroom Bag Specifications"),
  p("We produce cleanroom bags to your exact requirements:"),
  bullet("ISO class rating — matched to your facility or product requirements"),
  bullet("Material — ultra-pure polyethylene, nylon, or specialty cleanroom films"),
  bullet("Size — fully custom width, length, and thickness"),
  bullet("Seal type — bottom seal, side seal, or open-top configurations"),
  bullet("Certification documentation — available on request for regulated industries"),
  spacer(),

  h2("Why Cleanroom Packaging Matters for Medical & Electronics Buyers"),
  p("For medical device packaging and electronics packaging, particulate contamination during the packaging process can cause product failures, regulatory non-compliance, or patient safety issues. Fruth's vertically integrated cleanroom production means your bags are never exposed to a non-controlled environment between manufacture and delivery."),
  spacer(),
  ctaP("Contact Fruth for a custom cleanroom bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What ISO class are Fruth cleanroom bags manufactured in?"),
  p("Fruth manufactures cleanroom bags in an ISO 14644 certified facility. Specific ISO class documentation is available on request for regulated procurement processes."),
  spacer(),
  h3("Do Fruth cleanroom bags contain any additives?"),
  p("No. Our cleanroom bags are produced with zero additives including slip agents and anti-block compounds. Resins are pure from the start and maintained in a controlled environment through vacuum-sealed completion."),
  spacer(),
  h3("What industries use Fruth cleanroom bags?"),
  p("Our cleanroom bags serve microelectronics, semiconductor, aerospace, silicon manufacturing, medical device, and food sector applications — any industry where contamination control and regulatory compliance are critical."),

]), "CE_Fruth_Cleanroom Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 6 — FFP BARRIER FILM
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("FFP Barrier Film", [
  ...pageHeader("FFP Barrier Film", "https://www.fruth.com/products/barrier-films/ffp-barrier-film"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth offers FFP (Film-Foil-Poly) barrier film — a lower cost, non DOD-compliant alternative to MIL-PRF-131k Type foil. The material provides heat-sealability and delivers superior tear and puncture resistance as well as protection from light, air, and moisture vapor."),
  spacer(),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Made in the USA"),
  bullet("Available in standard and custom configurations"),
  bullet("Material consultation available — we help you select the barrier material best suited for your product, specifications, and budget"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Moisture Barrier Film for B2B & Industrial Packaging"),
  p("Fruth FFP barrier film is a high-performance moisture barrier film solution for B2B buyers who need reliable protection against humidity, oxygen, and light without the cost or compliance overhead of full military-specification materials. The three-layer Film-Foil-Poly construction delivers the barrier performance required for long-term storage, international shipment, and contamination-sensitive applications."),
  spacer(),
  p("Common applications include:"),
  bullet("Industrial parts and metal components — corrosion prevention during storage and transit"),
  bullet("Food and pharmaceutical packaging — moisture and oxygen exclusion for shelf life extension"),
  bullet("Electronics and sensitive components — humidity and light protection"),
  bullet("Military and defense adjacent — cost-effective alternative where full MIL-PRF-131K is not required"),
  bullet("Long-term archival and preservation packaging"),
  spacer(),

  h2("FFP Barrier Film Properties"),
  bullet("Three-layer construction — film, foil, and poly laminate"),
  bullet("Heat-sealable for airtight closures"),
  bullet("Superior tear and puncture resistance"),
  bullet("Blocks moisture vapor, oxygen, and light"),
  bullet("Custom widths, lengths, and thicknesses available"),
  bullet("Non DOD-compliant — cost-effective for commercial and industrial applications"),
  spacer(),
  ctaP("Not sure if FFP is right for your application? Contact Fruth — our team provides material consultation and responds within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What is FFP barrier film?"),
  p("FFP stands for Film-Foil-Poly — a three-layer laminate that combines a plastic film outer layer, an aluminum foil middle layer, and a polyethylene inner layer. Together these layers provide an effective barrier against moisture vapor, oxygen, and light."),
  spacer(),
  h3("How does FFP barrier film differ from MIL-PRF-131K?"),
  p("FFP barrier film provides comparable moisture and oxygen barrier performance to MIL-PRF-131K type foil at a lower cost, but it is not DOD-compliant. It is the right choice when military specification certification is not required but high barrier performance still is."),
  spacer(),
  h3("Can FFP barrier film be custom sized?"),
  p("Yes. Fruth produces FFP barrier film in both standard and fully custom configurations. Contact us with your dimensions, thickness requirements, and application details for a quote."),

]), "CE_Fruth_FFP Barrier Film v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 7 — WICKETED BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Wicketed Bags", [
  ...pageHeader("Wicketed Bags", "https://www.fruth.com/products/bags/wicketed-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth Custom Packaging offers wicketed bags in practically any color, print color, or thickness depending on your packaging needs. Featuring wire-wicket mounting for fast and easy manual loading, our wicketed bags are fully compatible with automatic and semi-automatic filling equipment."),
  spacer(),
  bullet("Highest quality polyethylene material for product visibility"),
  bullet("Customizable sizing to exact specifications"),
  bullet("Lightweight and flexible construction"),
  bullet("Made in the USA"),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Meets FDA and USDA food safety specifications"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Wicketed Bags for High-Speed B2B Packaging Lines"),
  p("Fruth wicketed bags are engineered for B2B buyers running automated or semi-automatic packaging operations. The wire-wicket format allows bags to be dispensed one at a time directly from the stack — maximizing throughput on filling lines and reducing manual handling time across food processing, produce, retail, and industrial packaging operations."),
  spacer(),
  p("Common applications include:"),
  bullet("Food processing and produce — FDA and USDA compliant materials for direct food contact"),
  bullet("Bakery and fresh goods — high-clarity poly for product visibility on retail shelves"),
  bullet("Industrial parts and hardware — custom gauge options for heavier components"),
  bullet("Retail packaging and fulfillment — branded print options for shelf-ready presentation"),
  spacer(),

  h2("Custom Wicketed Bag Specifications"),
  p("Every wicketed bag is built to your production line requirements:"),
  bullet("Material — polyethylene (LDPE/HDPE) or specialty poly blends"),
  bullet("Size — custom width and length to match your product and filling equipment"),
  bullet("Thickness — light to heavy gauge depending on load requirements"),
  bullet("Color — clear or any custom color"),
  bullet("Print — unprinted or fully custom branded"),
  bullet("Wicket spacing — configured to your filling equipment specifications"),
  spacer(),
  ctaP("Contact Fruth for a custom quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What are wicketed bags?"),
  p("Wicketed bags are poly bags mounted on a metal wire wicket that holds them in a stacked, dispensable format. This allows bags to be peeled off one at a time during manual or automated filling, significantly increasing packaging line speed and efficiency."),
  spacer(),
  h3("Are wicketed bags compatible with automated filling equipment?"),
  p("Yes. Fruth wicketed bags are designed for use with automatic and semi-automatic filling equipment. Wicket spacing can be configured to match your specific machinery requirements."),
  spacer(),
  h3("Are Fruth wicketed bags FDA compliant?"),
  p("Yes. Our wicketed bags meet FDA and USDA food safety specifications, making them suitable for direct food contact applications including produce, bakery, and processed food packaging."),

]), "CE_Fruth_Wicketed Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 8 — SIDE SEAL BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Side Seal Bags", [
  ...pageHeader("Side Seal Bags", "https://www.fruth.com/products/bags/side-seal-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth manufactures side seal bags in multiple styles for diverse product requirements. These pouches feature sealing on three sides with an opening at the top or bottom for hand or machine filling. Optional features include hang holes and zippers upon request."),
  spacer(),
  p("Common uses include ground coffee, spices, liquids, and similar products. Clear packaging options allow full visibility of contents through the sealed pouch."),
  spacer(),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Lightweight and flexible construction"),
  bullet("Compliant with FDA and USDA food safety specifications"),
  bullet("Made in the USA"),
  bullet("Standard and custom options available"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Side Seal Bags for Food, Retail & Industrial Applications"),
  p("Fruth side seal bags are a versatile packaging solution for B2B buyers who need a three-sided sealed pouch for hand or automated filling lines. The open-top or open-bottom format supports a wide range of products and filling methods, with optional hang holes and zipper closures available for retail display or resealable applications."),
  spacer(),
  p("Common applications include:"),
  bullet("Ground coffee, spices, and dry food products — FDA and USDA compliant materials"),
  bullet("Liquids and semi-liquids — heat-sealed three-side construction prevents leakage"),
  bullet("Retail product packaging — clear film for full product visibility"),
  bullet("Industrial powders and granules — custom gauge for weight and puncture requirements"),
  spacer(),

  h2("Custom Side Seal Bag Specifications"),
  p("Every bag is built to your exact requirements:"),
  bullet("Size — custom width and length"),
  bullet("Material — polyethylene or specialty films"),
  bullet("Thickness — matched to your product weight and filling method"),
  bullet("Closure options — hang holes or zipper upon request"),
  bullet("Print — clear/unprinted or custom branded"),
  spacer(),
  ctaP("Contact Fruth for a custom quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What are side seal bags?"),
  p("Side seal bags are flexible pouches sealed on three sides with an open top or bottom for filling. The three-sided seal provides a clean, professional finish and strong containment for food, liquid, powder, and retail product applications."),
  spacer(),
  h3("Can side seal bags include hang holes or zippers?"),
  p("Yes. Fruth side seal bags are available with hang holes for retail display and zipper closures for resealable applications. Specify your requirements when requesting a quote."),
  spacer(),
  h3("Are Fruth side seal bags FDA compliant?"),
  p("Yes. Our side seal bags comply with FDA and USDA food safety specifications and are suitable for direct food contact applications."),

]), "CE_Fruth_Side Seal Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 9 — LAY FLAT BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Lay Flat Bags", [
  ...pageHeader("Lay Flat Bags", "https://www.fruth.com/products/bags/lay-flat-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth offers lay flat bags that provide excellent protection, are extremely versatile, and cost-efficient for packaging various product sizes. These polyethylene bags are lightweight and flexible, offering protection for your goods from dust and scratches."),
  spacer(),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Standard and custom options available"),
  bullet("FDA and USDA food safety compliant"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Lay Flat Poly Bags for B2B & Industrial Use"),
  p("Fruth lay flat bags are a practical, cost-efficient packaging solution for B2B buyers who need reliable protection across a wide range of product sizes and applications. The open-end format makes them simple to load by hand or machine, and the lay flat design enables efficient stacking, storage, and shipping."),
  spacer(),
  p("Common applications include:"),
  bullet("Parts and component storage — dust and scratch protection for industrial and hardware products"),
  bullet("Food packaging — FDA and USDA compliant materials for direct food contact"),
  bullet("Retail sleeving — clear film for product visibility on shelf or in fulfillment"),
  bullet("Medical and laboratory — clean, uncontaminated containment for instruments and supplies"),
  spacer(),

  h2("Custom Lay Flat Bag Specifications"),
  p("Available in a wide range of sizes and configurations:"),
  bullet("Material — polyethylene (LDPE/HDPE) in standard or custom formulations"),
  bullet("Size — custom width and length to match your product dimensions"),
  bullet("Thickness — light to heavy gauge depending on protection requirements"),
  bullet("Finish — clear for visibility or opaque options available"),
  bullet("Print — unprinted or custom branded"),
  spacer(),
  ctaP("Contact Fruth to discuss standard or custom lay flat bag options — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What are lay flat bags used for?"),
  p("Lay flat bags are open-end polyethylene bags used to protect products from dust, scratches, and moisture during storage and shipping. They are used across food, industrial, retail, and medical applications."),
  spacer(),
  h3("What sizes are available for lay flat bags?"),
  p("Fruth produces lay flat bags in both standard and fully custom sizes. Provide your product dimensions and we will recommend the right width, length, and gauge for your application."),
  spacer(),
  h3("Are Fruth lay flat bags FDA compliant?"),
  p("Yes. Our lay flat bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications."),

]), "CE_Fruth_Lay Flat Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 10 — NYLON FILM
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Nylon Film", [
  ...pageHeader("Nylon Film", "https://www.fruth.com/products/films/nylon-film"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Nylon is ideal for packaging challenges involving toughness, cleanliness, and maximum resistance to abrasion."),
  spacer(),
  p("Medical / Laboratory:", { bold: true }),
  p("Nylon contains sharp objects safely while protecting devices from particulate exposure."),
  spacer(),
  p("Electronics & Semiconductors:", { bold: true }),
  p("This material prevents even microscopic particles from damaging electronic components or semiconductors used in diodes, transistors, and integrated circuits."),
  spacer(),
  p("Cleanroom:", { bold: true }),
  p("The film provides the lowest particulate emission levels available for cleanroom packaging applications."),
  spacer(),
  p("Chemical Packaging:", { bold: true }),
  p("Nylon functions effectively as an aroma and gas barrier for pesticides, fertilizers, fragrances, and chemical products."),
  spacer(),
  p("Solvent Recovery:", { bold: true }),
  p("The material supports extraction of useful materials from waste or manufacturing by-product solvents."),
  spacer(),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Standard and custom nylon packaging solutions"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Nylon Packaging for B2B Applications"),
  p("Fruth nylon film is a high-performance packaging material chosen by B2B buyers who need superior toughness, cleanliness, and barrier performance in a single film. Unlike standard polyethylene, nylon offers exceptional puncture and abrasion resistance, making it the preferred choice for sharp, heavy, or particulate-sensitive products across medical, electronics, cleanroom, and chemical industries."),
  spacer(),

  h2("Nylon Film Properties"),
  bullet("Outstanding puncture and abrasion resistance"),
  bullet("Lowest particulate emission levels — ideal for cleanroom environments"),
  bullet("Effective aroma and gas barrier for chemical applications"),
  bullet("Contains sharp objects safely without risk of film failure"),
  bullet("Prevents microscopic particle contamination for electronics and semiconductors"),
  bullet("Available in standard and custom configurations"),
  spacer(),
  ctaP("Contact Fruth to discuss nylon packaging options for your application — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What is nylon film used for in packaging?"),
  p("Nylon film is used in packaging applications that require toughness, cleanliness, and abrasion resistance. Common uses include medical and laboratory device packaging, electronics and semiconductor protection, cleanroom packaging, and chemical and solvent containment."),
  spacer(),
  h3("How does nylon film differ from standard polyethylene?"),
  p("Nylon provides significantly higher puncture resistance, abrasion resistance, and barrier performance compared to standard polyethylene. It is the preferred material when product sharpness, particulate sensitivity, or chemical barrier requirements exceed what standard poly can deliver."),
  spacer(),
  h3("Is Fruth nylon film available in custom sizes?"),
  p("Yes. Fruth produces nylon packaging in both standard and custom configurations. Contact us with your dimensions and application requirements for a quote."),

]), "CE_Fruth_Nylon Film v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 11 — AUTOCLAVE BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Autoclave Bags", [
  ...pageHeader("Autoclave Bags", "https://www.fruth.com/products/bags/autoclave-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth Custom Packaging manufactures autoclave bags designed for high-temperature, high-pressure steam sterilization in medical and laboratory settings. These bags safely contain infectious and biohazardous waste before disposal."),
  spacer(),
  bullet("Made from virgin polypropylene for durability"),
  bullet("Compatible with gravity steam, high-vacuum steam, ETO gas, and chemical sterilization"),
  bullet("Meets USP 1211 specifications"),
  bullet("ISO 9001:2015 certified"),
  bullet("Red opaque color for biohazard compliance"),
  bullet("Optional temperature-indicator ink available"),
  bullet("Manufactured in the USA"),
  spacer(),
  p("Performance Specifications:"),
  bullet("Impact resistance: 165 grams (ASTM D1709)"),
  bullet("Tear resistance: 480 grams in both directions (ASTM D1922)"),
  bullet("Gauge thickness: 1.5 to 6 mil"),
  bullet("Available in flat or gusseted styles"),
  spacer(),
  p("Available Configurations:"),
  bullet("Resealable autoclave bags"),
  bullet("Anti-static variants"),
  bullet("Parts and sample bags"),
  bullet("Biohazard and specimen pouches"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Medical Device Packaging & Biohazard Containment"),
  p("Fruth autoclave bags are used by hospitals, laboratories, medical device manufacturers, and research facilities that require validated sterilization packaging and reliable biohazardous waste containment. Manufactured from virgin polypropylene and tested to ASTM standards, our autoclave bags are built to perform under the demanding conditions of steam sterilization cycles."),
  spacer(),
  p("Common applications include:"),
  bullet("Infectious and biohazardous waste containment prior to disposal"),
  bullet("Steam sterilization of medical instruments and devices"),
  bullet("Laboratory specimen and sample packaging"),
  bullet("Medical device manufacturer packaging and sterilization validation"),
  bullet("Hospital and clinical waste management compliance"),
  spacer(),

  h2("Sterilization Compatibility"),
  p("Fruth autoclave bags are compatible with all major sterilization methods:"),
  bullet("Gravity steam sterilization"),
  bullet("High-vacuum steam sterilization"),
  bullet("ETO (ethylene oxide) gas sterilization"),
  bullet("Chemical sterilization"),
  spacer(),
  ctaP("Contact Fruth to discuss autoclave bag specifications for your application — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What are autoclave bags made from?"),
  p("Fruth autoclave bags are manufactured from virgin polypropylene, which provides the durability and thermal stability required for high-temperature, high-pressure steam sterilization cycles."),
  spacer(),
  h3("What sterilization methods are Fruth autoclave bags compatible with?"),
  p("Our autoclave bags are compatible with gravity steam, high-vacuum steam, ETO gas, and chemical sterilization methods."),
  spacer(),
  h3("Do Fruth autoclave bags meet USP 1211 specifications?"),
  p("Yes. Fruth autoclave bags meet USP 1211 specifications and are ISO 9001:2015 certified. ASTM-tested impact and tear resistance data is available on request."),

]), "CE_Fruth_Autoclave Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 12 — MIL-PRF-131K BARRIER FILM
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("MIL-PRF-131K Barrier Film", [
  ...pageHeader("MIL-PRF-131K Barrier Film", "https://www.fruth.com/products/barrier-films/mil-prf-131k-barrier-film"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth manufactures heat-sealable barrier films designed for applications requiring superior tear and puncture resistance as well as protection from light, air, and moisture vapor."),
  spacer(),
  bullet("MIL-PRF-131K Class 1 certified"),
  bullet("Contains no amines, amides, or N-Octanoic acid"),
  bullet("Polycarbonate-compatible"),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Meets FDA and USDA food safety specifications"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("MIL-PRF-131K Class 1 Barrier Film for Defense & Industrial Applications"),
  p("Fruth MIL-PRF-131K barrier film is the military-specification packaging solution for defense contractors, government suppliers, and industrial buyers who require certified long-term protection against moisture, oxygen, and light. Class 1 certification means this material meets the U.S. Department of Defense performance standard for barrier materials used in the preservation and packaging of military hardware, electronics, and equipment."),
  spacer(),
  p("Common applications include:"),
  bullet("Military and defense hardware preservation packaging"),
  bullet("Long-term corrosion protection for metal parts and components"),
  bullet("Electronics and sensitive equipment packaging for government procurement"),
  bullet("Industrial preservation packaging requiring DOD-compliant materials"),
  bullet("Archival and long-term storage applications with moisture and oxygen barrier requirements"),
  spacer(),

  h2("MIL-PRF-131K Film Properties"),
  bullet("MIL-PRF-131K Class 1 certified — meets full DOD specification"),
  bullet("Heat-sealable for airtight, tamper-evident closures"),
  bullet("Superior tear and puncture resistance"),
  bullet("Blocks moisture vapor, oxygen, and light"),
  bullet("Contains no amines, amides, or N-Octanoic acid — safe for polycarbonate components"),
  bullet("FDA and USDA compliant materials"),
  spacer(),
  ctaP("Contact Fruth for MIL-PRF-131K barrier film specifications and custom configurations — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What does MIL-PRF-131K Class 1 mean?"),
  p("MIL-PRF-131K is a U.S. Department of Defense performance specification for barrier materials used in military packaging. Class 1 designates the highest barrier rating, providing certified protection against moisture vapor, oxygen, and light for long-term preservation of military and industrial equipment."),
  spacer(),
  h3("Why does MIL-PRF-131K film contain no amines or amides?"),
  p("Amines, amides, and N-Octanoic acid can react with or damage polycarbonate components commonly found in electronics and optical equipment. Fruth MIL-PRF-131K film is formulated without these compounds, making it safe for direct contact with polycarbonate parts."),
  spacer(),
  h3("Can Fruth produce MIL-PRF-131K film in custom sizes?"),
  p("Yes. We produce MIL-PRF-131K barrier film in custom widths, lengths, and configurations. Contact us with your specifications and application details for a quote."),

]), "CE_Fruth_MIL-PRF-131K Barrier Film v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 13 — GUSSET BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Gusset Bags", [
  ...pageHeader("Gusset Bags", "https://www.fruth.com/products/bags/gusset-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Want to get more out of each bag? Need some more space? Fruth's gusset bags are the answer. Gusset bags feature extra material on the sides or bottom, increasing both volume and structural integrity compared to flat alternatives."),
  spacer(),
  p("Side Gusset Bags:", { bold: true }),
  p("These bags resemble pillows when expanded and are particularly popular in food packaging applications."),
  spacer(),
  p("Bottom Gusset Bags:", { bold: true }),
  p("These provide a box-like shape at the base, allowing them to stand upright. They offer excellent product visibility and versatility — suitable for items ranging from coffee to pet food and consumer goods."),
  spacer(),
  bullet("Lightweight and flexible construction"),
  bullet("FDA and USDA compliant"),
  bullet("Made in the USA"),
  bullet("Heat-sealable options for security and freshness retention"),
  bullet("High clarity for product display"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Gusseted Bags for Food, Retail & Commercial Packaging"),
  p("Fruth gusseted bags are a high-capacity packaging solution for B2B buyers who need more volume, structure, and shelf presence than a flat bag can provide. The gusset design expands to accommodate larger or irregular product volumes, while maintaining a clean, professional appearance for retail display or commercial distribution."),
  spacer(),
  p("Common applications include:"),
  bullet("Coffee and specialty foods — bottom gusset bags stand upright for retail shelf presence"),
  bullet("Pet food and dry goods — side gusset construction for high-volume fills"),
  bullet("Spices and seasonings — heat-sealable options maintain freshness"),
  bullet("Consumer goods and hardware — versatile sizing for varied product dimensions"),
  spacer(),

  h2("Custom Gusset Bag Specifications"),
  p("Every bag is built to your requirements:"),
  bullet("Style — side gusset or bottom gusset"),
  bullet("Material — polyethylene film in standard or custom formulations"),
  bullet("Size — custom width, length, and gusset depth"),
  bullet("Finish — high clarity or opaque"),
  bullet("Seal — heat-sealable options available"),
  bullet("Print — unprinted or custom branded"),
  spacer(),
  ctaP("Contact Fruth for a custom gusset bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What is the difference between side gusset and bottom gusset bags?"),
  p("Side gusset bags expand at the sides and take on a pillow-like shape when filled — commonly used for coffee, snacks, and food products. Bottom gusset bags expand at the base to create a box-like, stand-up structure — ideal for retail display and products that need shelf stability."),
  spacer(),
  h3("Are gusseted bags FDA compliant?"),
  p("Yes. Fruth gusseted bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications."),
  spacer(),
  h3("Can gusset bags be custom printed?"),
  p("Yes. Fruth produces custom printed gusset bags for retail branding, product identification, and handling instructions. Any size, film type, or print configuration is available."),

]), "CE_Fruth_Gusset Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 14 — SQUARE BOTTOM BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Square Bottom Bags", [
  ...pageHeader("Square Bottom Bags", "https://www.fruth.com/products/bags/square-bottom-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth manufactures square bottom bags that merge the advantages of traditional gusseted bags with stand-up pouches — the perfect combination of a side gusset bag and stand-up pouch. These containers offer exceptional shelf stability with an easy-fill design."),
  spacer(),
  bullet("Paneled aesthetic suitable for graphics, labeling, hot stamping, and degassing valves"),
  bullet("Available in multiple colors with lamination options — foil, metallized, or clear poly"),
  bullet("Lightweight and flexible construction"),
  bullet("Clear viewing options available for product visibility"),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Meets all FDA and USDA food safety specifications"),
  bullet("Made in the USA"),
  spacer(),
  p("Suitable for food and non-food industries including ground coffee, spices, liquids, and similar products."),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Square Bottom Bags for Retail & Commercial Packaging"),
  p("Fruth square bottom bags combine the high-volume capacity of a side gusset bag with the freestanding stability of a stand-up pouch — making them a premium retail packaging choice for B2B buyers who need shelf presence, product visibility, and branding flexibility in a single format."),
  spacer(),
  p("Common applications include:"),
  bullet("Specialty coffee and tea — stand-up format with degassing valve compatibility"),
  bullet("Spices, seasonings, and dry goods — stable base for retail shelf display"),
  bullet("Liquids and semi-liquids — heat-sealable construction with lamination options"),
  bullet("Pet food and consumer goods — large-format fills with strong visual branding panel"),
  spacer(),

  h2("Custom Square Bottom Bag Specifications"),
  p("Every bag is built to your exact specifications:"),
  bullet("Size — custom width, length, and gusset depth"),
  bullet("Lamination — foil, metallized, or clear poly"),
  bullet("Color — multiple color options available"),
  bullet("Features — degassing valves, hot stamping, labeling panels"),
  bullet("Finish — clear window or opaque"),
  bullet("Print — unprinted or custom branded"),
  spacer(),
  ctaP("Contact Fruth for a custom square bottom bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What is a square bottom bag?"),
  p("A square bottom bag combines the expandable sides of a gusseted bag with a flat, stable base that allows it to stand upright. This gives the bag maximum fill volume, shelf stability, and a large flat panel for branding and graphics."),
  spacer(),
  h3("Can square bottom bags include degassing valves?"),
  p("Yes. Fruth square bottom bags support degassing valve integration, making them well suited for freshly roasted coffee and other products that off-gas after sealing."),
  spacer(),
  h3("What lamination options are available for square bottom bags?"),
  p("Fruth offers foil, metallized, and clear poly lamination options for square bottom bags, providing varying levels of barrier protection, opacity, and visual finish depending on your product requirements."),

]), "CE_Fruth_Square Bottom Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 15 — GROW BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Grow Bags", [
  ...pageHeader("Grow Bags", "https://www.fruth.com/products/bags/grow-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth Plastics manufactures reusable flexible nursery pots and grow bags as a cost-effective alternative to rigid containers. The same amount of plastic used to produce two rigid 5-gallon nursery pots can make 14 reusable flexible nursery pots."),
  spacer(),
  bullet("Available in multiple sizes — from 1-gallon seed bags up to 30-gallon bags for mature shrubs and trees"),
  bullet("Standard and custom vent hole patterns available"),
  bullet("Color options from clear to black for soil temperature control"),
  bullet("Flexible structure to promote root growth"),
  bullet("Heavy-duty, reusable construction"),
  bullet("Cost-effective and space-efficient storage"),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Made in the USA"),
  bullet("Suitable for greenhouse, nursery, and agricultural applications"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Plastic Plant Bags & Flexible Nursery Pots for Commercial Growers"),
  p("Fruth grow bags are a proven alternative to rigid nursery containers for commercial greenhouse operations, nurseries, and agricultural producers. The flexible construction promotes healthy root development, reduces container costs, and dramatically improves storage and shipping efficiency compared to rigid plastic pots."),
  spacer(),
  p("Common applications include:"),
  bullet("Greenhouse and nursery operations — seedlings through mature shrubs and trees"),
  bullet("Commercial vegetable and herb growing — 1 to 30-gallon sizes for any crop stage"),
  bullet("Tree and shrub production — large-format bags for woody plants and root ball development"),
  bullet("Agricultural and horticulture distributors — bulk orders for resale and field use"),
  spacer(),

  h2("Grow Bag Size Guide"),
  bullet("1-gallon — seed starting and early seedling development"),
  bullet("3 to 5-gallon — vegetable crops, herbs, and small shrubs"),
  bullet("7 to 15-gallon — larger shrubs, fruit trees, and perennials"),
  bullet("20 to 30-gallon — mature trees and large woody plants"),
  spacer(),
  p("Custom sizes, vent hole patterns, and colors available for commercial volume orders."),
  spacer(),
  ctaP("Contact Fruth to discuss grow bag specifications and volume pricing — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What sizes do Fruth grow bags come in?"),
  p("Fruth grow bags are available from 1-gallon seed bags up to 30-gallon bags suitable for mature shrubs and trees. Custom sizes are available for commercial nursery and greenhouse operations."),
  spacer(),
  h3("What colors are available for grow bags?"),
  p("Grow bags are available from clear to black. Color choice affects soil temperature — black bags absorb heat, while lighter colors reflect it, making color selection important for specific crop and climate requirements."),
  spacer(),
  h3("Are Fruth grow bags reusable?"),
  p("Yes. Fruth grow bags are constructed for heavy-duty, reusable use, making them a cost-effective alternative to single-use rigid nursery containers over multiple growing seasons."),

]), "CE_Fruth_Grow Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 16 — HEADER BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Header Bags", [
  ...pageHeader("Header Bags", "https://www.fruth.com/products/bags/header-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth manufactures poly header bags designed for convenient loading and easy tear-off functionality via perforated headers. These lightweight, flexible bags suit multiple industries including industrial, healthcare, business, and food service sectors."),
  spacer(),
  p("Industrial uses encompass storage and transport of small car parts, toys, decorative items, and accessories."),
  spacer(),
  bullet("Packaged on a convenient header with perforations for easy tear off"),
  bullet("Lightweight and flexible construction"),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("Meets all FDA and USDA food safety specifications"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Header Bags for Retail Display & Industrial Packaging"),
  p("Fruth poly header bags are a versatile retail and industrial packaging solution for B2B buyers who need a simple, organized, and shelf-ready format. The perforated header design allows bags to be hung on pegboard displays or dispensed one at a time from a rack — making them a practical choice for retail, hardware, and industrial distribution applications."),
  spacer(),
  p("Common applications include:"),
  bullet("Retail display — hang bags on pegboard for organized product presentation"),
  bullet("Hardware and auto parts — small components, fasteners, and accessories"),
  bullet("Toys and consumer goods — lightweight containment with visible product"),
  bullet("Food service and healthcare — FDA and USDA compliant materials for regulated applications"),
  bullet("Industrial distribution — bulk-packed header bags for high-volume dispensing"),
  spacer(),

  h2("Custom Header Bag Specifications"),
  p("Every header bag is built to your requirements:"),
  bullet("Size — custom bag width, length, and header dimensions"),
  bullet("Material — polyethylene in standard or specialty formulations"),
  bullet("Thickness — matched to product weight and handling requirements"),
  bullet("Header style — perforated for tear-off or standard hang hole"),
  bullet("Print — unprinted or custom branded"),
  spacer(),
  ctaP("Contact Fruth for a custom header bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What are header bags used for?"),
  p("Header bags are poly bags attached to a cardboard or poly header, typically hung on pegboard displays or rack systems. They are used in retail, hardware, industrial, and food service applications where organized display and easy dispensing are important."),
  spacer(),
  h3("Are Fruth header bags FDA compliant?"),
  p("Yes. Fruth header bags meet FDA and USDA food safety specifications, making them suitable for food service and healthcare applications."),
  spacer(),
  h3("Can header bags be custom sized?"),
  p("Yes. Fruth produces header bags in custom sizes to match your product dimensions and display requirements. Contact us with your specifications for a quote."),

]), "CE_Fruth_Header Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 17 — BOTTOM SEAL BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Bottom Seal Bags", [
  ...pageHeader("Bottom Seal Bags", "https://www.fruth.com/products/bags/bottom-seal-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth manufactures bottom seal bags built for strength, leak protection, and heavy-duty performance. Unlike standard flat bags, bottom seal bags are made from a section of tubing that is sealed at the bottom and then cut — a construction method that enhances durability and leak prevention."),
  spacer(),
  p("When to use bottom seal bags:"),
  bullet("Packaging items that conform to bag shape"),
  bullet("Containing heavy or rigid products"),
  bullet("Applications involving materials that exert pressure on bag sides"),
  spacer(),
  p("Industry applications include:"),
  bullet("Food processing — grain, flour, and liquids"),
  bullet("Industrial — fertilizer, aggregates (gravel, sand, recycled concrete, slag, topsoil)"),
  spacer(),
  p("Customization options:"),
  bullet("Nearly any custom size available"),
  bullet("Material choices: LDPE, FDA-approved materials, recycled poly materials"),
  bullet("Multiple printing options"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Heavy-Duty Bottom Seal Bags for Industrial & Food Packaging"),
  p("Fruth bottom seal bags are the preferred choice for B2B buyers who need stronger containment than a standard side seal bag provides. The tubing-based construction eliminates side seams — reducing leak risk and distributing load stress more evenly across the bag, making them ideal for heavy, dense, or pressure-sensitive products."),
  spacer(),
  p("Common applications include:"),
  bullet("Grain, flour, and dry food ingredients — food-grade materials, FDA compliant"),
  bullet("Liquids and semi-liquids — leak-resistant construction for wet or moist products"),
  bullet("Fertilizer and agricultural inputs — heavy-duty gauge for bulk industrial fills"),
  bullet("Aggregates and construction materials — gravel, sand, topsoil, and recycled materials"),
  bullet("Industrial chemicals and powders — strong seam construction resists pressure"),
  spacer(),

  h2("Custom Bottom Seal Bag Specifications"),
  bullet("Material — LDPE, FDA-approved, or recycled poly materials"),
  bullet("Size — custom width and length to match your product and fill equipment"),
  bullet("Thickness — heavy-duty gauge options for demanding applications"),
  bullet("Print — unprinted or custom branded with multiple print options"),
  spacer(),
  ctaP("Contact Fruth for a custom bottom seal bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What makes bottom seal bags stronger than standard bags?"),
  p("Bottom seal bags are made from a continuous tube of film sealed at the bottom, eliminating the side seams found in standard flat bags. This construction distributes stress more evenly and reduces the risk of leaks or failures under heavy loads or internal pressure."),
  spacer(),
  h3("What materials are Fruth bottom seal bags available in?"),
  p("Fruth bottom seal bags are available in LDPE, FDA-approved materials for food contact, and recycled poly materials. Material selection depends on your product type, weight, and regulatory requirements."),
  spacer(),
  h3("Are bottom seal bags suitable for food packaging?"),
  p("Yes. Fruth bottom seal bags are available in FDA-approved materials and are used in food processing applications including grain, flour, and liquid packaging."),

]), "CE_Fruth_Bottom Seal Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 18 — FRESH PRODUCE BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Fresh Produce Bags", [
  ...pageHeader("Fresh Produce Bags", "https://www.fruth.com/products/bags/fresh-produce-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth manufactures custom-designed fresh produce bags engineered to create an optimal environment, preserving the natural flavor, texture, and nutritional value of fruits and vegetables."),
  spacer(),
  bullet("Protection against oxygen, moisture, and UV rays"),
  bullet("Oxidation prevention to maintain freshness"),
  bullet("Moisture control to prevent spoilage"),
  bullet("Lightweight and flexible design"),
  bullet("ISO 9001:2015 certified manufacturer and distributor"),
  bullet("FDA and USDA food safety compliant"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Fresh Produce Packaging for Commercial Growers & Distributors"),
  p("Fruth fresh produce bags are engineered for commercial growers, packers, and distributors who need reliable barrier protection and food-safe materials at volume. Our bags are designed to maintain optimal moisture and oxygen levels around your product — extending shelf life and reducing spoilage from farm to retail."),
  spacer(),
  p("Common applications include:"),
  bullet("Fresh fruits and vegetables — oxygen and moisture barrier for extended shelf life"),
  bullet("Leafy greens and herbs — moisture control to prevent wilting and browning"),
  bullet("Retail produce packaging — clear film for product visibility at point of sale"),
  bullet("Commercial packing operations — custom sizes for high-speed filling lines"),
  bullet("Export and long-distance distribution — UV and oxygen protection for transit"),
  spacer(),

  h2("Fresh Produce Bag Features"),
  bullet("Oxygen barrier — slows oxidation and browning"),
  bullet("Moisture control — prevents both drying out and excess condensation"),
  bullet("UV protection — shields sensitive produce from light degradation"),
  bullet("FDA and USDA compliant materials — food-safe throughout"),
  bullet("Custom sizes available for any produce type or pack format"),
  spacer(),
  ctaP("Contact Fruth to discuss fresh produce bag options for your operation — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What makes fresh produce bags different from standard poly bags?"),
  p("Fresh produce bags are engineered with specific barrier properties — controlling oxygen transmission, moisture vapor, and UV exposure — to create an optimal environment for perishable products. Standard poly bags do not provide these barrier characteristics."),
  spacer(),
  h3("Are Fruth fresh produce bags FDA compliant?"),
  p("Yes. Fruth fresh produce bags meet all FDA and USDA food safety specifications and are safe for direct contact with fresh fruits and vegetables."),
  spacer(),
  h3("Can fresh produce bags be custom sized?"),
  p("Yes. Fruth produces fresh produce bags in custom sizes to match your product dimensions, pack weights, and filling equipment requirements."),

]), "CE_Fruth_Fresh Produce Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 19 — BAKERY BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Bakery Bags", [
  ...pageHeader("Bakery Bags", "https://www.fruth.com/products/bags/bakery-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Custom bakery bags designed to protect freshness and elevate your brand. Fruth bakery bags are engineered to maintain product freshness, seal out air, and guard against moisture and contaminants."),
  spacer(),
  bullet("Material: High-quality, low-density polyethylene (LDPE)"),
  bullet("Lightweight and easy to handle"),
  bullet("Recyclable"),
  bullet("ISO 9001:2015 certified manufacturer"),
  bullet("FDA and USDA food contact regulations compliant"),
  bullet("Produced under strict quality controls for cleanliness and strength"),
  bullet("Customizable sizing, thickness, color, and printing"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Bakery Bags for Commercial Bakers & Food Distributors"),
  p("Fruth bakery bags are a food-safe, brand-ready packaging solution for commercial bakeries, food distributors, and specialty food producers who need reliable freshness protection with full customization options. Made from high-quality LDPE, our bakery bags seal out air and moisture while supporting your branding through custom print, color, and sizing."),
  spacer(),
  p("Common applications include:"),
  bullet("Bread and rolls — moisture barrier to maintain softness and prevent staleness"),
  bullet("Pastries and baked goods — airtight seal to guard against contamination"),
  bullet("Specialty and artisan bakery products — custom print for branded retail presentation"),
  bullet("Wholesale and food service distribution — bulk packs for commercial operations"),
  spacer(),

  h2("Custom Bakery Bag Specifications"),
  bullet("Material — LDPE, recyclable and food-safe"),
  bullet("Size — custom width and length for any product format"),
  bullet("Thickness — matched to product weight and handling requirements"),
  bullet("Color — clear or custom colored"),
  bullet("Print — unprinted or fully custom branded"),
  spacer(),
  ctaP("Contact Fruth for a custom bakery bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What material are Fruth bakery bags made from?"),
  p("Fruth bakery bags are made from high-quality, low-density polyethylene (LDPE). The material is lightweight, food-safe, recyclable, and compliant with FDA and USDA food contact regulations."),
  spacer(),
  h3("Can bakery bags be custom printed?"),
  p("Yes. Fruth produces custom printed bakery bags for retail branding, product identification, and ingredient labeling. Custom sizing, thickness, and color options are also available."),
  spacer(),
  h3("Are Fruth bakery bags FDA compliant?"),
  p("Yes. Our bakery bags comply with FDA and USDA food contact regulations and are produced under strict quality controls for cleanliness and strength."),

]), "CE_Fruth_Bakery Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 20 — FOAM BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Foam Bags", [
  ...pageHeader("Foam Bags", "https://www.fruth.com/products/bags/foam-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth Custom Packaging offers polyethylene foam bags as protective packaging solutions — economical, versatile, and durable options designed to safeguard items during storage and transportation."),
  spacer(),
  bullet("Thickness options: 1/16 inch or 1/8 inch"),
  bullet("Tear resistant and extremely durable"),
  bullet("Non-abrasive and anti-slip — prevents shifting and scratching"),
  bullet("High cushioning and shock absorption for transit protection"),
  bullet("Water resistant and lightweight to reduce shipping costs"),
  bullet("Available in custom sizes and formats"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Polyethylene Foam Bags for Protective Industrial & Commercial Packaging"),
  p("Fruth polyethylene foam bags provide reliable cushioning and surface protection for B2B buyers who need to protect delicate, fragile, or high-value items during storage, shipping, and handling. The closed-cell foam construction absorbs impact, prevents surface scratching, and resists moisture — making it a practical and cost-effective protective packaging solution across industries."),
  spacer(),
  p("Common applications include:"),
  bullet("Electronics and instrumentation — scratch and impact protection during shipping"),
  bullet("Glassware and ceramics — cushioning to prevent breakage in transit"),
  bullet("Metal parts and precision components — non-abrasive surface protection"),
  bullet("Medical devices and instruments — gentle, clean protection for sensitive equipment"),
  bullet("Retail and e-commerce fulfillment — lightweight protective packaging that reduces shipping costs"),
  spacer(),

  h2("Foam Bag Specifications"),
  bullet("Thickness — 1/16 inch or 1/8 inch standard; custom available"),
  bullet("Material — closed-cell polyethylene foam"),
  bullet("Properties — non-abrasive, anti-slip, water resistant, tear resistant"),
  bullet("Size — custom width and length to fit your product"),
  bullet("Format — bags or sheeting available"),
  spacer(),
  ctaP("Contact Fruth for a custom foam bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What are polyethylene foam bags used for?"),
  p("Polyethylene foam bags are used to protect delicate or high-value items from scratching, impact, and moisture during storage and shipping. Common uses include electronics, glassware, metal parts, medical devices, and retail product packaging."),
  spacer(),
  h3("What thickness options are available for foam bags?"),
  p("Fruth foam bags are available in 1/16 inch and 1/8 inch standard thicknesses. Custom thickness options are available for specific cushioning requirements."),
  spacer(),
  h3("Are foam bags water resistant?"),
  p("Yes. Fruth polyethylene foam bags are water resistant, making them suitable for products that require moisture protection during storage and transportation."),

]), "CE_Fruth_Foam Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 21 — MULTI-POCKET BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Multi-Pocket Bags", [
  ...pageHeader("Multi-Pocket Bags", "https://www.fruth.com/products/bags/multi-pocket-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth custom multi-pocket bags are available in practically any color, print color, or thickness depending on your packaging needs."),
  spacer(),
  bullet("Made from highest-quality materials"),
  bullet("Often used for prescriptions and medical purposes"),
  bullet("Customizable to exact size requirements"),
  bullet("Lightweight and flexible"),
  bullet("ISO 9001:2015 certified manufacturer & distributor"),
  bullet("Meets all FDA and USDA food and safety specifications"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Multi-Pocket Bags for Pharmacy, Medical & Industrial Packaging"),
  p("Fruth multi-pocket bags are a purpose-built B2B packaging solution for organizations that need to organize, separate, or deliver multiple items in a single bag. With fully customizable pocket count, sizing, color, and print options, they are a practical choice for pharmaceutical distribution, medical supply, and industrial kit packaging."),
  spacer(),
  p("Common applications include:"),
  bullet("Prescription and pharmacy packaging — organize medication by dose or day"),
  bullet("Medical and clinical supply kits — separate components in a single sealed unit"),
  bullet("Industrial parts kits — group components, hardware, or accessories"),
  bullet("Document and form packets — organize multi-part paperwork for distribution"),
  bullet("Retail and promotional kits — custom printed multi-pocket bags for branded packaging"),
  spacer(),

  h2("Custom Multi-Pocket Bag Specifications"),
  bullet("Pocket count — two or more pockets, any configuration"),
  bullet("Size — custom dimensions per pocket and overall bag"),
  bullet("Material — FDA and USDA compliant; food-safe options available"),
  bullet("Color — available in virtually any color"),
  bullet("Print — custom single or multi-color print"),
  bullet("Thickness — matched to product weight and handling requirements"),
  spacer(),
  ctaP("Contact Fruth for a custom multi-pocket bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What are multi-pocket bags used for?"),
  p("Multi-pocket bags are used to organize and separate multiple items within a single bag. Common applications include prescription and pharmacy packaging, medical supply kits, industrial parts kits, and retail promotional packaging."),
  spacer(),
  h3("Can multi-pocket bags be custom printed?"),
  p("Yes. Fruth produces multi-pocket bags in virtually any color and print configuration. Custom sizing and pocket layout are also available to match your specific application."),
  spacer(),
  h3("Are Fruth multi-pocket bags FDA compliant?"),
  p("Yes. Fruth multi-pocket bags meet FDA and USDA food and safety specifications and are manufactured under ISO 9001:2015 certified quality controls."),

]), "CE_Fruth_Multi-Pocket Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 22 — TAMPER EVIDENT BAGS
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Tamper Evident Bags", [
  ...pageHeader("Tamper Evident Bags", "https://www.fruth.com/products/bags/tamper-evident-bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Used for a variety of markets such as pharmaceutical distribution or document transfers, our tamper-evident bags are engineered to prevent side-breach and reseal attempts providing uncompromising protection for products."),
  spacer(),
  p("Designed to reveal any signs of tampering or unauthorized access, these bags ensure the integrity of items throughout storage, transportation, and delivery."),
  spacer(),
  bullet("ISO 9001:2015 certified manufacturer & distributor"),
  bullet("Lightweight and flexible"),
  bullet("Meets all FDA and USDA food and safety specifications"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Tamper Evident Bags for Pharmaceutical, Document & Secure Delivery Applications"),
  p("Fruth tamper evident bags are engineered for B2B buyers who need a verifiable chain of custody — whether moving pharmaceuticals through distribution, securing sensitive documents, or protecting high-value retail merchandise. Our bags are constructed to resist side-breach and reseal attempts, providing visible evidence of unauthorized access at every stage of the supply chain."),
  spacer(),
  p("Common applications include:"),
  bullet("Pharmaceutical distribution — verifiable seal for drug and sample chain of custody"),
  bullet("Document security — legal, medical, and financial records in transit"),
  bullet("Cash and currency handling — bank and retail deposit bag applications"),
  bullet("Retail and e-commerce returns — prevent unauthorized access during return transit"),
  bullet("Evidence and specimen bags — integrity verification for sensitive samples"),
  spacer(),

  h2("Tamper Evident Bag Features"),
  bullet("Side-breach resistant construction — engineered to prevent unauthorized entry from sides"),
  bullet("Reseal resistant — visible evidence if opening is attempted"),
  bullet("Lightweight and flexible for easy handling"),
  bullet("FDA and USDA compliant materials"),
  bullet("Custom sizing, print, and security feature options"),
  spacer(),
  ctaP("Contact Fruth for a custom tamper evident bag quote — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("How do tamper evident bags show signs of tampering?"),
  p("Fruth tamper evident bags are engineered to resist side-breach and reseal attempts. If the bag is opened or an entry is attempted, the bag construction makes tampering visible — providing verifiable evidence of unauthorized access."),
  spacer(),
  h3("What industries use tamper evident bags?"),
  p("Common users include pharmaceutical distributors, healthcare providers, financial institutions, legal and document management firms, and retailers who require chain-of-custody verification or loss prevention packaging."),
  spacer(),
  h3("Can tamper evident bags be custom sized and printed?"),
  p("Yes. Fruth produces tamper evident bags in custom sizes with print options for barcodes, sequential numbering, branding, and security features. Contact us with your requirements for a quote."),

]), "CE_Fruth_Tamper Evident Bags v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 23 — SCRIM FOIL BARRIER FILM
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Scrim Foil Barrier Film", [
  ...pageHeader("Scrim Foil Barrier Film", "https://www.fruth.com/products/barrier-films/scrim-foil-barrier-film"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth Custom Packaging offers custom scrim foil designed as a sealing laminate backing for use on fibrous and sheet metal ducts. The product is intended for joining seams on foil-faced fiberglass ductwork insulation or repairing damaged insulation."),
  spacer(),
  bullet("Easy application to both fibrous and sheet metal ductwork"),
  bullet("No special tools or installation methods required"),
  bullet("Highly resistant to moisture, mold, and vapors"),
  bullet("Surface adhesion tape provides enhanced sealing performance in challenging environments"),
  bullet("ISO 9001:2015 certified manufacturer"),
  bullet("Made in the USA"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Custom Scrim Foil for HVAC Ductwork & Mechanical Insulation"),
  p("Fruth scrim foil barrier film is a commercial and industrial sealing laminate used by HVAC contractors, mechanical insulation installers, and building products distributors who need reliable, high-performance sealing for foil-faced ductwork. Designed for both fibrous and sheet metal duct applications, it installs without special tools and delivers consistent performance in demanding environments."),
  spacer(),
  p("Common applications include:"),
  bullet("Foil-faced fiberglass ductwork — sealing and joining duct seams"),
  bullet("Sheet metal ductwork — surface laminate for moisture and vapor barriers"),
  bullet("Insulation repair — restoring damaged foil-faced insulation"),
  bullet("Mechanical insulation — moisture and mold protection for HVAC systems"),
  bullet("Commercial construction — vapor barrier sealing in demanding environments"),
  spacer(),

  h2("Scrim Foil Barrier Film Specifications"),
  bullet("Application — fibrous and sheet metal ducts"),
  bullet("Performance — moisture, mold, and vapor resistant"),
  bullet("Installation — no special tools required"),
  bullet("Adhesion — surface adhesion tape for enhanced sealing in challenging conditions"),
  bullet("Custom configurations available"),
  spacer(),
  ctaP("Contact Fruth for scrim foil pricing and specifications — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What is scrim foil barrier film used for?"),
  p("Scrim foil barrier film is a sealing laminate used on foil-faced fiberglass ductwork and sheet metal ducts in HVAC and mechanical insulation applications. It is used to seal duct seams, repair damaged insulation, and provide moisture and vapor barrier protection."),
  spacer(),
  h3("Does Fruth scrim foil require special tools to install?"),
  p("No. Fruth scrim foil is designed for straightforward application to fibrous and sheet metal ductwork without special tools or installation methods."),
  spacer(),
  h3("Is Fruth scrim foil resistant to moisture and mold?"),
  p("Yes. Fruth scrim foil barrier film is highly resistant to moisture, mold, and vapors, making it suitable for HVAC and mechanical insulation environments where long-term performance is required."),

]), "CE_Fruth_Scrim Foil Barrier Film v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 24 — KRAFT FOIL BARRIER FILM
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Kraft Foil Barrier Film", [
  ...pageHeader("Kraft Foil Barrier Film", "https://www.fruth.com/products/barrier-films/kraft-foil-barrier-film"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("Fruth Custom Packaging offers kraft foil barrier film manufactured to MIL-PRF-131 TI C2 specifications. The product serves industries requiring single-use or single-serve packaging solutions."),
  spacer(),
  p("Frequently used when protection from grease is required."),
  spacer(),
  p("Applications include:"),
  bullet("Snacks"),
  bullet("Seasonings and condiments"),
  bullet("Cosmetics"),
  bullet("Nutritional and sports nutrition products"),
  bullet("Dietary supplements"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing content on the page."),
  spacer(),

  h2("Kraft Foil Barrier Film for Food, Supplement & Cosmetic Packaging"),
  p("Fruth kraft foil barrier film is a flexible packaging material for B2B buyers in the food, supplement, and personal care industries who need grease resistance, moisture protection, and a premium kraft-foil aesthetic in a single-use or single-serve format. Manufactured to MIL-PRF-131 TI C2 specifications, it delivers reliable barrier performance for sensitive products."),
  spacer(),
  p("Common applications include:"),
  bullet("Snack packaging — grease and moisture barrier for chips, nuts, and dry snacks"),
  bullet("Seasonings and condiments — portion packs and single-serve sachets"),
  bullet("Nutritional and sports nutrition products — protein powder, pre-workout, and supplement sachets"),
  bullet("Dietary supplements — vitamins, powders, and single-dose packets"),
  bullet("Cosmetics and personal care — sample sachets and single-use applicator packs"),
  spacer(),

  h2("Kraft Foil Barrier Film Specifications"),
  bullet("Standard — MIL-PRF-131 TI C2"),
  bullet("Barrier properties — grease resistant, moisture protection"),
  bullet("Format — single-use and single-serve packaging applications"),
  bullet("Custom widths, lengths, and configurations available"),
  spacer(),
  ctaP("Contact Fruth for kraft foil barrier film pricing and specifications — we respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("What is kraft foil barrier film used for?"),
  p("Kraft foil barrier film is used for single-use and single-serve flexible packaging in the food, supplement, and cosmetic industries. It provides grease resistance and moisture protection for products like snacks, seasonings, nutritional supplements, and cosmetic sachets."),
  spacer(),
  h3("What specification does Fruth kraft foil barrier film meet?"),
  p("Fruth kraft foil barrier film is manufactured to MIL-PRF-131 TI C2 specifications, a recognized standard for barrier material performance."),
  spacer(),
  h3("Can kraft foil barrier film be produced in custom sizes?"),
  p("Yes. Fruth produces kraft foil barrier film in custom widths, lengths, and configurations to match your packaging equipment and product requirements. Contact us for specifications and a quote."),

]), "CE_Fruth_Kraft Foil Barrier Film v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 25 — BAGS HUB PAGE (poly bag manufacturer)
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Bags — Product Hub", [
  ...pageHeader("Bags — Product Hub Page", "https://www.fruth.com/products/bags"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("We a full range of custom plastic bags engineered to meet the performance, compliance, and durability requirements of industrial, food, medical, and cleanroom applications."),
  spacer(),
  p("Fruth's bags are produced to exact specifications for size, material, sealing method, and functionality."),
  spacer(),
  p("Whether you need high-temperature resistance, electrostatic protection, moisture control, or automated packaging compatibility. We deliver reliable bag solutions designed for efficiency and protection."),
  spacer(),
  p("Products: Autoclave Bags, Bakery Bags, Bottom Seal Bags, Fresh Produce Bags, Cleanroom Bags, Foam Bags, Grow Bags, Gusset Bags, Header Bags, Lay Flat Bags, Lip & Tape Bags, Multi Pocket Bags, Side Seal Bags, Square Bottom Bags, Tamper Evident Bags, Vacuum Seal Bags, Wicketed Bags, Zipper Bags"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing product grid on the page."),
  spacer(),

  h2("Custom Poly Bag Manufacturer for Industrial, Food, Medical & Cleanroom Applications"),
  p("Fruth is a U.S.-based poly bag manufacturer producing custom plastic bags for B2B buyers across industrial, food processing, medical, and cleanroom sectors. Every bag we produce is built to specification — from material selection and sealing method to size, thickness, and print — so you get exactly the bag your application requires, not a catalog compromise."),
  spacer(),
  p("We support buyers at all stages of the supply chain: procurement teams sourcing in volume, operations teams optimizing line efficiency, and quality teams meeting FDA, USDA, or cleanroom compliance requirements."),
  spacer(),

  h2("Why B2B Buyers Choose Fruth"),
  bullet("U.S. manufacturer — domestic production with shorter lead times and supply chain reliability"),
  bullet("ISO 9001:2015 certified — documented quality management across all production"),
  bullet("FDA and USDA compliant materials — food-safe and healthcare-ready formulations"),
  bullet("Full customization — size, material, sealing method, thickness, color, and print"),
  bullet("18 bag types — from autoclave and cleanroom to wicketed and tamper evident"),
  bullet("Engineering support — specification guidance for demanding or regulated applications"),
  spacer(),

  h2("Poly Bag Capabilities at a Glance"),
  bullet("Materials — LDPE, HDPE, nylon/poly laminates, multi-layer barrier films, specialty formulations"),
  bullet("Sealing — bottom seal, side seal, gusset, lip and tape, zipper, wicketed, and more"),
  bullet("Compliance — FDA, USDA, cleanroom, autoclave, and electrostatic protection options"),
  bullet("Custom print — single and multi-color, barcodes, sequential numbering"),
  bullet("Volume — standard and high-volume production runs"),
  spacer(),
  ctaP("Request a custom bag quote — contact Fruth at (714) 993-9955 or sales@fruth.com. We respond within one business day."),
  spacer(),
  divider(),
  spacer(),

  h2("Frequently Asked Questions"),
  spacer(),
  h3("Is Fruth a U.S.-based poly bag manufacturer?"),
  p("Yes. Fruth is a domestic custom poly bag manufacturer based in Placentia, California. All bags are made in the USA under ISO 9001:2015 certified quality controls."),
  spacer(),
  h3("What types of custom plastic bags does Fruth produce?"),
  p("Fruth produces 18 bag types including autoclave bags, cleanroom bags, vacuum seal bags, wicketed bags, gusset bags, zipper bags, tamper evident bags, and more. All are available in custom sizes, materials, and configurations."),
  spacer(),
  h3("Can Fruth produce bags that meet FDA or USDA requirements?"),
  p("Yes. Fruth produces bags in FDA and USDA compliant materials for food, medical, and pharmaceutical applications. Cleanroom-compatible and electrostatic protection options are also available."),

]), "CE_Fruth_Bags Hub v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// PAGE 26 — HOMEPAGE
// ══════════════════════════════════════════════════════════════════════════
save(makeDoc("Homepage", [
  ...pageHeader("Homepage", "https://www.fruth.com"),

  labelBanner("BEFORE  —  Current Content (Preserved)", GRAY_BG),
  spacer(),
  p("From the extraordinary to the ordinary and in our most critical moments"),
  spacer(),
  p("Fruth makes life happen."),
  spacer(),
  p("Custom Packaging Solutions That Fuel Life."),
  spacer(),
  p("Our engineering expertise, customization capabilities, and rapid go-to-market resources makes Fruth the packaging industry's go-to partner."),
  spacer(),
  p("The company specializes in custom bags and barrier film across the country for medical and food companies."),
  spacer(),
  p("Products: Cleanroom Bags, Films (poly rolls), Barrier Films"),
  spacer(),
  p("Industries: Medical/Pharmacy, Agriculture, Industrial, Cleanroom, Electronics"),
  spacer(),
  p("Capabilities: Extrusion, Packaging Conversion, Package Printing, Reprocessing, Tooling, Customization"),
  spacer(),
  p("FDA Compliant & ISO Certified Cleanroom packaging"),
  spacer(),
  divider(),
  spacer(),

  labelBanner("AFTER  —  New Content (Add Below Existing)", FRUTH_RED),
  spacer(),
  note("Add the following sections below the existing hero/intro content — before or after the product category grid."),
  spacer(),

  h2("Custom Packaging Manufacturer for Industrial, Food, Medical & Electronics Applications"),
  p("Fruth is a U.S.-based custom packaging manufacturer producing flexible bags and barrier films for B2B buyers who need specification-grade materials, regulatory compliance, and domestic supply chain reliability. From FDA-compliant food bags to MIL-spec barrier films and cleanroom-certified packaging, we build to your exact requirements — not to a catalog."),
  spacer(),
  p("We serve procurement teams, operations managers, and quality engineers across:"),
  bullet("Industrial and manufacturing — heavy-duty bags and barrier films for harsh environments"),
  bullet("Food and beverage processing — FDA and USDA compliant bags and films"),
  bullet("Medical and pharmaceutical — cleanroom bags, autoclave bags, and tamper evident packaging"),
  bullet("Electronics — electrostatic protection bags and barrier films for sensitive components"),
  bullet("Agriculture — grow bags, fresh produce bags, and VCI packaging solutions"),
  spacer(),

  h2("Why Fruth"),
  bullet("Made in the USA — domestic manufacturing in Placentia, California"),
  bullet("ISO 9001:2015 certified — quality management across all production"),
  bullet("FDA and USDA compliant — food-safe and healthcare-ready materials"),
  bullet("Full specification customization — size, material, sealing, thickness, print"),
  bullet("Engineering support — guidance for regulated and performance-critical applications"),
  bullet("Rapid go-to-market — production and distribution resources for time-sensitive programs"),
  spacer(),
  ctaP("Contact Fruth to discuss your packaging requirements — (714) 993-9955 or sales@fruth.com."),

]), "CE_Fruth_Homepage v1-1.docx");


// ══════════════════════════════════════════════════════════════════════════
// SCHEMA LIBRARY DOCUMENT
// ══════════════════════════════════════════════════════════════════════════
const schemaChildren = [
  new Paragraph({
    heading: HeadingLevel.HEADING_1,
    children: [new TextRun({ text: "Fruth.com — FAQ Schema Library", color: BLACK })]
  }),
  p("This file contains all FAQ schema JSON for fruth.com product pages. Paste each block into the page's JSON-LD / schema field in the CMS. This file is for internal/developer use only — do not share with client contacts.", { color: "555555", italics: true }),
  spacer(),
  p("Last updated: June 16, 2026", { color: "888888" }),
  spacer(),
  new Paragraph({
    border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: FRUTH_RED, space: 1 } },
    children: [new TextRun("")]
  }),
  spacer(),
];

schemaEntries.forEach((entry, i) => {
  schemaChildren.push(
    new Paragraph({
      children: [new TextRun({ text: entry.title, bold: true, size: 28, color: FRUTH_RED })]
    }),
    new Paragraph({
      children: [new TextRun({ text: entry.url, size: 20, color: MID_BLUE })]
    }),
    spacer(),
    new Paragraph({
      shading: { fill: "F4F4F4", type: ShadingType.CLEAR },
      children: [new TextRun({ text: entry.json, font: "Courier New", size: 16, color: "222222" })]
    }),
    spacer(),
  );
  if (i < schemaEntries.length - 1) {
    schemaChildren.push(
      new Paragraph({
        border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: "CCCCCC", space: 1 } },
        children: [new TextRun("")]
      }),
      spacer(),
    );
  }
});

save(new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 32, bold: true, font: "Arial", color: BLACK },
        paragraph: { spacing: { before: 240, after: 120 }, outlineLevel: 0 } },
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1080, right: 1080, bottom: 1080, left: 1080 }
      }
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          children: [
            new TextRun({ text: "Fruth.com — Schema Library", bold: true, color: DARK_BLUE, size: 20 }),
            new TextRun({ text: "\tINTERNAL / DEVELOPER USE ONLY", color: FRUTH_RED, bold: true, size: 18 }),
          ],
          tabStops: [{ type: "right", position: 9360 }],
          border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: FRUTH_RED, space: 1 } }
        })]
      })
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          children: [
            new TextRun({ text: "Fruth Schema Library  |  ", color: "888888", size: 18 }),
            new TextRun({ text: "Page ", color: "888888", size: 18 }),
            new TextRun({ children: [PageNumber.CURRENT], color: "888888", size: 18 }),
          ]
        })]
      })
    },
    children: schemaChildren
  }]
}), "CE_Fruth_Schema-Library v1-1.docx");

console.log("\nAll 7 files created successfully.");
