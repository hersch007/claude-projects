const {
  Document, Packer, Paragraph, TextRun, AlignmentType,
  BorderStyle, HeadingLevel, Table, TableRow, TableCell, WidthType, ShadingType
} = require('docx');
const fs = require('fs');

const thinBorder = { style: BorderStyle.SINGLE, size: 1, color: "DDDDDD" };
const noBorder   = { style: BorderStyle.NONE, size: 0, color: "FFFFFF" };

function p(text, opts = {}) {
  return new Paragraph({ children: [new TextRun({ text, font: "Arial", size: 24, ...opts })], spacing: { after: 160 } });
}
function bold(text, size = 24) {
  return new TextRun({ text, bold: true, font: "Arial", size });
}
function normal(text) {
  return new TextRun({ text, font: "Arial", size: 24 });
}
function blank() {
  return new Paragraph({ children: [new TextRun("")], spacing: { after: 160 } });
}
function rule() {
  return new Paragraph({
    border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: "2E5827", space: 1 } },
    spacing: { after: 200 },
    children: []
  });
}
function sectionHead(text) {
  return new Paragraph({
    children: [bold(text, 26)],
    spacing: { before: 280, after: 100 },
    border: { bottom: { style: BorderStyle.SINGLE, size: 2, color: "CCCCCC", space: 1 } }
  });
}
function feeRow(label, value, highlight = false) {
  const fill = highlight ? "F0F7EE" : "FFFFFF";
  return new TableRow({
    children: [
      new TableCell({
        width: { size: 6240, type: WidthType.DXA },
        borders: { top: thinBorder, bottom: thinBorder, left: noBorder, right: noBorder },
        shading: { fill, type: ShadingType.CLEAR },
        margins: { top: 80, bottom: 80, left: 120, right: 120 },
        children: [new Paragraph({ children: [normal(label)], spacing: { after: 0 } })]
      }),
      new TableCell({
        width: { size: 3120, type: WidthType.DXA },
        borders: { top: thinBorder, bottom: thinBorder, left: noBorder, right: noBorder },
        shading: { fill, type: ShadingType.CLEAR },
        margins: { top: 80, bottom: 80, left: 120, right: 120 },
        children: [new Paragraph({ children: [highlight ? bold(value) : normal(value)], alignment: AlignmentType.RIGHT, spacing: { after: 0 } })]
      })
    ]
  });
}

const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 24 } } }
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
      }
    },
    children: [

      // Header / Logo area
      new Paragraph({
        children: [bold("START PERFORMANCE", 36)],
        spacing: { after: 40 }
      }),
      new Paragraph({
        children: [new TextRun({ text: "115 Dave Lyle Blvd. S.  |  Rock Hill, SC 29730  |  startadvertising.com", font: "Arial", size: 20, color: "666666" })],
        spacing: { after: 40 }
      }),
      rule(),

      // Date + greeting
      p("June 15, 2026"),
      blank(),
      p("Kyle Flanagan, President"),
      p("Lawn Ace"),
      p("1048 Franke Industrial Dr."),
      p("Augusta, GA 30909"),
      blank(),
      new Paragraph({ children: [normal("Dear Kyle,")], spacing: { after: 200 } }),

      // Opening
      new Paragraph({
        children: [
          normal("Thank you for trusting Start Performance with your digital presence. I'm excited to get to work. This letter summarizes what we've agreed to — the full legal details live in the three documents we've both signed, but I want you to have a plain-English version you can reference at any time.")
        ],
        spacing: { after: 200 }
      }),

      // ── WHAT WE'RE BUILDING ──
      sectionHead("What We're Building"),
      new Paragraph({
        children: [normal("We are designing, building, and supporting a "), bold("custom AI assistant for Lawn Ace"), normal(" — called Ace — that will live on your website and work for you around the clock. Here's what that includes:")],
        spacing: { after: 140 }
      }),

      // Deliverables list
      ...[
        ["Custom AI Chatbot", "Trained on your services, pricing, and voice. Handles customer questions, qualifies leads, and sounds like you — not a call center."],
        ["Lead Capture System", "Captures name, email, phone, and address. Sends instant notifications so your team can follow up fast."],
        ["Branding & Website Integration", "Your logo, colors, and style. Installed directly on your WordPress site, mobile-friendly."],
        ["Knowledge Base", "Built from your FAQs, pricing, and service details. We train Ace on how Lawn Ace actually talks and what Lawn Ace actually does."],
        ["Analytics Dashboard", "Searchable conversation history, lead tracking, drop-off analysis, and AI-generated business insights — all in one place."],
        ["Launch Support", "Discovery session, testing, optimization, and full deployment assistance."]
      ].map(([title, desc]) =>
        new Paragraph({
          children: [bold(title + ": "), normal(desc)],
          spacing: { after: 120 },
          indent: { left: 360 }
        })
      ),
      blank(),

      // ── ONGOING MONTHLY SERVICES ──
      sectionHead("What Happens Every Month"),
      new Paragraph({
        children: [normal("Your monthly fee keeps Ace sharp and your system running. Each month we handle:")],
        spacing: { after: 140 }
      }),
      ...[
        "AI refinement — prompt tuning, conversation improvements, performance monitoring, and bug fixes",
        "Content updates — service changes, pricing updates, seasonal adjustments (you supply the info, we update the bot)",
        "Dashboard monitoring — conversation reviews, lead monitoring, and workflow improvement recommendations",
        "Technical support — email support, minor configuration updates, and security monitoring"
      ].map(item =>
        new Paragraph({
          children: [normal(item)],
          spacing: { after: 100 },
          indent: { left: 360 },
          bullet: { level: 0 }
        })
      ),
      blank(),

      // ── INVESTMENT ──
      sectionHead("Your Investment"),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [6240, 3120],
        rows: [
          feeRow("One-Time Setup Fee (standard value: $3,500.00)", "$1,799.00", true),
          feeRow("Monthly Service Fee (standard value: $259.00/mo)", "$149.00 / mo", true),
          feeRow("Claude AI API Usage (billed at actual cost)", "~$5 – $15 / mo"),
        ]
      }),
      new Paragraph({
        children: [new TextRun({ text: "The AI API usage covers Anthropic's Claude — the engine that powers Ace. It runs through your account at actual cost, typically well under $20/month.", font: "Arial", size: 20, color: "666666", italics: true })],
        spacing: { before: 100, after: 200 }
      }),

      // ── WHAT'S NOT INCLUDED ──
      sectionHead("What's Not Included in the Monthly Fee"),
      new Paragraph({
        children: [normal("The monthly fee covers ongoing maintenance and support for the chatbot as built. It does not include major redesigns, complete rebuilds, new custom software development, CRM integrations, advertising management, or SEO services. Any of those would be scoped as a separate project.")],
        spacing: { after: 200 }
      }),

      // ── WHO OWNS WHAT ──
      sectionHead("Who Owns What"),
      new Paragraph({
        children: [normal("Your business data, customer records, branding, and content are "), bold("yours"), normal(". Always. We retain ownership of the software, AI architecture, and prompt systems — and you hold a license to use them for as long as we're working together.")],
        spacing: { after: 200 }
      }),

      // ── TERMS ──
      sectionHead("Key Terms at a Glance"),
      ...[
        ["Payments", "Due within 15 days of invoice. Late payments accrue interest at 1.5%/month."],
        ["Cancellation", "Either party may cancel with 30 days written notice."],
        ["Data on Termination", "We'll provide an export of your data within 30 days of termination on written request."],
        ["Governing Law", "South Carolina. Disputes go to arbitration in Rock Hill, SC."]
      ].map(([label, desc]) =>
        new Paragraph({
          children: [bold(label + ": "), normal(desc)],
          spacing: { after: 120 }
        })
      ),
      blank(),

      // ── CLOSING ──
      rule(),
      new Paragraph({
        children: [normal("Kyle, I'm genuinely excited about what we're building for Lawn Ace. Ace is going to work the phones while you're in the field. Let's make it great.")],
        spacing: { after: 200 }
      }),
      p("Warmly,"),
      blank(),
      new Paragraph({ children: [bold("Richard Brashear")], spacing: { after: 40 } }),
      p("Director of Operations"),
      p("Start Performance, Inc."),
      p("richard@grouprb.com"),

    ]
  }]
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync('C:\\Users\\richa\\Downloads\\LawnAce-Agreement-Letter.docx', buf);
  console.log('Done.');
});
