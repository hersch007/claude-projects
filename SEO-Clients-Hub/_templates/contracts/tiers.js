// Scope for each tier on the Parts of Practice SEO pricing sheet.
// included(client) gets the client so service area / specialties can be filled in.

const CORE_SPARK = c => [
  'On-page SEO optimization, including titles, meta descriptions, and headings.',
  `Local keyword research and targeting relevant to Client's specialties (${c.specialties}) and identified service area (${c.serviceArea}).`,
  'Image optimization for SEO and accessibility (including alt text), limited to images touched during included work.',
  'Google Business Profile optimization, subject to platform access, verification, and policy.',
  'Monthly monitoring and performance checks.',
  'Monthly reporting.',
];

const SUPPORT_EXTRAS = [
  'Technical SEO improvements that are reasonably achievable within the existing website and hosting framework.',
  'Google Analytics and Google Search Console setup or configuration, using Client-owned accounts where available.',
  'Schema implementation appropriate to the business, such as LocalBusiness and therapist-related schema, where technically appropriate.',
  'Content and SEO recommendations.',
];

const COMMON_EXCLUDED = [
  'Website hosting, domain registration, email hosting, platform subscriptions, paid themes, premium plugins, stock media, or other third-party fees.',
  'A website rebuild, migration, replatforming, custom application development, custom plugin development, or material redesign of the existing framework.',
];
const TAIL_EXCLUDED = [
  'Legal, clinical, privacy, HIPAA, cybersecurity, or accessibility audits, certifications, or remediation.',
  'Emergency support, restoration from backup, malware removal, incident response, or repair of pre-existing defects unless added by change order.',
];

// What one "page" / "blog post" counts as, so allowances can't stretch into custom work.
const PAGE_DEF = 'A "page addition" means one standard informational content page of up to approximately eight hundred (800) words, built using an existing layout or template on Client\'s website. Custom-designed layouts, landing pages with forms, booking tools, or other integrations, and pages exceeding this length require a written change order.';
const BLOG_DEF = 'A "blog post draft" means one SEO-optimized article of up to approximately one thousand (1,000) words, drafted for Client\'s review and approval before publication.';

module.exports = {
  spark: {
    key: 'spark',
    fileTag: 'Spark-SEO-55',
    planName: 'Spark SEO – The Foundation',
    monthly: 55,
    objective: "Build a strong local SEO foundation for Client's practice using Client's existing website, hosting, and technical framework.",
    included: c => CORE_SPARK(c),
    excluded: [
      ...COMMON_EXCLUDED,
      'Technical SEO improvements, Google Analytics or Search Console setup, schema implementation, and content and SEO recommendations.',
      'Web page additions, blog post drafting, competitor research, advanced SEO strategy, paid advertising, or social-media services.',
      ...TAIL_EXCLUDED,
    ],
  },

  support: {
    key: 'support',
    fileTag: 'Website-SEO-Support-99',
    planName: 'Website + SEO Support',
    monthly: 99,
    objective: "Strengthen Client's online presence and correct critical search gaps through ongoing website support and search-engine optimization within Client's existing website, hosting, and technical framework.",
    included: c => [...CORE_SPARK(c).slice(0, 4), ...SUPPORT_EXTRAS, ...CORE_SPARK(c).slice(4)],
    allowances: [
      'Web page additions: up to two (2) new pages per service month during service months one (1) and two (2), then up to four (4) new pages in total during service months three (3) through twelve (12).',
      PAGE_DEF,
    ],
    excluded: [
      ...COMMON_EXCLUDED,
      'Blog post drafting, competitor research, advanced SEO strategy (including AI-search and long-term growth strategy), paid advertising, or social-media services.',
      ...TAIL_EXCLUDED,
    ],
  },

  growth: {
    key: 'growth',
    fileTag: 'Growth-SEO-149',
    planName: 'Growth SEO',
    monthly: 149,
    objective: "Grow Client's search visibility, attract more clients, and build authority through advanced SEO, ongoing content creation, and website support within Client's existing website, hosting, and technical framework.",
    included: c => [
      'On-page SEO optimization, including titles, meta descriptions, and headings.',
      `Advanced specialty keyword research and targeting relevant to Client's specialties (${c.specialties}) and identified service area (${c.serviceArea}).`,
      ...CORE_SPARK(c).slice(2, 4),
      ...SUPPORT_EXTRAS,
      'Competitor research and insights.',
      'Advanced SEO strategy, including AI-search visibility and long-term growth recommendations.',
      ...CORE_SPARK(c).slice(4),
    ],
    allowances: [
      'Web page additions: up to two (2) new pages per service month during service months one (1) through six (6), then up to one (1) new page per service month during service months seven (7) through twelve (12).',
      'Blog post drafts: up to two (2) SEO-optimized blog post drafts per service month throughout the Initial Term.',
      PAGE_DEF,
      BLOG_DEF,
    ],
    excluded: [
      ...COMMON_EXCLUDED,
      'Paid advertising, social-media services, public-relations services, or other marketing work not expressly included above.',
      ...TAIL_EXCLUDED,
    ],
  },
};

module.exports.PAGE_DEF = PAGE_DEF;
