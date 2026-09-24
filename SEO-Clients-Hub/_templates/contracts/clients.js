// Client-specific contracts. tiers: which plan versions to generate (keys from tiers.js).
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
    platformNote: "Client's website runs on Squarespace. Schema implementation requires custom code injection, which is available on the Squarespace Business plan or higher. If Client does not maintain an eligible plan, Provider will implement schema only to the extent the platform allows, and the remaining Services and fees are unaffected. All other included Services work on all Squarespace plans.",
  },
];
