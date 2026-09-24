// Client-specific contracts. tiers: which plan versions to generate (keys from tiers.js).
const { PAGE_DEF } = require('./tiers');

module.exports = [
  {
    folder: 'WanderingFreeCounseling',
    prefix: 'WFC',
    tiers: ['support', 'spark', 'growth'],
    legalName: 'Wandering Free Counseling, LLC',
    footerName: 'Wandering Free Counseling, LLC',
    address: ['Attn: Erin Zarlino, LPCC', '5995 Wilcox Place, Suite D', 'Dublin, OH 43016'],
    phone: '(614) 881-2439',
    email: 'info@wanderingfreecounseling.sprucecare.com',
    serviceArea: 'Dublin, Columbus, and central Ohio',
    specialties: 'trauma therapy, EMDR and EMDR Intensives, childhood trauma, and perinatal trauma',
    // Erin was quoted the Sept 2026 pricing sheet ("3 first 2 mo, then 1-2/mo"), so her $99 contract honors it.
    allowances: {
      support: [
        'Web page additions: up to three (3) new pages in total during service months one (1) and two (2), then up to two (2) new pages per service month during service months three (3) through twelve (12).',
        PAGE_DEF,
      ],
    },
    platformNote: "Client's website runs on Squarespace. Schema implementation requires custom code injection, which is available on the Squarespace Business plan or higher. If Client does not maintain an eligible plan, Provider will implement schema only to the extent the platform allows, and the remaining Services and fees are unaffected. All other included Services work on all Squarespace plans.",
  },
];
