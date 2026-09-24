// Builds Parts of Practice Website Support & SEO Services Agreements.
//
//   node build-contracts.js            -> blank tier templates + every client in clients.js
//
// Blank templates go to ./output/. Client contracts go to ../../clients/<folder>/.
// Tier scope lives in tiers.js; client details live in clients.js.
// Text wrapped in [[...]] renders as a yellow-highlighted fill-in blank.

const fs = require('fs');
const path = require('path');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType,
  AlignmentType, BorderStyle, ShadingType, Header, Footer, PageNumber,
  LevelFormat, PageBreak, HeightRule, VerticalAlign,
} = require('docx');
const TIERS = require('./tiers');
const CLIENTS = require('./clients');

const NAVY = '1F2A44';
const ORANGE = 'E0552B';
const GREY = '6B7280';
const FONT = 'Arial';
const CONTENT_W = 9360; // 6.5" at 1" margins on US Letter

const PROVIDER = {
  name: 'Part of Practice, LLC',
  lines: ['a South Carolina limited liability company', '1403 Woodland Drive', 'Rock Hill, SC 29723'],
  email: 'PartsofPractice@gmail.com',
};

const money = n => '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// ---------- inline markup: **bold**, [[fill-in]] ----------
function runs(text, base = {}) {
  const out = [];
  const re = /(\*\*[^*]+\*\*|\[\[[^\]]+\]\])/g;
  let last = 0, m;
  while ((m = re.exec(text))) {
    if (m.index > last) out.push(new TextRun({ text: text.slice(last, m.index), ...base }));
    const tok = m[0];
    if (tok.startsWith('**')) out.push(new TextRun({ text: tok.slice(2, -2), bold: true, ...base }));
    else out.push(new TextRun({ text: '[' + tok.slice(2, -2) + ']', highlight: 'yellow', ...base }));
    last = m.index + tok.length;
  }
  if (last < text.length) out.push(new TextRun({ text: text.slice(last), ...base }));
  return out;
}

const para = (text, opts = {}) => new Paragraph({
  children: runs(text, opts.run || {}),
  spacing: { after: 100, line: 264 },
  alignment: opts.align,
  keepNext: opts.keepNext,
});
const bullet = text => new Paragraph({
  children: runs(text), numbering: { reference: 'bullets', level: 0 }, spacing: { after: 60, line: 264 },
});
const h1 = text => new Paragraph({
  children: [new TextRun({ text, bold: true, size: 26, color: NAVY })],
  spacing: { before: 200, after: 100 }, keepNext: true,
});
const h2 = text => new Paragraph({
  children: [new TextRun({ text, bold: true, size: 22, color: NAVY })],
  spacing: { before: 160, after: 80 }, keepNext: true,
});
const pageBreak = () => new Paragraph({ children: [new PageBreak()] });

const border = { style: BorderStyle.SINGLE, size: 4, color: '000000' };
const borders = { top: border, bottom: border, left: border, right: border };

function cell(children, width, extra = {}) {
  return new TableCell({
    children, width: { size: width, type: WidthType.DXA }, borders,
    margins: { top: 80, bottom: 80, left: 120, right: 120 }, ...extra,
  });
}

// ---------- parties box ----------
function partyCell(label, name, lines, width) {
  return cell([
    new Paragraph({ children: [new TextRun({ text: label, bold: true, color: ORANGE, size: 18 })] }),
    new Paragraph({ children: runs(name, { bold: true, color: NAVY }) }),
    ...lines.map(l => new Paragraph({ children: runs(l, { size: 19 }) })),
  ], width, { shading: { type: ShadingType.CLEAR, fill: 'F1F3F8', color: 'auto' } });
}

function partiesTable(client) {
  const half = CONTENT_W / 2;
  const clientLines = [
    ...client.address,
    `Phone: ${client.phone}`,
    `Notice email: ${client.email}`,
  ];
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [half, half],
    rows: [new TableRow({ children: [
      partyCell('PROVIDER', PROVIDER.name, [...PROVIDER.lines, `Notice email: ${PROVIDER.email}`], half),
      partyCell('CLIENT', client.legalName, clientLines, half),
    ] })],
  });
}

// ---------- signature block (left blank for e-signature) ----------
function signatureTable(client) {
  const half = CONTENT_W / 2;
  const row = (l, r, h) => new TableRow({
    height: h ? { value: h, rule: HeightRule.ATLEAST } : undefined,
    cantSplit: true,
    children: [l, r].map(t => cell([new Paragraph({ children: runs(t), keepNext: true })], half, { verticalAlign: VerticalAlign.BOTTOM })),
  });
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [half, half],
    rows: [
      new TableRow({ cantSplit: true, children: [
        cell([new Paragraph({ keepNext: true, children: [new TextRun({ text: 'PROVIDER', bold: true, color: ORANGE, size: 18 })] }),
              new Paragraph({ children: [new TextRun({ text: PROVIDER.name.toUpperCase(), bold: true })] })], half),
        cell([new Paragraph({ children: [new TextRun({ text: 'CLIENT', bold: true, color: ORANGE, size: 18 })] }),
              new Paragraph({ children: runs(client.legalName.toUpperCase(), { bold: true }) })], half),
      ] }),
      row('Signature:', 'Signature:', 900),
      row('Printed Name:', 'Printed Name:', 560),
      row('Title:', 'Title:', 560),
      row('Date:', 'Date:', 560),
    ],
  });
}

// ---------- fee table ----------
function feeTable(rows) {
  const w1 = 3000, w2 = CONTENT_W - w1;
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [w1, w2],
    rows: rows.map(([k, v]) => new TableRow({ children: [
      cell([new Paragraph({ children: [new TextRun({ text: k, bold: true })] })], w1,
        { shading: { type: ShadingType.CLEAR, fill: 'F1F3F8', color: 'auto' } }),
      cell([new Paragraph({ children: runs(v) })], w2),
    ] })),
  });
}

// ---------- document body ----------
function buildDoc(tier, client) {
  const total = tier.monthly * 12;
  const c = [];

  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 40 },
    children: [new TextRun({ text: 'PARTS OF PRACTICE', bold: true, size: 40, color: NAVY })] }));
  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 40 },
    children: [new TextRun({ text: 'WEBSITE SUPPORT & SEO SERVICES AGREEMENT', bold: true, size: 28, color: NAVY })] }));
  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 200 },
    children: [new TextRun({ text: `12-Month ${tier.planName} Plan`, bold: true, size: 22, color: ORANGE })] }));

  c.push(para('This Website Support & SEO Services Agreement ("Agreement") is effective as of the date on which the last Party signs below (the "**Effective Date**") and is entered into by and between the following parties:'));
  c.push(partiesTable(client));
  c.push(para('Provider and Client may each be called a "Party" and together the "Parties."'));

  c.push(h1('1. Services'));
  c.push(para('Provider will perform the services described in Exhibit A (the "Services") using Client\'s existing website, hosting environment, content-management system, theme, plugins, and technical framework. Provider is not supplying or replacing website hosting under this Agreement.'));
  c.push(para('Work outside Exhibit A requires a written change order describing the added scope, timing, and fees. Email approval by authorized representatives is sufficient for a change order.'));

  c.push(h1('2. Term; Renewal; Termination'));
  c.push(para(`**2.1 Initial Term; Automatic Renewal.** The Agreement begins on the Effective Date and continues for twelve (12) consecutive months (the "Initial Term"). At the end of the Initial Term, it automatically renews for successive one-year renewal terms at $55.00 per month for the ${tier.key === 'spark' ? 'Spark SEO renewal services' : 'SEO-only renewal services'} described in Exhibit A, unless either Party gives written notice of non-renewal at least sixty (60) days before the end of the then-current term.`));
  c.push(para('**2.2 Twelve-Month Commitment.** Neither Party may terminate for convenience during the Initial Term. If Client stops the Services or access without Provider\'s material breach, Client remains responsible for the unpaid monthly fees through the end of the Initial Term. The Parties agree this payment commitment reflects reserved capacity and agreed pricing and is not a penalty, subject to any non-waivable law.'));
  c.push(para('**2.3 Termination for Cause; Suspension.** Either Party may terminate for material breach if the breaching Party does not cure the breach within thirty (30) days after written notice. Provider may suspend Services on five (5) days\' written notice for overdue amounts or loss of required access, and suspension does not extend the Initial Term or any renewal term.'));
  c.push(para('**2.4 Effect.** On expiration or termination, Client must pay all amounts then due. Each Party will return or securely destroy the other\'s confidential information on request, subject to routine backups and legal retention duties. Sections intended by their nature to survive will survive.'));

  c.push(h1('3. Fees and Payment'));
  c.push(para(`**3.1 Fees.** Client will pay ${money(tier.monthly)} per month for twelve (12) months, for a total Initial Term contract value of ${money(total)}, plus applicable taxes.`));
  c.push(para('**3.2 Schedule.** The first monthly payment is due on the Effective Date. Each remaining monthly payment is due in advance on the same day of each following month (or the last day of any month that has no such day). Client authorizes recurring payment by the payment method on file, if used. Provider may invoice electronically.'));
  c.push(para('**3.3 Late or Disputed Amounts.** An unpaid amount may accrue a late charge of 1.5% per month or the maximum lawful rate, whichever is lower, plus reasonable collection costs. Client must dispute an invoice in writing within ten (10) days after receipt, identifying the specific disputed amount and basis; undisputed amounts remain due.'));

  c.push(h1('4. Client Responsibilities'));
  c.push(para('Client will provide timely administrator-level access, business information, service-area details, brand assets, approvals, and properly licensed content reasonably needed for the Services. Client will designate one authorized decision-maker and respond to approval requests within five (5) business days. Delays by Client or third parties may shift delivery timing without reducing fees.'));
  c.push(para('Client is responsible for the legality, clinical accuracy, advertising claims, professional licensing statements, privacy notices, consent language, and intellectual-property rights in Client materials and in final content Client approves.'));

  c.push(h1('5. Existing Website, Hosting, and Third Parties'));
  c.push(para('Client controls and remains responsible for its hosting account, domain registration, email, backups, platform subscription, theme and plugin licenses, security configuration, uptime, and third-party fees unless Exhibit A expressly says otherwise. Provider may make reasonable changes within the existing framework but is not required to rebuild, migrate, or remediate pre-existing defects.'));
  c.push(para('Provider is not responsible for outages, data loss, security incidents, incompatibilities, policy changes, or discontinued features caused by Client\'s host, platform, plugins, analytics tools, search engines, or other third parties. Provider will notify Client of material issues it discovers and may recommend separately priced work.'));

  c.push(h1('6. SEO Standards; No Guarantee'));
  c.push(para('Provider will use commercially reasonable, industry-standard practices. Search rankings, traffic, leads, conversions, indexing, and Google Business Profile actions depend on third parties and are not guaranteed. Algorithms and platform policies change without notice. Reports and recommendations are informational, and Client retains responsibility for business decisions.'));

  c.push(h1('7. Review; Corrections'));
  c.push(para('Client will review deliverables promptly. If Client reports a material failure to conform to Exhibit A within thirty (30) days after delivery, Provider\'s exclusive obligation is to use commercially reasonable efforts to correct or re-perform the affected Service. This remedy does not apply to Client changes, third-party changes, pre-existing conditions, or use contrary to Provider\'s instructions.'));

  c.push(h1('8. Intellectual Property'));
  c.push(para('Client retains ownership of materials it supplies. Client grants Provider a non-exclusive license to use those materials only to perform the Services. After Client pays all amounts due, Client owns the final, client-specific copy and page content created under this Agreement.'));
  c.push(para('Provider retains all rights in its pre-existing and generally applicable templates, know-how, processes, code snippets, systems, and tools ("Provider Materials"). To the extent Provider Materials are embedded in a deliverable, Provider grants Client a perpetual, non-exclusive license to use them solely as part of that deliverable. Third-party materials remain subject to their own licenses.'));

  c.push(h1('9. Confidentiality; Privacy; HIPAA'));
  c.push(para('Each Party will protect the other\'s non-public business, technical, and personal information using reasonable care and use it only to perform or receive the Services. This duty does not cover information that is public through no breach, already known without restriction, independently developed, or lawfully received from another source.'));
  c.push(para('Client will not provide Provider with protected health information (PHI) or permit Provider to access systems containing PHI unless the Parties first sign a Business Associate Agreement and agree in writing on the covered services. This Agreement alone does not make Provider a business associate or promise that Client\'s website, hosting, forms, or integrations are HIPAA compliant.'));

  c.push(h1('10. Accessibility and Legal Compliance'));
  c.push(para('Provider will apply reasonable web and SEO practices within the agreed scope, but does not provide legal advice or guarantee compliance with the ADA, state privacy laws, professional-board rules, HIPAA, or any other law or standard. Client should obtain legal review of its website, forms, policies, and accessibility obligations. Accessibility audits or remediation are outside scope unless added in writing.'));

  c.push(h1('11. Warranties; Disclaimer'));
  c.push(para('Each Party represents that it has authority to enter into this Agreement. Except for that express warranty and the correction remedy in Section 7, the Services and deliverables are provided "as is." To the maximum extent permitted by law, Provider disclaims implied warranties, including merchantability, fitness for a particular purpose, non-infringement, and any warranty arising from course of dealing.'));

  c.push(h1('12. Indemnification'));
  c.push(para('Client will defend, indemnify, and hold harmless Provider and its personnel from third-party claims arising from Client materials, Client\'s services or clinical practices, Client\'s legal or regulatory noncompliance, or Client\'s material breach. Provider will defend, indemnify, and hold harmless Client from third-party claims arising from Provider\'s gross negligence, willful misconduct, or knowing infringement in Provider-created, original deliverables. The indemnified Party must give prompt notice and reasonable cooperation, and the indemnifying Party may control the defense, but may not admit fault or impose non-monetary obligations without consent.'));

  c.push(h1('13. Limitation of Liability'));
  c.push(para(`To the maximum extent permitted by law, neither Party will be liable for indirect, incidental, special, exemplary, punitive, or consequential damages, or lost profits, revenue, goodwill, data, or business opportunities. Provider's aggregate liability arising from this Agreement will not exceed the total fees paid or payable during the Initial Term (${money(total)}). These limits do not apply where prohibited by law or to a Party's fraud, willful misconduct, confidentiality breach, or indemnification obligations.`));

  c.push(h1('14. Force Majeure'));
  c.push(para('Neither Party is liable for delay or failure caused by events beyond its reasonable control, including natural disasters, labor disputes, internet or utility failures, cyberattacks, government action, epidemics, or third-party platform outages. The affected Party will give prompt notice and resume performance when reasonably possible. Payment obligations for Services already performed are not excused.'));

  c.push(h1('15. Governing Law; Venue'));
  c.push(para('South Carolina law governs this Agreement, without regard to conflict-of-law rules. Subject to any non-waivable right or law, the state and federal courts located in York County, South Carolina will have exclusive jurisdiction, and each Party consents to that venue.'));

  c.push(h1('16. Notices'));
  c.push(para('Formal notices must be in writing and sent to the postal and notice-email addresses above (as updated by written notice). Email notice is effective when sent if no delivery-failure message is received; mailed notice is effective upon confirmed delivery.'));

  c.push(h1('17. General'));
  c.push(para('This Agreement and Exhibit A are the entire agreement concerning the Services and supersede prior proposals and discussions. If they conflict, this Agreement controls unless Exhibit A expressly identifies the provision it overrides. Amendments and waivers must be in a writing signed by both Parties. Client may not assign this Agreement without Provider\'s written consent; Provider may assign it in connection with a merger, reorganization, or sale of substantially all related assets. The Parties are independent contractors, with no partnership, agency, employment, or fiduciary relationship. If a provision is unenforceable, it will be modified to the minimum extent necessary and the remainder will continue. A waiver once is not a continuing waiver. Headings are for convenience only.'));
  c.push(para('This Agreement may be signed in counterparts and by electronic signature, each treated as an original and together constituting one instrument.'));

  // Signatures follow the terms directly (no blank gap before the signature block)
  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 360, after: 120 }, keepNext: true,
    children: [new TextRun({ text: 'SIGNATURES', bold: true, size: 32, color: NAVY })] }));
  c.push(para('By signing below, each signer confirms that they are authorized to sign on behalf of the Party named above their signature, and the Parties agree to the terms of this Agreement, including Exhibit A. The Effective Date is the date of the later signature.', { keepNext: true }));
  c.push(signatureTable(client));

  // Exhibit A
  c.push(pageBreak());
  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 20 },
    children: [new TextRun({ text: 'EXHIBIT A', bold: true, size: 32, color: NAVY })] }));
  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 20 },
    children: [new TextRun({ text: 'STATEMENT OF WORK', bold: true, size: 24, color: NAVY })] }));
  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 160 },
    children: [new TextRun({ text: `${tier.planName} | ${money(tier.monthly)} per month`, bold: true, color: ORANGE })] }));

  const letters = 'ABCDEFGHIJ';
  let li = 0;
  const sec = t => c.push(h2(`${letters[li++]}. ${t}`));

  sec('Service Objective');
  c.push(para(tier.objective));

  sec('Included Services');
  tier.included(client).forEach(b => c.push(bullet(b)));

  const allowances = (client.allowances && client.allowances[tier.key]) || tier.allowances;
  if (allowances) {
    sec('Content Allowances');
    allowances.forEach(b => c.push(bullet(b)));
  }

  if (client.platformNote) {
    sec('Platform Note');
    c.push(para(client.platformNote));
  }

  sec('Not Included');
  tier.excluded.forEach(b => c.push(bullet(b)));

  sec('Workflow and Assumptions');
  [
    'Provider will prioritize work based on SEO impact, technical feasibility, and timely Client approvals.',
    'Client will keep its website, hosting, domain, theme, plugins, and required third-party accounts active and in good standing.',
    'Client will provide credentials through a reasonably secure method and will maintain backups appropriate to its risk.',
    ...(tier.allowances ? [
      'Each included page addition and blog post draft is subject to timely source information from Client and one consolidated round of reasonable revisions. Client reviews and approves all content before publication. Substantial rewrites, material redesigns, or changes in direction require a written change order.',
      'Allowances apply only to the service month or period stated above, expire at the end of that month or period, and do not roll over unless Provider agrees otherwise in writing.',
    ] : [
      'New pages, blog posts, substantial copywriting, material redesigns, and changes in direction require a written change order.',
    ]),
    'A "service month" is each consecutive one-month period beginning on the Effective Date.',
    'Reports may be delivered electronically. Meetings are not included unless separately scheduled or stated in writing.',
  ].forEach(b => c.push(bullet(b)));

  sec('Fees and Schedule');
  c.push(feeTable([
    ['Plan', tier.planName],
    ['Monthly Fee', money(tier.monthly)],
    ['Initial Term', '12 consecutive months'],
    ['Total Contract Value', `${money(total)}, plus applicable taxes`],
    ['Setup Fee', 'None'],
  ]));
  c.push(para('Payment is due in advance as stated in Section 3 of the Agreement.'));

  sec(tier.key === 'spark' ? 'Renewal Services' : 'SEO-Only Renewal Services');
  c.push(para(tier.key === 'spark'
    ? 'Beginning after the Initial Term, each renewal term is billed at $55.00 per month and continues the Spark SEO – The Foundation services listed in Section B of this Exhibit.'
    : 'Beginning after the Initial Term, each renewal term is billed at $55.00 per month. Renewal services are limited to the Spark SEO – The Foundation plan and include on-page SEO optimization, local keyword research and targeting, image optimization for SEO and accessibility, Google Business Profile optimization, monthly monitoring and performance checks, and monthly reporting. Technical SEO improvements, analytics or Search Console setup, schema implementation, content recommendations, page additions, blog drafting, competitor research, and advanced SEO strategy are not included during a renewal term unless added by written change order.'));

  const footerName = client.footerName;
  return new Document({
    creator: 'Part of Practice, LLC',
    title: `Website Support & SEO Services Agreement - ${tier.planName}`,
    styles: { default: { document: { run: { font: FONT, size: 20 } } } },
    numbering: { config: [{ reference: 'bullets', levels: [{ level: 0, format: LevelFormat.BULLET, text: '•',
      alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 540, hanging: 270 } } } }] }] },
    sections: [{
      properties: { page: { size: { width: 12240, height: 15840 },
        margin: { top: 1260, bottom: 1080, left: 1440, right: 1440 } } },
      headers: { default: new Header({ children: [new Paragraph({ alignment: AlignmentType.RIGHT,
        children: [new TextRun({ text: 'PARTS OF PRACTICE  |  SERVICES AGREEMENT', bold: true, size: 16, color: GREY })] })] }) },
      footers: { default: new Footer({ children: [new Paragraph({ alignment: AlignmentType.CENTER, children: [
        ...runs(`${footerName}  |  Page `, { size: 16, color: GREY }),
        new TextRun({ children: [PageNumber.CURRENT], size: 16, color: GREY }),
        new TextRun({ text: ' of ', size: 16, color: GREY }),
        new TextRun({ children: [PageNumber.TOTAL_PAGES], size: 16, color: GREY }),
      ] })] }) },
      children: c,
    }],
  });
}

// ---------- build ----------
const BLANK_CLIENT = {
  legalName: '[[CLIENT LEGAL NAME]]',
  footerName: '[[CLIENT LEGAL NAME]]',
  address: ['[[Street Address, Suite]]', '[[City, State ZIP]]'],
  phone: '[[(000) 000-0000]]',
  email: '[[client@email.com]]',
  serviceArea: '[[identified service area(s)]]',
  specialties: '[[Client specialties]]',
};

async function write(doc, file) {
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, await Packer.toBuffer(doc));
  console.log('wrote', path.relative(process.cwd(), file));
}

(async () => {
  for (const tier of Object.values(TIERS).filter(t => t.key)) {
    await write(buildDoc(tier, BLANK_CLIENT),
      path.join(__dirname, 'output', `TEMPLATE-${tier.fileTag}-Agreement.docx`));
  }
  for (const client of CLIENTS) {
    for (const key of client.tiers) {
      const tier = TIERS[key];
      await write(buildDoc(tier, client),
        path.join(__dirname, '..', '..', 'clients', client.folder, `${client.prefix}-${tier.fileTag}-Agreement.docx`));
    }
  }
})();
