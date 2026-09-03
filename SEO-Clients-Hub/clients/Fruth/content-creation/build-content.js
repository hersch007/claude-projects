const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  HeadingLevel, AlignmentType, BorderStyle, WidthType, ShadingType,
  LevelFormat, PageNumber, Header, Footer
} = require("docx");
const fs = require("fs");

const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };

function h1(text) {
  return new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun(text)] });
}
function h2(text) {
  return new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun(text)] });
}
function h3(text) {
  return new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun(text)] });
}
function p(text, opts = {}) {
  return new Paragraph({ children: [new TextRun({ text, ...opts })] });
}
function spacer() {
  return new Paragraph({ children: [new TextRun("")] });
}
function bullet(text, numbering) {
  return new Paragraph({ numbering: { reference: numbering, level: 0 }, children: [new TextRun(text)] });
}
function labelValue(label, value) {
  return new Paragraph({
    children: [
      new TextRun({ text: label + ": ", bold: true }),
      new TextRun(value),
    ]
  });
}
function divider() {
  return new Paragraph({
    border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: "2E75B6", space: 1 } },
    children: [new TextRun("")]
  });
}

function keywordTable(rows) {
  const colWidths = [3000, 1200, 1200, 1800, 2160];
  const headerShade = { fill: "1F4E79", type: ShadingType.CLEAR };
  const altShade = { fill: "EBF3FB", type: ShadingType.CLEAR };
  const cellMargins = { top: 80, bottom: 80, left: 120, right: 120 };

  function headerCell(text) {
    return new TableCell({
      borders,
      width: { size: colWidths[["Keyword", "Volume", "SD", "Priority", "Notes/Intent"].indexOf(text)], type: WidthType.DXA },
      shading: headerShade,
      margins: cellMargins,
      children: [new Paragraph({ children: [new TextRun({ text, bold: true, color: "FFFFFF" })] })]
    });
  }
  function dataCell(text, idx, shade) {
    return new TableCell({
      borders,
      width: { size: colWidths[idx], type: WidthType.DXA },
      shading: shade ? altShade : { fill: "FFFFFF", type: ShadingType.CLEAR },
      margins: cellMargins,
      children: [new Paragraph({ children: [new TextRun(text)] })]
    });
  }

  return new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: colWidths,
    rows: [
      new TableRow({
        tableHeader: true,
        children: ["Keyword", "Volume", "SD", "Priority", "Notes/Intent"].map(t => headerCell(t))
      }),
      ...rows.map((row, i) =>
        new TableRow({
          children: row.map((cell, j) => dataCell(cell, j, i % 2 === 1))
        })
      )
    ]
  });
}

const doc = new Document({
  numbering: {
    config: [
      {
        reference: "bullets",
        levels: [{ level: 0, format: LevelFormat.BULLET, text: "•", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } }]
      }
    ]
  },
  styles: {
    default: { document: { run: { font: "Arial", size: 24 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 36, bold: true, font: "Arial", color: "1F4E79" },
        paragraph: { spacing: { before: 360, after: 120 }, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 28, bold: true, font: "Arial", color: "2E75B6" },
        paragraph: { spacing: { before: 240, after: 120 }, outlineLevel: 1 } },
      { id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 24, bold: true, font: "Arial", color: "333333" },
        paragraph: { spacing: { before: 180, after: 80 }, outlineLevel: 2 } },
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
      }
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          children: [
            new TextRun({ text: "Fruth.com — SEO Content Expansion", bold: true, color: "1F4E79" }),
            new TextRun({ text: "\t" + "Confidential | Richard", color: "888888" }),
          ],
          tabStops: [{ type: "right", position: 9360 }],
          border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: "2E75B6", space: 1 } }
        })]
      })
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          children: [
            new TextRun({ text: "Page ", color: "888888" }),
            new TextRun({ children: [PageNumber.CURRENT], color: "888888" }),
            new TextRun({ text: " of ", color: "888888" }),
            new TextRun({ children: [PageNumber.TOTAL_PAGES], color: "888888" }),
            new TextRun({ text: "\tGenerated: June 1, 2026", color: "888888" }),
          ],
          tabStops: [{ type: "right", position: 9360 }]
        })]
      })
    },
    children: [

      // ── COVER / INTRO ──────────────────────────────────────────────
      h1("Fruth.com SEO Content Expansion"),
      p("This document contains ready-to-publish content briefs for fruth.com product pages. Each entry includes keyword targets, updated metadata, full page copy (existing + additions), and FAQ schema. Content is written for B2B buyers: manufacturers, distributors, and industrial processors.", { color: "444444" }),
      spacer(),
      labelValue("Client", "Fruth Custom Packaging — fruth.com"),
      labelValue("Prepared by", "Richard"),
      labelValue("Date", "June 1, 2026"),
      labelValue("Tracking period", "May 2, 2026 – June 1, 2026"),
      spacer(),
      divider(),
      spacer(),

      // ── PAGE 1: VACUUM SEAL BAGS ───────────────────────────────────
      h1("Page 1 of [N]: Vacuum Seal Bags"),
      labelValue("URL", "https://www.fruth.com/products/bags/vacuum-seal-bags"),
      labelValue("Status", "Live — low word count (audit critical error)"),
      spacer(),

      // Keyword Targets
      h2("Keyword Targets"),
      keywordTable([
        ["vacuum seal bags",          "49,500", "42", "Primary",   "High-volume, winnable — not ranked"],
        ["custom vacuum seal bags",   "140",    "19", "Secondary", "Exact match for Fruth offering"],
        ["heavy duty plastic bags",   "1,000",  "35", "Supporting","B2B industrial intent"],
        ["custom poly bags",          "880",    "28", "Supporting","Broad B2B buyer term"],
        ["custom flexible packaging", "210",    "33", "Supporting","Brand-level signal"],
      ]),
      spacer(),

      // Metadata
      h2("Updated Metadata"),
      labelValue("Title Tag", "Custom Vacuum Seal Bags | Industrial & Commercial Packaging | Fruth"),
      labelValue("H1", "Custom Vacuum Seal Bags"),
      labelValue("Meta Description", "Fruth manufactures custom vacuum seal bags for food, industrial, and commercial applications. ISO 9001 certified, FDA/USDA compliant, Made in USA. Request a quote today."),
      spacer(),

      // Full Page Content
      h2("Full Page Content (Existing + New)"),
      p("The content below is the complete page as it should appear — existing copy preserved, new sections added. All new content is clearly labeled.", { color: "555555", italics: true }),
      spacer(),

      h3("H1: Custom Vacuum Seal Bags"),
      spacer(),

      p("--- EXISTING CONTENT (keep as-is) ---", { bold: true, color: "888888" }),
      spacer(),
      p("Fruth Custom Packaging offers vacuum seal bags engineered to revolutionize storage, preserving flavor, texture, and nutrients. These bags feature airtight sealing technology that locks out oxygen and moisture, keeping your products fresher for longer — ideal for fruits, vegetables, meats, and seafood."),
      spacer(),
      bullet("ISO 9001:2015 certified manufacturer and distributor", "bullets"),
      bullet("Produces standard and custom lay flat bags", "bullets"),
      bullet("Lightweight and flexible design", "bullets"),
      bullet("Meets FDA and USDA food safety specifications", "bullets"),
      bullet("Made in the USA", "bullets"),
      spacer(),

      p("--- NEW CONTENT (add below existing) ---", { bold: true, color: "2E75B6" }),
      spacer(),
      h3("Custom Vacuum Seal Bags for Industrial & Commercial Applications"),
      p("Beyond food storage, Fruth manufactures custom vacuum seal bags for B2B buyers across manufacturing, distribution, medical, and industrial sectors. Every bag is built to your specifications — custom sizes, materials, and seal types with no limitation to catalog dimensions."),
      spacer(),
      p("Customize by:", { bold: true }),
      bullet("Material — polyethylene (PE), nylon/poly laminate, or multi-layer barrier film", "bullets"),
      bullet("Size — width, length, and gusset to your exact dimensions", "bullets"),
      bullet("Seal type — bottom seal, side seal, or zipper reseal", "bullets"),
      bullet("Barrier properties — moisture, oxygen, UV, or ESD-safe configurations", "bullets"),
      bullet("Print — clear/unprinted or custom branded", "bullets"),
      spacer(),
      p("We serve manufacturers, distributors, and processors who need consistent, high-quality flexible packaging at volume. As a U.S.-based manufacturer, Fruth offers competitive pricing, responsive quoting, and production built around your timeline."),
      spacer(),
      p("Request a custom quote and we will respond within one business day.", { bold: true }),
      spacer(),

      // FAQ
      h2("FAQ Section (add below product copy)"),
      spacer(),
      h3("Q: What materials are Fruth vacuum seal bags made from?"),
      p("We manufacture vacuum seal bags from polyethylene (PE), nylon/poly laminates, and multi-layer high-barrier films. FDA-compliant and ESD-safe material options are available depending on your application."),
      spacer(),
      h3("Q: Can you produce vacuum seal bags in custom sizes?"),
      p("Yes. All bags are manufactured to your specified dimensions — width, length, and gusset. We are not limited to pre-set catalog sizes."),
      spacer(),
      h3("Q: How do I get a quote?"),
      p("Contact us via the form on this page or call (714) 993-9955. Provide your size, material, quantity, and any special requirements and we will respond within one business day."),
      spacer(),

      // FAQ Schema
      h2("FAQ Schema Code (add to page <head> or via CMS schema field)"),
      p("Paste the code block below into the page's custom schema / JSON-LD field in your CMS:", { italics: true, color: "555555" }),
      spacer(),
      new Paragraph({
        children: [new TextRun({
          text: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What materials are Fruth vacuum seal bags made from?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Fruth manufactures vacuum seal bags from polyethylene (PE), nylon/poly laminates, and multi-layer high-barrier films. FDA-compliant and ESD-safe material options are available."
      }
    },
    {
      "@type": "Question",
      "name": "Can you produce vacuum seal bags in custom sizes?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. All bags are manufactured to your specified dimensions. We are not limited to pre-set catalog sizes."
      }
    },
    {
      "@type": "Question",
      "name": "How do I get a quote?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Contact us or call (714) 993-9955. Provide your size, material, quantity, and special requirements and we will respond within one business day."
      }
    }
  ]
}
</script>`,
          font: "Courier New",
          size: 18,
          color: "333333"
        })],
        shading: { fill: "F4F4F4", type: ShadingType.CLEAR }
      }),
      spacer(),
      divider(),
      spacer(),

      // ── PAGE 2: BLACK CONDUCTIVE FILM ─────────────────────────────
      h1("Page 2 of [N]: Black Conductive Film"),
      labelValue("URL", "https://www.fruth.com/products/films/black-conductive-film"),
      labelValue("Status", "Live — low word count (audit critical error)"),
      spacer(),

      h2("Keyword Targets"),
      keywordTable([
        ["anti static packaging",   "4,400", "29", "Primary",   "High-volume, winnable — not ranked"],
        ["anti static bags",        "4,400", "30", "Primary",   "Same intent, same volume — use both"],
        ["esd packaging",           "260",   "20", "Secondary", "Electronics/defense buyer term"],
        ["static shielding bags",   "390",   "33", "Supporting","Specific product type — B2B"],
        ["conductive packaging",    "10",    "25", "Supporting","Brand signal — low volume but on-page"],
      ]),
      spacer(),

      h2("Updated Metadata"),
      labelValue("Title Tag", "Anti Static & Black Conductive Film | ESD Packaging | Fruth"),
      labelValue("H1", "Black Conductive Film & Anti Static Packaging"),
      labelValue("Meta Description", "Fruth manufactures black conductive film and anti static packaging for electronics, defense, and industrial applications. MIL-spec compliant, ISO 9001 certified, Made in USA. Request a quote."),
      spacer(),

      h2("Full Page Content (Existing + New)"),
      p("The content below is the complete page as it should appear — existing copy preserved, new sections added. All new content is clearly labeled.", { color: "555555", italics: true }),
      spacer(),

      h3("H1: Black Conductive Film & Anti Static Packaging"),
      spacer(),

      p("--- EXISTING CONTENT (keep as-is) ---", { bold: true, color: "888888" }),
      spacer(),
      p("Fruth manufactures black conductive film designed to protect items vulnerable to static electricity damage. The material features a non-sparking surface perfect for use with chemicals, powder explosives, and sensitive electronics."),
      spacer(),
      p("Constructed from carbon-loaded polyethylene in a single layer, our black conductive film complies with MIL DTL 82646, MIL DTL 82647, and NFPA-56A standards and maintains performance regardless of humidity or aging conditions."),
      spacer(),
      bullet("Temperature range: -20 degrees F to 100 degrees F", "bullets"),
      bullet("Thickness: 20 microns", "bullets"),
      bullet("Available as bags, tubing, or sheeting (continuous rolls)", "bullets"),
      bullet("Good UV resistance", "bullets"),
      bullet("Corrosion resistant", "bullets"),
      bullet("Groundable for static dissipation", "bullets"),
      bullet("ISO 9001:2015 certified manufacturer", "bullets"),
      bullet("Made in the USA", "bullets"),
      spacer(),

      p("--- NEW CONTENT (add below existing) ---", { bold: true, color: "2E75B6" }),
      spacer(),
      h3("Anti Static & ESD Packaging for B2B Applications"),
      p("Fruth black conductive film is a trusted anti static packaging solution for B2B buyers in electronics manufacturing, defense, aerospace, and industrial sectors. When electrostatic discharge (ESD) poses a risk to your components or materials, our conductive film provides a reliable, MIL-spec compliant barrier."),
      spacer(),
      p("Common applications include:"),
      bullet("Printed circuit boards (PCBs) and electronic assemblies", "bullets"),
      bullet("Sensitive sensors and semiconductor components", "bullets"),
      bullet("Explosive or flammable powder containment", "bullets"),
      bullet("Defense and aerospace component packaging", "bullets"),
      bullet("Industrial parts requiring static-free storage or shipment", "bullets"),
      spacer(),
      h3("Custom Configurations Available"),
      p("We produce black conductive film in bags, lay flat tubing, and sheeting to match your production or shipping workflow. Custom sizing, gauge specifications, and print options are available for OEM and distributor accounts."),
      spacer(),
      p("Contact Fruth for a custom quote — we respond within one business day.", { bold: true }),
      spacer(),

      h2("FAQ Section (add below product copy)"),
      spacer(),
      h3("Q: What is black conductive film used for?"),
      p("Black conductive film is used to protect items sensitive to electrostatic discharge (ESD) — including electronics, semiconductors, and explosive or flammable materials. The carbon-loaded polyethylene construction dissipates static charge and provides a non-sparking surface."),
      spacer(),
      h3("Q: Does Fruth black conductive film meet military specifications?"),
      p("Yes. Our black conductive film complies with MIL DTL 82646, MIL DTL 82647, and NFPA-56A standards. Performance is consistent regardless of humidity or aging conditions."),
      spacer(),
      h3("Q: What formats is black conductive film available in?"),
      p("We produce black conductive film as bags, tubing, or sheeting on continuous rolls. Custom sizes and configurations are available — contact us with your specifications."),
      spacer(),

      h2("FAQ Schema Code (add to page <head> or via CMS schema field)"),
      p("Paste the code block below into the page's custom schema / JSON-LD field in your CMS:", { italics: true, color: "555555" }),
      spacer(),
      new Paragraph({
        children: [new TextRun({
          text: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is black conductive film used for?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Black conductive film protects items sensitive to electrostatic discharge (ESD) including electronics, semiconductors, and explosive or flammable materials. The carbon-loaded polyethylene construction dissipates static charge and provides a non-sparking surface."
      }
    },
    {
      "@type": "Question",
      "name": "Does Fruth black conductive film meet military specifications?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. Fruth black conductive film complies with MIL DTL 82646, MIL DTL 82647, and NFPA-56A standards. Performance is consistent regardless of humidity or aging conditions."
      }
    },
    {
      "@type": "Question",
      "name": "What formats is black conductive film available in?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Black conductive film is available as bags, tubing, or sheeting on continuous rolls. Custom sizes and configurations are available on request."
      }
    }
  ]
}
</script>`,
          font: "Courier New",
          size: 18,
          color: "333333"
        })],
        shading: { fill: "F4F4F4", type: ShadingType.CLEAR }
      }),
      spacer(),
      divider(),
      spacer(),

      // ── PAGE 3: ZIPPER BAGS ───────────────────────────────────────
      h1("Page 3 of [N]: Zipper Bags"),
      labelValue("URL", "https://www.fruth.com/products/bags/zipper-bags"),
      labelValue("Status", "Live — low word count (audit critical error)"),
      spacer(),

      h2("Keyword Targets"),
      keywordTable([
        ["zipper bags",             "5,400", "38", "Primary",   "High-volume, not ranked — winnable"],
        ["custom poly bags",        "880",   "28", "Secondary", "B2B buyer intent, low difficulty"],
        ["custom plastic bags",     "1,900", "42", "Secondary", "Broad B2B buyer term"],
        ["heavy duty plastic bags", "1,000", "35", "Supporting","Industrial buyer signal"],
        ["custom flexible packaging","210",  "33", "Supporting","Brand-level signal"],
      ]),
      spacer(),

      h2("Updated Metadata"),
      labelValue("Title Tag", "Custom Zipper Bags | Poly & Plastic Resealable Bags | Fruth"),
      labelValue("H1", "Custom Zipper Bags"),
      labelValue("Meta Description", "Fruth manufactures custom zipper bags for retail, food, industrial, and B2B applications. Any size, color, or thickness. ISO 9001 certified, FDA/USDA compliant, Made in USA. Request a quote."),
      spacer(),

      h2("Full Page Content (Existing + New)"),
      p("The content below is the complete page as it should appear — existing copy preserved, new sections added. All new content is clearly labeled.", { color: "555555", italics: true }),
      spacer(),

      h3("H1: Custom Zipper Bags"),
      spacer(),

      p("--- EXISTING CONTENT (keep as-is) ---", { bold: true, color: "888888" }),
      spacer(),
      p("Fruth Custom Packaging manufactures zipper bags — one of the most sought after bags in nearly every industry. From retail and food to gift and containment applications, our zipper bags deliver reliable sealing performance with full customization options."),
      spacer(),
      bullet("Available in practically any color, print color, or thickness", "bullets"),
      bullet("Side-weld seal reinforces the zipper for added durability", "bullets"),
      bullet("Constructed from high-density polyethylene — recyclable", "bullets"),
      bullet("Customizable sizing per client specifications", "bullets"),
      bullet("Lightweight and flexible design", "bullets"),
      bullet("ISO 9001:2015 certified manufacturer and distributor", "bullets"),
      bullet("Meets FDA and USDA food safety specifications", "bullets"),
      bullet("Made in the USA", "bullets"),
      spacer(),

      p("--- NEW CONTENT (add below existing) ---", { bold: true, color: "2E75B6" }),
      spacer(),
      h3("Custom Zipper Bags for B2B & Industrial Applications"),
      p("Fruth produces custom zipper bags for B2B buyers across food processing, retail packaging, industrial distribution, pharmaceutical, and specialty product sectors. Every bag is manufactured to your exact specifications — no catalog limitations on size, gauge, or print."),
      spacer(),
      p("Customize by:"),
      bullet("Material — high-density polyethylene (HDPE), LDPE, or poly blends", "bullets"),
      bullet("Size — custom width and length to fit your product exactly", "bullets"),
      bullet("Thickness — light-duty to heavy-duty gauge options", "bullets"),
      bullet("Color — clear, colored, or opaque", "bullets"),
      bullet("Print — unprinted or custom branded with logo and handling instructions", "bullets"),
      bullet("Closure — standard press-to-close zipper or reinforced side-weld seal", "bullets"),
      spacer(),
      h3("Industries We Serve"),
      p("Our custom zipper bags support operations across a wide range of B2B sectors:"),
      bullet("Food processing and distribution — FDA and USDA compliant materials", "bullets"),
      bullet("Retail and e-commerce — custom print for branded unboxing", "bullets"),
      bullet("Pharmaceutical and nutraceutical — tamper-evident and moisture-resistant options", "bullets"),
      bullet("Industrial parts and hardware — heavy-duty construction for component storage", "bullets"),
      bullet("Cannabis and specialty goods — child-resistant compatible configurations available", "bullets"),
      spacer(),
      p("Need custom zipper bags at volume? Request a quote and we will respond within one business day.", { bold: true }),
      spacer(),

      h2("FAQ Section (add below product copy)"),
      spacer(),
      h3("Q: What materials are Fruth zipper bags made from?"),
      p("Our zipper bags are constructed from high-density polyethylene (HDPE) and are recyclable. We also produce zipper bags in LDPE and poly blends depending on your application requirements."),
      spacer(),
      h3("Q: Can zipper bags be custom printed?"),
      p("Yes. We offer custom print options for branding, product identification, and handling instructions. Any color or print configuration is available."),
      spacer(),
      h3("Q: Are your zipper bags FDA compliant?"),
      p("Yes. Our zipper bags meet FDA and USDA food safety specifications, making them suitable for direct food contact applications."),
      spacer(),

      h2("FAQ Schema Code (add to page <head> or via CMS schema field)"),
      p("Paste the code block below into the page's custom schema / JSON-LD field in your CMS:", { italics: true, color: "555555" }),
      spacer(),
      new Paragraph({
        children: [new TextRun({
          text: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What materials are Fruth zipper bags made from?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Fruth zipper bags are constructed from high-density polyethylene (HDPE) and are recyclable. LDPE and poly blend options are also available depending on application requirements."
      }
    },
    {
      "@type": "Question",
      "name": "Can zipper bags be custom printed?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. Fruth offers custom print options for branding, product identification, and handling instructions in any color or print configuration."
      }
    },
    {
      "@type": "Question",
      "name": "Are Fruth zipper bags FDA compliant?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. Fruth zipper bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications."
      }
    }
  ]
}
</script>`,
          font: "Courier New",
          size: 18,
          color: "333333"
        })],
        shading: { fill: "F4F4F4", type: ShadingType.CLEAR }
      }),
      spacer(),
      divider(),
      spacer(),

      // ── PAGE 4: LIP AND TAPE BAGS ─────────────────────────────────
      h1("Page 4 of [N]: Lip & Tape Bags"),
      labelValue("URL", "https://www.fruth.com/products/bags/lip-and-tape-bags"),
      labelValue("Status", "Live — low word count (audit critical error)"),
      spacer(),

      h2("Keyword Targets"),
      keywordTable([
        ["tamper evident bags",     "590",  "16", "Primary",   "Mentioned on page already — SD 16 very easy"],
        ["lip and tape bags",       "90",   "15", "Secondary", "Exact product name — low vol but relevant"],
        ["custom poly bags",        "880",  "28", "Supporting","Broad B2B buyer term"],
        ["heavy duty plastic bags", "1,000","35", "Supporting","Industrial buyer signal"],
      ]),
      spacer(),

      h2("Updated Metadata"),
      labelValue("Title Tag", "Lip & Tape Bags | Tamper Evident Bags | Custom Poly Bags | Fruth"),
      labelValue("H1", "Lip & Tape Bags with Tamper Evident Options"),
      labelValue("Meta Description", "Fruth manufactures custom lip and tape bags with permanent and resealable closure options. Tamper evident versions available. ISO 9001 certified, Made in USA. Request a quote today."),
      spacer(),

      h2("Full Page Content (Existing + New)"),
      p("The content below is the complete page as it should appear — existing copy preserved, new sections added. All new content is clearly labeled.", { color: "555555", italics: true }),
      spacer(),

      h3("H1: Lip & Tape Bags with Tamper Evident Options"),
      spacer(),

      p("--- EXISTING CONTENT (keep as-is) ---", { bold: true, color: "888888" }),
      spacer(),
      p("Fruth Custom Packaging specializes in lip and tape bags that combine performance with affordability. These bags feature an adhesive strip on the flap, which can be folded and sealed over the bag's opening."),
      spacer(),
      bullet("Sideweld design with standard 1 inch lip", "bullets"),
      bullet("Available for various films and cushion options", "bullets"),
      bullet("Made in the USA", "bullets"),
      spacer(),
      p("Permanent Tape:", { bold: true }),
      p("Once sealed, the adhesive will not release when pulled. This option is ideal for securing contents — excessive force will damage the bag itself rather than break the seal. Tamper-evident versions are available."),
      spacer(),
      p("Resealable Option:", { bold: true }),
      p("These bags can be opened and resealed several times without impacting the adhesion of the tape."),
      spacer(),

      p("--- NEW CONTENT (add below existing) ---", { bold: true, color: "2E75B6" }),
      spacer(),
      h3("Tamper Evident Bags for B2B & Commercial Applications"),
      p("Fruth lip and tape bags are a leading tamper evident packaging solution for B2B buyers across retail, e-commerce, pharmaceutical, food service, and industrial distribution. The permanent seal construction ensures contents cannot be accessed without visible evidence of tampering — critical for brand protection, compliance, and consumer trust."),
      spacer(),
      p("Common applications include:"),
      bullet("Retail and apparel — polybag sleeve packaging with tamper evidence", "bullets"),
      bullet("E-commerce fulfillment — resealable or permanent seal for returns and shipments", "bullets"),
      bullet("Pharmaceutical and nutraceutical — tamper-evident compliance packaging", "bullets"),
      bullet("Food service and specialty goods — sealed freshness and contamination protection", "bullets"),
      bullet("Industrial parts — secure component packaging for distribution", "bullets"),
      spacer(),
      h3("Custom Lip & Tape Bag Specifications"),
      p("Every bag is built to your specifications. Customization options include:"),
      bullet("Lip length — standard 1 inch or custom length", "bullets"),
      bullet("Material — polyethylene film, poly blends, or specialty films", "bullets"),
      bullet("Size — custom width and length", "bullets"),
      bullet("Closure type — permanent seal or resealable", "bullets"),
      bullet("Print — clear/unprinted or custom branded", "bullets"),
      spacer(),
      p("Request a custom quote and we will respond within one business day.", { bold: true }),
      spacer(),

      h2("FAQ Section (add below product copy)"),
      spacer(),
      h3("Q: What is the difference between permanent and resealable lip and tape bags?"),
      p("Permanent tape bags form a tamper-evident seal — once closed, the bag must be torn or damaged to open, making tampering visible. Resealable bags use a repositionable adhesive that can be opened and resealed multiple times without losing adhesion."),
      spacer(),
      h3("Q: Are lip and tape bags tamper evident?"),
      p("Yes — permanent closure versions are tamper evident. Once sealed, the adhesive will not release cleanly, so any attempt to open the bag leaves visible evidence of tampering."),
      spacer(),
      h3("Q: Can lip and tape bags be custom printed?"),
      p("Yes. Fruth produces custom printed lip and tape bags for retail branding, product identification, and handling instructions. Any size, film type, or print configuration is available."),
      spacer(),

      h2("FAQ Schema Code (add to page <head> or via CMS schema field)"),
      p("Paste the code block below into the page's custom schema / JSON-LD field in your CMS:", { italics: true, color: "555555" }),
      spacer(),
      new Paragraph({
        children: [new TextRun({
          text: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is the difference between permanent and resealable lip and tape bags?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Permanent tape bags form a tamper-evident seal — once closed the bag must be torn or damaged to open, making tampering visible. Resealable bags use a repositionable adhesive that can be opened and resealed multiple times without losing adhesion."
      }
    },
    {
      "@type": "Question",
      "name": "Are lip and tape bags tamper evident?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes — permanent closure versions are tamper evident. Once sealed, the adhesive will not release cleanly, so any attempt to open the bag leaves visible evidence of tampering."
      }
    },
    {
      "@type": "Question",
      "name": "Can lip and tape bags be custom printed?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. Fruth produces custom printed lip and tape bags for retail branding, product identification, and handling instructions. Any size, film type, or print configuration is available."
      }
    }
  ]
}
</script>`,
          font: "Courier New",
          size: 18,
          color: "333333"
        })],
        shading: { fill: "F4F4F4", type: ShadingType.CLEAR }
      }),
      spacer(),
      divider(),
      spacer(),

      // ── PAGE 5: CLEANROOM BAGS ────────────────────────────────────
      h1("Page 5 of [N]: Cleanroom Bags"),
      labelValue("URL", "https://www.fruth.com/products/bags/cleanroom-bags"),
      labelValue("Status", "Live — low word count (audit critical error)"),
      spacer(),

      h2("Keyword Targets"),
      keywordTable([
        ["cleanroom bags",          "260", "13", "Primary",   "SD 13 — easiest keyword in full list"],
        ["medical device packaging","320", "23", "Secondary", "Same buyer, strong volume"],
        ["cleanroom packaging",     "70",  "34", "Secondary", "Category-level term"],
        ["cleanroom poly bags",     "30",  "13", "Supporting","Exact product variant"],
        ["iso cleanroom packaging", "10",  "19", "Supporting","Spec-aware buyer signal"],
        ["medical packaging bags",  "30",  "19", "Supporting","Medical sector buyer"],
      ]),
      spacer(),

      h2("Updated Metadata"),
      labelValue("Title Tag", "Cleanroom Bags | ISO 14644 Certified | Medical & ESD Packaging | Fruth"),
      labelValue("H1", "Cleanroom Bags"),
      labelValue("Meta Description", "Fruth manufactures cleanroom bags in an ISO 14644 certified facility. Zero additives, FDA compliant, made entirely in the USA. Serving medical, semiconductor, aerospace, and electronics industries. Request a quote."),
      spacer(),

      h2("Full Page Content (Existing + New)"),
      p("The content below is the complete page as it should appear — existing copy preserved, new sections added. All new content is clearly labeled.", { color: "555555", italics: true }),
      spacer(),

      h3("H1: Cleanroom Bags"),
      spacer(),

      p("--- EXISTING CONTENT (keep as-is) ---", { bold: true, color: "888888" }),
      spacer(),
      p("Fruth manufactures cleanroom bags in an ISO 14644 certified facility. Our resins are pure from the start and contain ZERO additives like slip and anti-block. The production process maintains materials in the cleanroom environment from raw state through vacuum-sealed completion, achieving full FDA compliance."),
      spacer(),
      bullet("Vertically integrated operations", "bullets"),
      bullet("ISO 14644 compliant cleanrooms", "bullets"),
      bullet("Manufactured entirely in the USA", "bullets"),
      bullet("Zero additive formulations", "bullets"),
      spacer(),
      p("Industries served:"),
      bullet("Microelectronics", "bullets"),
      bullet("Aerospace", "bullets"),
      bullet("Semiconductors", "bullets"),
      bullet("Silicon manufacturing", "bullets"),
      bullet("Food sector", "bullets"),
      bullet("Medical device markets", "bullets"),
      spacer(),

      p("--- NEW CONTENT (add below existing) ---", { bold: true, color: "2E75B6" }),
      spacer(),
      h3("ISO Cleanroom Packaging for Medical, Semiconductor & Aerospace Applications"),
      p("Fruth cleanroom bags are engineered for applications where contamination control is non-negotiable. Our ISO 14644 certified production environment ensures every bag meets the stringent cleanliness standards required by medical device manufacturers, semiconductor fabs, aerospace suppliers, and precision electronics producers."),
      spacer(),
      p("Unlike standard poly bags, our cleanroom bags are produced with zero slip agents, anti-block additives, or processing aids that could contaminate sensitive components or violate regulatory requirements. Every batch is vacuum-sealed within the cleanroom to preserve integrity through delivery."),
      spacer(),
      h3("Custom Cleanroom Bag Specifications"),
      p("We produce cleanroom bags to your exact requirements:"),
      bullet("ISO class rating — matched to your facility or product requirements", "bullets"),
      bullet("Material — ultra-pure polyethylene, nylon, or specialty cleanroom films", "bullets"),
      bullet("Size — fully custom width, length, and thickness", "bullets"),
      bullet("Seal type — bottom seal, side seal, or open-top configurations", "bullets"),
      bullet("Certification documentation — available on request for regulated industries", "bullets"),
      spacer(),
      h3("Why Cleanroom Packaging Matters for Medical & Electronics Buyers"),
      p("For medical device packaging and electronics packaging, particulate contamination during the packaging process can cause product failures, regulatory non-compliance, or patient safety issues. Fruth's vertically integrated cleanroom production means your bags are never exposed to a non-controlled environment between manufacture and delivery."),
      spacer(),
      p("Request a custom cleanroom bag quote and we will respond within one business day.", { bold: true }),
      spacer(),

      h2("FAQ Section (add below product copy)"),
      spacer(),
      h3("Q: What ISO class are Fruth cleanroom bags manufactured in?"),
      p("Fruth manufactures cleanroom bags in an ISO 14644 certified facility. Specific ISO class documentation is available on request for regulated procurement processes."),
      spacer(),
      h3("Q: Do Fruth cleanroom bags contain any additives?"),
      p("No. Our cleanroom bags are produced with zero additives including slip agents and anti-block compounds. Resins are pure from the start and maintained in a controlled environment through vacuum-sealed completion."),
      spacer(),
      h3("Q: What industries use Fruth cleanroom bags?"),
      p("Our cleanroom bags serve microelectronics, semiconductor, aerospace, silicon manufacturing, medical device, and food sector applications — any industry where contamination control and regulatory compliance are critical."),
      spacer(),

      h2("FAQ Schema Code (add to page <head> or via CMS schema field)"),
      p("Paste the code block below into the page's custom schema / JSON-LD field in your CMS:", { italics: true, color: "555555" }),
      spacer(),
      new Paragraph({
        children: [new TextRun({
          text: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What ISO class are Fruth cleanroom bags manufactured in?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Fruth manufactures cleanroom bags in an ISO 14644 certified facility. Specific ISO class documentation is available on request for regulated procurement processes."
      }
    },
    {
      "@type": "Question",
      "name": "Do Fruth cleanroom bags contain any additives?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No. Fruth cleanroom bags are produced with zero additives including slip agents and anti-block compounds. Resins are pure from the start and maintained in a controlled environment through vacuum-sealed completion."
      }
    },
    {
      "@type": "Question",
      "name": "What industries use Fruth cleanroom bags?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Fruth cleanroom bags serve microelectronics, semiconductor, aerospace, silicon manufacturing, medical device, and food sector applications."
      }
    }
  ]
}
</script>`,
          font: "Courier New",
          size: 18,
          color: "333333"
        })],
        shading: { fill: "F4F4F4", type: ShadingType.CLEAR }
      }),
      spacer(),
      divider(),
      spacer(),

      // ── PAGE 6: FFP BARRIER FILM ──────────────────────────────────
      h1("Page 6 of [N]: FFP Barrier Film (Moisture Barrier Film)"),
      labelValue("URL", "https://www.fruth.com/products/barrier-films/ffp-barrier-film"),
      labelValue("Status", "Live — low word count (audit critical error)"),
      spacer(),

      h2("Keyword Targets"),
      keywordTable([
        ["moisture barrier film",   "50",  "13", "Primary",   "Direct product match — very easy SD"],
        ["barrier film packaging",  "10",  "13", "Secondary", "Category-level buyer term"],
        ["plastic packaging film",  "140", "27", "Secondary", "Broader B2B buyer intent"],
        ["high barrier films",      "50",  "28", "Supporting","Spec-aware procurement term"],
        ["packaging films",         "390", "42", "Supporting","High vol — use naturally in copy"],
      ]),
      spacer(),

      h2("Updated Metadata"),
      labelValue("Title Tag", "Moisture Barrier Film | FFP Film-Foil-Poly Barrier Packaging | Fruth"),
      labelValue("H1", "FFP Barrier Film — Moisture, Light & Oxygen Protection"),
      labelValue("Meta Description", "Fruth manufactures FFP (Film-Foil-Poly) moisture barrier film for industrial and commercial packaging. Superior tear resistance, heat-sealable, custom configurations. ISO 9001 certified, Made in USA. Request a quote."),
      spacer(),

      h2("Full Page Content (Existing + New)"),
      p("The content below is the complete page as it should appear — existing copy preserved, new sections added. All new content is clearly labeled.", { color: "555555", italics: true }),
      spacer(),

      h3("H1: FFP Barrier Film — Moisture, Light & Oxygen Protection"),
      spacer(),

      p("--- EXISTING CONTENT (keep as-is) ---", { bold: true, color: "888888" }),
      spacer(),
      p("Fruth offers FFP (Film-Foil-Poly) barrier film — a lower cost, non DOD-compliant alternative to MIL-PRF-131k Type foil. The material provides heat-sealability and delivers superior tear and puncture resistance as well as protection from light, air, and moisture vapor."),
      spacer(),
      bullet("ISO 9001:2015 certified manufacturer and distributor", "bullets"),
      bullet("Made in the USA", "bullets"),
      bullet("Available in standard and custom configurations", "bullets"),
      bullet("Material consultation available — we help you select the barrier material best suited for your product, specifications, and budget", "bullets"),
      spacer(),

      p("--- NEW CONTENT (add below existing) ---", { bold: true, color: "2E75B6" }),
      spacer(),
      h3("Moisture Barrier Film for B2B & Industrial Packaging"),
      p("Fruth FFP barrier film is a high-performance moisture barrier film solution for B2B buyers who need reliable protection against humidity, oxygen, and light without the cost or compliance overhead of full military-specification materials. The three-layer Film-Foil-Poly construction delivers the barrier performance required for long-term storage, international shipment, and contamination-sensitive applications."),
      spacer(),
      p("Common applications include:"),
      bullet("Industrial parts and metal components — corrosion prevention during storage and transit", "bullets"),
      bullet("Food and pharmaceutical packaging — moisture and oxygen exclusion for shelf life extension", "bullets"),
      bullet("Electronics and sensitive components — humidity and light protection", "bullets"),
      bullet("Military and defense adjacent — cost-effective alternative where full MIL-PRF-131K is not required", "bullets"),
      bullet("Long-term archival and preservation packaging", "bullets"),
      spacer(),
      h3("FFP Barrier Film Properties"),
      bullet("Three-layer construction — film, foil, and poly laminate", "bullets"),
      bullet("Heat-sealable for airtight closures", "bullets"),
      bullet("Superior tear and puncture resistance", "bullets"),
      bullet("Blocks moisture vapor, oxygen, and light", "bullets"),
      bullet("Custom widths, lengths, and thicknesses available", "bullets"),
      bullet("Non DOD-compliant — cost-effective for commercial and industrial applications", "bullets"),
      spacer(),
      p("Not sure if FFP is the right barrier material for your application? Our team provides material consultation to match your product requirements, specifications, and budget. Contact us and we will respond within one business day.", { bold: true }),
      spacer(),

      h2("FAQ Section (add below product copy)"),
      spacer(),
      h3("Q: What is FFP barrier film?"),
      p("FFP stands for Film-Foil-Poly — a three-layer laminate that combines a plastic film outer layer, an aluminum foil middle layer, and a polyethylene inner layer. Together these layers provide an effective barrier against moisture vapor, oxygen, and light, making it a versatile moisture barrier film for industrial and commercial packaging."),
      spacer(),
      h3("Q: How does FFP barrier film differ from MIL-PRF-131K?"),
      p("FFP barrier film provides comparable moisture and oxygen barrier performance to MIL-PRF-131K type foil at a lower cost, but it is not DOD-compliant. It is the right choice when military specification certification is not required but high barrier performance still is."),
      spacer(),
      h3("Q: Can FFP barrier film be custom sized?"),
      p("Yes. Fruth produces FFP barrier film in both standard and fully custom configurations. Contact us with your dimensions, thickness requirements, and application details for a quote."),
      spacer(),

      h2("FAQ Schema Code (add to page <head> or via CMS schema field)"),
      p("Paste the code block below into the page's custom schema / JSON-LD field in your CMS:", { italics: true, color: "555555" }),
      spacer(),
      new Paragraph({
        children: [new TextRun({
          text: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is FFP barrier film?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "FFP stands for Film-Foil-Poly — a three-layer laminate combining a plastic film outer layer, aluminum foil middle layer, and polyethylene inner layer. Together these layers provide an effective barrier against moisture vapor, oxygen, and light for industrial and commercial packaging."
      }
    },
    {
      "@type": "Question",
      "name": "How does FFP barrier film differ from MIL-PRF-131K?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "FFP barrier film provides comparable moisture and oxygen barrier performance to MIL-PRF-131K type foil at a lower cost, but is not DOD-compliant. It is the right choice when military specification certification is not required but high barrier performance still is."
      }
    },
    {
      "@type": "Question",
      "name": "Can FFP barrier film be custom sized?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. Fruth produces FFP barrier film in both standard and fully custom configurations. Contact us with your dimensions, thickness requirements, and application details for a quote."
      }
    }
  ]
}
</script>`,
          font: "Courier New",
          size: 18,
          color: "333333"
        })],
        shading: { fill: "F4F4F4", type: ShadingType.CLEAR }
      }),
      spacer(),
      divider(),
      spacer(),

      // ── PAGE 7: WICKETED BAGS ─────────────────────────────────────
      h1("Page 7 of [N]: Wicketed Bags"),
      labelValue("URL", "https://www.fruth.com/products/bags/wicketed-bags"),
      labelValue("Status", "Live — low word count (audit critical error)"),
      spacer(),

      h2("Keyword Targets"),
      keywordTable([
        ["wicketed bags",           "390",   "14", "Primary",   "SD 14 — highly winnable, not ranked"],
        ["custom poly bags",        "880",   "28", "Secondary", "Broad B2B buyer term"],
        ["custom plastic bags",     "1,900", "42", "Secondary", "High vol — use naturally in copy"],
        ["heavy duty plastic bags", "1,000", "35", "Supporting","Industrial buyer signal"],
      ]),
      spacer(),

      h2("Updated Metadata"),
      labelValue("Title Tag", "Custom Wicketed Bags | Automated Filling Poly Bags | Fruth"),
      labelValue("H1", "Custom Wicketed Bags"),
      labelValue("Meta Description", "Fruth manufactures custom wicketed bags for automated and semi-automatic filling lines. Any color, size, or thickness. ISO 9001 certified, FDA/USDA compliant, Made in USA. Request a quote today."),
      spacer(),

      h2("Full Page Content (Existing + New)"),
      p("The content below is the complete page as it should appear — existing copy preserved, new sections added. All new content is clearly labeled.", { color: "555555", italics: true }),
      spacer(),

      h3("H1: Custom Wicketed Bags"),
      spacer(),

      p("--- EXISTING CONTENT (keep as-is) ---", { bold: true, color: "888888" }),
      spacer(),
      p("Fruth Custom Packaging offers wicketed bags in practically any color, print color, or thickness depending on your packaging needs. Featuring wire-wicket mounting for fast and easy manual loading, our wicketed bags are fully compatible with automatic and semi-automatic filling equipment."),
      spacer(),
      bullet("Highest quality polyethylene material for product visibility", "bullets"),
      bullet("Customizable sizing to exact specifications", "bullets"),
      bullet("Lightweight and flexible construction", "bullets"),
      bullet("Made in the USA", "bullets"),
      bullet("ISO 9001:2015 certified manufacturer and distributor", "bullets"),
      bullet("Meets FDA and USDA food safety specifications", "bullets"),
      spacer(),

      p("--- NEW CONTENT (add below existing) ---", { bold: true, color: "2E75B6" }),
      spacer(),
      h3("Wicketed Bags for High-Speed B2B Packaging Lines"),
      p("Fruth wicketed bags are engineered for B2B buyers running automated or semi-automatic packaging operations. The wire-wicket format allows bags to be dispensed one at a time directly from the stack — maximizing throughput on filling lines and reducing manual handling time across food processing, produce, retail, and industrial packaging operations."),
      spacer(),
      p("Common applications include:"),
      bullet("Food processing and produce — FDA and USDA compliant materials for direct food contact", "bullets"),
      bullet("Bakery and fresh goods — high-clarity poly for product visibility on retail shelves", "bullets"),
      bullet("Industrial parts and hardware — custom gauge options for heavier components", "bullets"),
      bullet("Retail and e-commerce fulfillment — branded print options for shelf-ready presentation", "bullets"),
      bullet("Agricultural and horticulture — bulk bagging for nursery and garden products", "bullets"),
      spacer(),
      h3("Custom Wicketed Bag Specifications"),
      p("Every wicketed bag is built to your production line requirements:"),
      bullet("Material — polyethylene (LDPE/HDPE) or specialty poly blends", "bullets"),
      bullet("Size — custom width and length to match your product and filling equipment", "bullets"),
      bullet("Thickness — light to heavy gauge depending on load requirements", "bullets"),
      bullet("Color — clear or any custom color", "bullets"),
      bullet("Print — unprinted or fully custom branded", "bullets"),
      bullet("Wicket spacing — configured to your filling equipment specifications", "bullets"),
      spacer(),
      p("Request a custom quote and we will respond within one business day.", { bold: true }),
      spacer(),

      h2("FAQ Section (add below product copy)"),
      spacer(),
      h3("Q: What are wicketed bags?"),
      p("Wicketed bags are poly bags mounted on a metal wire wicket that holds them in a stacked, dispensable format. This allows bags to be peeled off one at a time during manual or automated filling, significantly increasing packaging line speed and efficiency."),
      spacer(),
      h3("Q: Are wicketed bags compatible with automated filling equipment?"),
      p("Yes. Fruth wicketed bags are designed for use with automatic and semi-automatic filling equipment. Wicket spacing can be configured to match your specific machinery requirements."),
      spacer(),
      h3("Q: Are Fruth wicketed bags FDA compliant?"),
      p("Yes. Our wicketed bags meet FDA and USDA food safety specifications, making them suitable for direct food contact applications including produce, bakery, and processed food packaging."),
      spacer(),

      h2("FAQ Schema Code (add to page <head> or via CMS schema field)"),
      p("Paste the code block below into the page's custom schema / JSON-LD field in your CMS:", { italics: true, color: "555555" }),
      spacer(),
      new Paragraph({
        children: [new TextRun({
          text: `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What are wicketed bags?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Wicketed bags are poly bags mounted on a metal wire wicket that holds them in a stacked, dispensable format. This allows bags to be peeled off one at a time during manual or automated filling, significantly increasing packaging line speed and efficiency."
      }
    },
    {
      "@type": "Question",
      "name": "Are wicketed bags compatible with automated filling equipment?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. Fruth wicketed bags are designed for use with automatic and semi-automatic filling equipment. Wicket spacing can be configured to match your specific machinery requirements."
      }
    },
    {
      "@type": "Question",
      "name": "Are Fruth wicketed bags FDA compliant?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. Fruth wicketed bags meet FDA and USDA food safety specifications and are suitable for direct food contact applications including produce, bakery, and processed food packaging."
      }
    }
  ]
}
</script>`,
          font: "Courier New",
          size: 18,
          color: "333333"
        })],
        shading: { fill: "F4F4F4", type: ShadingType.CLEAR }
      }),
      spacer(),
      divider(),
      spacer(),

      // ── PLACEHOLDER FOR NEXT PAGES ─────────────────────────────────
      h1("Page 8 of [N]: [Next URL]"),
      p("Add next URL content brief here.", { color: "AAAAAA", italics: true }),
    ]
  }]
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync("Fruth-SEO-Content-Expansion.docx", buffer);
  console.log("Done: Fruth-SEO-Content-Expansion.docx");
});
