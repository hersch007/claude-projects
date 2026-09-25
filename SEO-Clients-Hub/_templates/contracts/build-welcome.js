// Builds the client Welcome & Access Checklist sent with (or right after) the signed agreement.
//
//   node build-welcome.js   -> blank template in ./output/ + one per client in clients.js (with welcomeTier set)
//
// Text wrapped in [[...]] renders as a yellow-highlighted fill-in blank.

const fs = require('fs');
const path = require('path');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType,
  AlignmentType, BorderStyle, ShadingType, Header, Footer, PageNumber, LevelFormat,
} = require('docx');
const TIERS = require('./tiers');
const CLIENTS = require('./clients');

const NAVY = '1F2A44';
const ORANGE = 'E0552B';
const GREY = '6B7280';
const CONTENT_W = 9360;
const PROVIDER_EMAIL = 'PartsofPractice@gmail.com';
const money = n => '$' + n.toLocaleString('en-US');

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

const para = (text, opts = {}) => new Paragraph({ children: runs(text, opts.run), spacing: { after: 100, line: 264 } });
const h1 = text => new Paragraph({ children: [new TextRun({ text, bold: true, size: 26, color: NAVY })], spacing: { before: 240, after: 100 }, keepNext: true });
const check = text => new Paragraph({ children: runs(text), numbering: { reference: 'boxes', level: 0 }, spacing: { after: 70, line: 264 } });
// Each numbered list restarts at 1: pass a distinct instance per list.
const step = (text, instance) => new Paragraph({ children: runs(text, { size: 19, color: '374151' }), numbering: { reference: 'steps', level: 0, instance }, spacing: { after: 40, line: 252 } });

const border = { style: BorderStyle.SINGLE, size: 4, color: 'CBD5E1' };
const borders = { top: border, bottom: border, left: border, right: border };
function table(rows) {
  const w1 = 2800, w2 = CONTENT_W - w1;
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [w1, w2],
    rows: rows.map(([k, v]) => new TableRow({ children: [
      new TableCell({ width: { size: w1, type: WidthType.DXA }, borders, margins: { top: 70, bottom: 70, left: 120, right: 120 },
        shading: { type: ShadingType.CLEAR, fill: 'F1F3F8', color: 'auto' },
        children: [new Paragraph({ children: [new TextRun({ text: k, bold: true })] })] }),
      new TableCell({ width: { size: w2, type: WidthType.DXA }, borders, margins: { top: 70, bottom: 70, left: 120, right: 120 },
        children: [new Paragraph({ children: runs(v) })] }),
    ] })),
  });
}
function callout(text) {
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [CONTENT_W],
    rows: [new TableRow({ children: [new TableCell({
      width: { size: CONTENT_W, type: WidthType.DXA },
      borders: { top: { style: BorderStyle.NONE }, bottom: { style: BorderStyle.NONE }, right: { style: BorderStyle.NONE },
        left: { style: BorderStyle.SINGLE, size: 24, color: ORANGE } },
      shading: { type: ShadingType.CLEAR, fill: 'FFF7ED', color: 'auto' },
      margins: { top: 100, bottom: 100, left: 180, right: 140 },
      children: [new Paragraph({ children: runs(text, { size: 19 }) })],
    })] })],
  });
}

// Website-access steps by platform. Unknown platform -> fill-in.
const WEBSITE_STEPS = {
  Squarespace: [
    'Log in to Squarespace and open your website.',
    'Go to **Settings → Permissions & Ownership** and click **Invite Contributor**.',
    `Enter **${PROVIDER_EMAIL}**, choose **Administrator**, and send the invitation.`,
    'While you are in Settings, check **Billing** to see your plan. Schema markup needs the **Business plan or higher** (it uses Code Injection); let us know which plan you have.',
  ],
  WordPress: [
    'Log in to your WordPress dashboard.',
    'Go to **Users → Add New User**.',
    `Enter **${PROVIDER_EMAIL}**, set the role to **Administrator**, and click **Add New User**.`,
  ],
  Wix: [
    'Log in to Wix and open your site dashboard.',
    'Go to **Settings → Roles & Permissions** and click **Invite People**.',
    `Enter **${PROVIDER_EMAIL}**, choose **Admin (Co-Owner)**, and send the invitation.`,
  ],
};

function buildDoc(client, tierKey) {
  const tier = tierKey && TIERS[tierKey];
  const c = [];
  let listNo = 0;
  const stepsOf = () => { const id = ++listNo; return t => c.push(step(t, id)); };
  const first = client.contactFirstName || '[[First Name]]';

  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 40 },
    children: [new TextRun({ text: 'WELCOME TO PARTS OF PRACTICE', bold: true, size: 36, color: NAVY })] }));
  c.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 220 },
    children: [...runs(`Getting Started Checklist  |  ${client.legalName}`, { bold: true, size: 22, color: ORANGE })] }));

  c.push(para(`Hi ${first},`));
  c.push(para('Thank you for choosing Parts of Practice. To start work in your first week, we need a few things from you. Most of this takes about 15 minutes. Please complete it within **5 business days** of signing, and reply to this email when you are done or if anything gets stuck.'));
  c.push(callout('**Please never send client or patient information to us.** We do not need it, and our agreement does not allow it. Also, please do not email passwords: the invitations below let you give us access without sharing a password, and you can remove it at any time.'));

  c.push(h1('Your Plan at a Glance'));
  const summary = tier ? ((client.summaries && client.summaries[tierKey]) || TIERS.SUMMARIES[tierKey]) : '[[Page / blog allowance]]';
  c.push(table([
    ['Plan', tier ? `${tier.planName} — ${money(tier.monthly)}/month` : '[[Plan name — $__/month]]'],
    ['Term', '12 months, starting on the date the agreement is signed'],
    ['Billing', 'Monthly, in advance, on the same day of the month you signed'],
    ['Content included', summary],
    ['Extra work', `Additional page ${money(TIERS.RATES.page)} · Additional blog post ${money(TIERS.RATES.blog)} · Custom work quoted separately`],
    ['Reports', 'Monthly, by email, in plain English'],
  ]));

  c.push(h1('Step 1 — Website Access'));
  const site = client.website ? ` (${client.website})` : '';
  c.push(para(`Your website${site} runs on **${client.platform || '[[Platform]]'}**.`));
  (WEBSITE_STEPS[client.platform] || ['[[Platform-specific steps to add ' + PROVIDER_EMAIL + ' as an Administrator]]']).forEach(stepsOf());
  c.push(check('Website access sent'));

  c.push(h1('Step 2 — Google Business Profile'));
  [
    'Sign in to the Google account that manages your Business Profile, then search Google for your practice name (or go to business.google.com).',
    'Click the **⋮ (three dots)** menu → **Business Profile settings** → **Managers** → **Add**.',
    `Enter **${PROVIDER_EMAIL}**, choose **Manager**, and click **Invite**.`,
  ].forEach(stepsOf());
  c.push(check('Google Business Profile access sent'));

  c.push(h1('Step 3 — Google Analytics and Search Console'));
  c.push(para('If you do not have these yet, just tell us. We will set them up under **your** Google account so you always own the data.'));
  c.push(para('**Google Analytics:**'));
  [
    'Go to analytics.google.com and click **Admin** (the gear icon, bottom left).',
    'Under Property, click **Property access management** → **+** → **Add users**.',
    `Enter **${PROVIDER_EMAIL}**, choose the **Editor** role, and click **Add**.`,
  ].forEach(stepsOf());
  c.push(para('**Google Search Console:**'));
  [
    'Go to search.google.com/search-console and select your website.',
    'Click **Settings** → **Users and permissions** → **Add user**.',
    `Enter **${PROVIDER_EMAIL}**, choose **Full**, and click **Add**.`,
  ].forEach(stepsOf());
  c.push(check('Google Analytics access sent (or "I don\'t have it")'));
  c.push(check('Search Console access sent (or "I don\'t have it")'));

  c.push(h1('Step 4 — About Your Practice'));
  c.push(para('Reply with whatever you have; bullet points are fine. This is what helps Google (and future clients) trust your site.'));
  [
    'The services and specialties you offer, and the **top 2–3 you most want to grow**',
    'The cities or areas you serve, and the states where you offer telehealth',
    'Your bio, credentials, license type and state(s), and trainings or certifications',
    'A professional headshot and any office photos you own the rights to',
    'Current fees and insurance accepted (so the website stays accurate)',
    'Links to your directory listings (Psychology Today, TherapyDen, insurance directories, etc.)',
    'Anything to avoid: topics, wording, or claims you do not want on your site',
  ].forEach(t => c.push(check(t)));

  c.push(h1('Step 5 — How We Work Together'));
  c.push(check('Your main contact for approvals (name, email, and best way to reach you)'));
  c.push(check('Payment method set up for monthly billing'));
  c.push(para('We send drafts of new pages and posts for your review before anything goes live. You check them for clinical accuracy, and we ask for approval (or one round of combined edits) within 5 business days.'));

  c.push(h1('What Happens Next'));
  c.push(table([
    ['Week 1', 'Access confirmed, kickoff email, and fixes to your most urgent audit issues begin'],
    ['Weeks 2–4', 'Page titles, meta descriptions, Google Business Profile, and (where included) schema and analytics'],
    ['End of month 1', 'Your first monthly report: what we did, what it means, and what comes next'],
    ['Every month', 'Ongoing SEO work, included content, and a monthly report'],
  ]));
  c.push(para(''));
  c.push(para(`Questions at any time: **${PROVIDER_EMAIL}**. We're glad to be working with you.`));
  c.push(para('— The Parts of Practice team'));

  return new Document({
    creator: 'Part of Practice, LLC',
    title: 'Welcome & Getting Started Checklist',
    styles: { default: { document: { run: { font: 'Arial', size: 20 } } } },
    numbering: { config: [
      { reference: 'boxes', levels: [{ level: 0, format: LevelFormat.BULLET, text: '☐', alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 460, hanging: 340 } }, run: { font: 'Segoe UI Symbol', size: 24 } } }] },
      { reference: 'steps', levels: [{ level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 460, hanging: 300 } } } }] },
    ] },
    sections: [{
      properties: { page: { size: { width: 12240, height: 15840 }, margin: { top: 1150, bottom: 1000, left: 1440, right: 1440 } } },
      headers: { default: new Header({ children: [new Paragraph({ alignment: AlignmentType.RIGHT,
        children: [new TextRun({ text: 'PARTS OF PRACTICE  |  GETTING STARTED', bold: true, size: 16, color: GREY })] })] }) },
      footers: { default: new Footer({ children: [new Paragraph({ alignment: AlignmentType.CENTER, children: [
        new TextRun({ text: `${PROVIDER_EMAIL}  |  Page `, size: 16, color: GREY }),
        new TextRun({ children: [PageNumber.CURRENT], size: 16, color: GREY }),
        new TextRun({ text: ' of ', size: 16, color: GREY }),
        new TextRun({ children: [PageNumber.TOTAL_PAGES], size: 16, color: GREY }),
      ] })] }) },
      children: c,
    }],
  });
}

async function write(doc, file) {
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, await Packer.toBuffer(doc));
  console.log('wrote', path.relative(process.cwd(), file));
}

(async () => {
  await write(buildDoc({ legalName: '[[CLIENT LEGAL NAME]]' }, null), path.join(__dirname, 'output', 'TEMPLATE-Welcome-Checklist.docx'));
  for (const client of CLIENTS.filter(cl => cl.welcomeTier)) {
    await write(buildDoc(client, client.welcomeTier),
      path.join(__dirname, '..', '..', 'clients', client.folder, `${client.prefix}-Welcome-Checklist.docx`));
  }
})();
