// Builder for the C-P Flexible Packaging BLOG SEO Audit running log.
// Add one object to BLOG[] per audited blog/news article, then run: node build-blog-audit.js
const fs = require("fs");
const docx = require("C:/Users/richa/AppData/Roaming/npm/node_modules/docx");
const {
  Document, Packer, Paragraph, TextRun, AlignmentType, HeadingLevel, BorderStyle,
  ShadingType, PageNumber, Header, Footer, LevelFormat, ExternalHyperlink,
} = docx;

const BRAND = "215732";

// ---------------------------------------------------------------------------
const BLOG = [
  {
    section: "Learning Center",
    name: "What Is Cold-Seal Packaging?",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/what-is-cold-seal-packaging",
    prodUrl: "https://gcpflexpack.com/learning-center/what-is-cold-seal-packaging",
    published: "2019-10-16", words: "~800", audited: "2026-08-17",
    h1: "What is cold-seal packaging?  (good — no change)",
    title: "What Is Cold-Seal Packaging? | C-P Flexible Packaging", titleChars: 53,
    meta: "Cold-seal packaging uses pressure-activated adhesive to seal up to 10x faster than heat-seal—ideal for heat-sensitive bars, chocolate & candy.", metaChars: 142,
    slug: "what-is-cold-seal-packaging",
    images: [],
    links: ["/products/cold-seal-packaging", "cold-seal-packaging-guide"],
    notes: ["Cold-seal cluster member — link up to the cluster hub ('How to Choose a Cold-Seal Supplier')."],
  },
  {
    section: "Learning Center",
    name: "3 Benefits of Cold-Seal Packaging",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/benefits-of-cold-seal-packaging",
    prodUrl: "https://gcpflexpack.com/learning-center/benefits-of-cold-seal-packaging",
    published: "2019-10-16", words: "~650", audited: "2026-08-17",
    h1: "3 key advantages of cold-seal packaging  (good)",
    title: "3 Benefits of Cold-Seal Packaging | C-P Flexible Packaging", titleChars: 58,
    meta: "3 advantages of cold-seal packaging: no heat, up to 10x faster speeds, and it runs on your existing heat-seal equipment. See why it fits your line.", metaChars: 147,
    slug: "benefits-of-cold-seal-packaging",
    images: [],
    links: ["/products/cold-seal-packaging"],
    notes: ["Cold-seal cluster member."],
  },
  {
    section: "Learning Center",
    name: "How to Choose a Cold-Seal Supplier",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/vetting-potential-cold-seal-packaging-suppliers",
    prodUrl: "https://gcpflexpack.com/learning-center/vetting-potential-cold-seal-packaging-suppliers",
    published: "2020-05-05", words: "~2,100 (strongest)", audited: "2026-08-17",
    h1: "Vetting potential cold-seal packaging suppliers  (good)",
    title: "How to Choose a Cold-Seal Supplier | C-P Flexible Packaging", titleChars: 59,
    meta: "How co-packers should vet a cold-seal packaging supplier: 4 criteria—customization, scheduling, quality control & technical support.", metaChars: 132,
    slug: "vetting-potential-cold-seal-packaging-suppliers",
    images: [
      ["IMG_2744-scaled-e1588769586589-768x425.webp", "(none)", "Cold-seal packaging run on a C-P Flexible Packaging production line", "58"],
      ["CPF-Vetting-Cold-image-1.webp", "(none)", "Diagram comparing monolayer and multilayer cold-seal packaging structures", "72"],
    ],
    links: ["/products/cold-seal-packaging"],
    notes: ["CLUSTER HUB — longest, most comprehensive cold-seal article. Link the other cold-seal pieces up to this one."],
  },
  {
    section: "Learning Center",
    name: "Short-Run Cold-Seal Packaging for Co-Packers",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/short-run-cold-seal-packaging-for-co-packers",
    prodUrl: "https://gcpflexpack.com/learning-center/short-run-cold-seal-packaging-for-co-packers",
    published: "2020-05-04", words: "~1,800", audited: "2026-08-17",
    h1: "Short-run cold-seal packaging for co-packers  (good)",
    title: "Short-Run Cold-Seal Packaging for Co-Packers | C-P Flexible", titleChars: 59,
    meta: "Short-run cold-seal packaging for co-packers—up to 10x faster speeds and lower costs without long runs. See how C-P makes it accessible.", metaChars: 136,
    slug: "short-run-cold-seal-packaging-for-co-packers",
    images: [
      ["CPF-cold-seal-image-2.webp", "Film 1, Adhesive, Film 2, Cold-seal layers", "Cross-section diagram of cold-seal packaging layers: film, adhesive, film", "72"],
      ["IMG_2775-scaled-e1588769784245-768x446.webp", "(none)", "Short-run cold-seal packaging produced at C-P Flexible Packaging", "62"],
    ],
    links: ["/products/cold-seal-packaging"],
    notes: ["Cold-seal cluster member."],
  },
  {
    section: "Learning Center",
    name: "Commercializing Recyclable Stand-Up Pouches",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/commercially-viable-recyclable-stand-up-pouches",
    prodUrl: "https://gcpflexpack.com/learning-center/commercially-viable-recyclable-stand-up-pouches",
    published: "2020-12-22", words: "~1,800", audited: "2026-08-17",
    h1: "Recyclable stand-up pouches: Mapping out your brand's path to commercialization  (OK as H1)",
    title: "Commercializing Recyclable Stand-Up Pouches | C-P Flexible Packaging", titleChars: 68,
    meta: "How CPG brands commercialize recyclable mono-material PE stand-up pouches—design, equipment fit & FTC compliance. A practical roadmap from C-P.", metaChars: 143,
    slug: "commercially-viable-recyclable-stand-up-pouches",
    images: [
      ["recyclable-pouch.webp", "(none)", "Recyclable mono-material stand-up pouch from C-P Flexible Packaging", "65"],
      ["trees.webp", "(none)", "Forest trees representing sustainable, recyclable flexible packaging", "66"],
      ["family-recycling.webp", "(none)", "Family recycling flexible packaging at a store drop-off point", "60"],
    ],
    links: ["/products/premade-pouches/recyclable-pouches", "/sustainable-packaging/"],
    notes: ["FIX: current title is 78 chars with no brand — replace with the recommended version."],
  },
  {
    section: "Learning Center",
    name: "Peel & Reseal (Reclose) Packaging Benefits",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/the-benefits-of-reclose-technology",
    prodUrl: "https://gcpflexpack.com/learning-center/the-benefits-of-reclose-technology",
    published: "2020-05-06", words: "~450 (lightest — consider expanding)", audited: "2026-08-17",
    h1: "The benefits of reclose technology  (good)",
    title: "Peel & Reseal (Reclose) Packaging Benefits | C-P Flexible Packaging", titleChars: 67,
    meta: "Why reclose (peel & reseal) packaging wins—81% of impulse buyers value resealability. Extend shelf life & keep branding with pressure-sensitive labels.", metaChars: 151,
    slug: "the-benefits-of-reclose-technology",
    images: [
      ["glossy-snack-pack.webp", "(none)", "Glossy snack pack with peel-and-reseal reclose packaging", "56"],
    ],
    links: ["/products/printed-rollstock/peel-reseal-rollstock"],
    notes: ["FIX: current title uses ' - ' and lowercase — use the recommended title-cased version.", "Lightest article (~450 words) — a modest content expansion would help."],
  },
  {
    section: "Learning Center",
    name: "Why Shrink Bands Deliver Tamper Evidence",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/shrink-bands",
    prodUrl: "https://gcpflexpack.com/learning-center/shrink-bands",
    published: "2023-06-13", words: "~850", audited: "2026-08-19",
    h1: "5 reasons why shrink bands are a preferred method for achieving tamper evidence  (good — keep)",
    title: "Why Shrink Bands Deliver Tamper Evidence | C-P Flexible Packaging", titleChars: 63,
    meta: "5 reasons shrink bands are the go-to for tamper-evident packaging—affordable, versatile & secure across bottles, jars & more. See why.", metaChars: 133,
    slug: "shrink-bands",
    images: [
      ["shrink-band-water-bottle.webp", "shrink band showing perforation for easy tearing - tamper evidence", "Shrink band with tear perforation for tamper evidence on a water bottle", "70"],
      ["BassShrink.PreformProduct.webp", "(none)", "Bass shrink band preform product from C-P Flexible Packaging", "58"],
      ["shrink-bands-for-eye-drops.webp", "shrink bands for eye drop bottles", "Tamper-evident shrink bands on eye-drop bottles", "47"],
      ["supplement-bottle-with-shrink-band.webp", "supplement bottle with sealed cap - shrink band", "Supplement bottle sealed with a tamper-evident shrink band", "57"],
    ],
    links: ["/products/printed-rollstock/shrink-bands"],
    notes: ["Current title = the full 79-char H1 with no brand — replace with the recommended title tag (H1 itself can stay).", "Newest article in this batch (2023) — strong, keep as-is content-wise."],
  },
  {
    section: "Learning Center",
    name: "Fix Flexible Packaging Performance & Print Issues",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/fixing-flexible-packaging-performance-print-quality-issues",
    prodUrl: "https://gcpflexpack.com/learning-center/fixing-flexible-packaging-performance-print-quality-issues",
    published: "2020-10-05", words: "~1,100", audited: "2026-08-19",
    h1: "How to fix flexible packaging performance and print-quality issues with your converter  (good)",
    title: "Fix Flexible Packaging Performance & Print Issues | C-P Flexible", titleChars: 63,
    meta: "How to diagnose and fix flexible packaging performance and print-quality issues with your converter—troubleshooting tips from C-P's technical team.", metaChars: 146,
    slug: "fixing-flexible-packaging-performance-print-quality-issues",
    images: [
      ["CPF-blog-image.jpg", "Fixing flexible packaging performance issues", "Technician inspecting flexible packaging performance issues at C-P", "64"],
      ["CPF-examining-labels-image.jpg", "Fixing flexible packaging printing issues", "Examining printed labels for flexible packaging print quality", "60"],
    ],
    links: ["/capabilities/high-definition-flexographic-printing", "/capabilities/flexible-package-design-and-consultation"],
    notes: ["Current title = the full 86-char H1 with no brand — replace with the recommended title tag.", "Consider converting the two .jpg images to WebP for Core Web Vitals."],
  },
  {
    section: "Learning Center",
    name: "Die-Cut Rollstock Meat Packaging (Case Study)",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/how-an-artisan-meats-company-improved-their-productivity-and-branding-with-die-cut-rollstock",
    prodUrl: "https://gcpflexpack.com/learning-center/how-an-artisan-meats-company-improved-their-productivity-and-branding-with-die-cut-rollstock",
    published: "2021-10-04", words: "~650", audited: "2026-08-19",
    h1: "CURRENT (91 chars — too long): “How an artisan meats company improved their productivity and branding with die-cut rollstock”  →  SHORTEN TO: “How Die-Cut Rollstock Boosted an Artisan Meat Brand's Productivity” (65)",
    title: "Die-Cut Rollstock Meat Packaging Case Study | C-P Flexible", titleChars: 58,
    meta: "How an artisan meats company boosted productivity and branding by switching to laser die-cut rollstock for flow-wrapping. A C-P case study.", metaChars: 137,
    slug: "how-an-artisan-meats-company-improved-their-productivity-and-branding-with-die-cut-rollstock",
    images: [
      ["elevation-meats-chad-2.webp", "(none)", "Elevation Meats team member with die-cut rollstock packaging", "60"],
      ["hand-wrapped-packaging.webp", "(none)", "Hand-wrapped artisan meat packaging before die-cut rollstock", "59"],
      ["dcrs-meats-packaging.webp", "(none)", "Laser die-cut rollstock packaging for artisan meats", "51"],
      ["elevation-charcuterie-packaging-768x714.webp", "(none)", "Elevation charcuterie in die-cut rollstock flow-wrap packaging", "61"],
      ["flow-wrap-meats-packaging-1.webp", "(none)", "Flow-wrapped meat packaging using laser die-cut rollstock", "57"],
    ],
    links: ["/products/printed-rollstock/laser-die-cut-window-rollstock", "/products/printed-rollstock/peel-reseal-rollstock"],
    notes: [
      "URL SLUG TOO LONG (~90 chars). NEW SLUG: die-cut-rollstock-meat-case-study  →  full URL /learning-center/die-cut-rollstock-meat-case-study",
      "Change the slug NOW (pre-launch = free, no 301 needed). After launch it would require a 301 redirect from the old URL.",
      "After changing the slug, update any internal links pointing to this article to use the new slug.",
      "Real CASE STUDY — strong social-proof asset. Current title tag ('DCRS Flow Wrap Meat Packaging') uses jargon; recommended is clearer.",
      "phone.svg is a decorative icon — leave alt empty (alt=\"\").",
    ],
  },
  {
    section: "Learning Center",
    name: "Cut Flexible Packaging Costs & Delivery Issues",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/how-to-cut-flexible-packaging-costs-eliminate-delivery-issues",
    prodUrl: "https://gcpflexpack.com/learning-center/how-to-cut-flexible-packaging-costs-eliminate-delivery-issues",
    published: "2020-10-12", words: "~1,100", audited: "2026-08-19",
    h1: "How to cut flexible packaging costs and eliminate delivery issues  (good)",
    title: "Cut Flexible Packaging Costs & Delivery Issues | C-P Flexible", titleChars: 60,
    meta: "How to cut flexible packaging costs and eliminate delivery issues—choose a supplier with the right supply-chain strategy. Tips from C-P.", metaChars: 135,
    slug: "how-to-cut-flexible-packaging-costs-eliminate-delivery-issues",
    images: [
      ["flexographic.webp", "Eliminate flexible packaging supply and delivery issues", "Flexographic press eliminating flexible packaging delivery issues", "64"],
      ["manufacturing-image.webp", "Identifying flexible packaging cost saving opportunities", "Manufacturing floor where flexible packaging cost savings are found", "65"],
    ],
    links: ["/capabilities/finishing-slitting-flexible-packaging", "/about-us"],
    notes: ["Current title = the 64-char H1 with no brand — add the recommended branded title tag."],
  },
  {
    section: "Learning Center",
    name: "Redesign Flexible Packaging for Sustainability",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/redesigning-flexible-packaging-sustainability-shelf-appeal",
    prodUrl: "https://gcpflexpack.com/learning-center/redesigning-flexible-packaging-sustainability-shelf-appeal",
    published: "2020-10-14", words: "~850", audited: "2026-08-19",
    h1: "How to redesign your flexible packaging for sustainability and enhanced shelf appeal  (good)",
    title: "Redesign Flexible Packaging for Sustainability | C-P Flexible", titleChars: 60,
    meta: "How to redesign flexible packaging for sustainability and stronger shelf appeal—work with your supplier to hit both goals. A guide from C-P.", metaChars: 138,
    slug: "redesigning-flexible-packaging-sustainability-shelf-appeal",
    images: [
      ["CPF-salty-snacks-image-1.webp", "(none)", "Salty snack flexible packaging redesigned for shelf appeal", "57"],
    ],
    links: ["/sustainable-packaging/", "/capabilities/flexible-package-design-and-consultation"],
    notes: ["DUP ALERT: a bare-path copy exists at /redesigning-flexible-packaging-sustainability-shelf-appeal (no /learning-center/). Set a canonical to THIS URL or 301 the bare one."],
  },
  {
    section: "Learning Center",
    name: "Sustainable Wipes Packaging 101",
    url: "https://gcpflexpack-24024882.hs-sites.com/learning-center/sustainable-wipes-packaging-101",
    prodUrl: "https://gcpflexpack.com/learning-center/sustainable-wipes-packaging-101",
    published: "2021-08-05", words: "~1,100", audited: "2026-08-19",
    h1: "Sustainable Wipes Packaging 101  (good)",
    title: "Sustainable Wipes Packaging 101 | C-P Flexible Packaging", titleChars: 55,
    meta: "Sustainable wipes packaging 101: recyclable, compostable & PCR options for wipes brands. Explore eco-friendly solutions from C-P Flexible Packaging.", metaChars: 147,
    slug: "sustainable-wipes-packaging-101",
    images: [
      ["sustainable-baby-wipes.webp", "eco-friendly baby wipes packaging", "Eco-friendly baby wipes in sustainable flexible packaging", "57"],
      ["sustainable-wipes-packaging-background-1.webp", "recyclable wipes packaging label opening", "Recyclable wipes packaging with a resealable label opening", "58"],
      ["pcr-150x150.webp", "plastic bottles used for wipes packaging", "Post-consumer recycled (PCR) plastic used for wipes packaging", "61"],
    ],
    links: ["/sustainable-packaging/", "/sustainable-packaging/greenstream-products/"],
    notes: ["FIX: current title uses ' - ' — change to ' | '."],
  },
  {
    section: "News",
    name: "Introducing C-P GreenStream Sustainable Packaging",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/introducing-c-p-greenstream-portfolio-of-sustainable-packaging-solutions",
    prodUrl: "https://gcpflexpack.com/news/introducing-c-p-greenstream-portfolio-of-sustainable-packaging-solutions",
    published: "2020-05-27", words: "~350", audited: "2026-08-19",
    h1: "Introducing C-P GreenStream™ Portfolio of Sustainable Packaging Solutions  (keep H1)",
    title: "C-P GreenStream Sustainable Packaging Portfolio | C-P Flexible", titleChars: 61,
    meta: "C-P Flexible Packaging launches GreenStream—PCR, compostable & single-source recyclable films accepted at 18,000+ U.S. retail drop-off sites.", metaChars: 140,
    slug: "introducing-c-p-greenstream-portfolio-of-sustainable-packaging-solutions",
    images: [
      ["greenstream-pouch-mockup-300x300.webp", "(none)", "C-P GreenStream sustainable pouch mockup", "40"],
      ["C-P-GreenStream-1-300x84.webp", "(none)", "C-P GreenStream sustainable packaging portfolio logo", "52"],
    ],
    links: ["/sustainable-packaging/greenstream-products/", "/sustainable-packaging/"],
    notes: ["Current title tag = full H1 + ' - C-P...' (~97 chars) — replace with the recommended branded title tag; keep the H1 as-is."],
  },
  {
    section: "News",
    name: "Voortman Resealable Cookie Packaging (Case)",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-enables-rapid-development-of-voortmans-resealable-cookie-packaging",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-enables-rapid-development-of-voortmans-resealable-cookie-packaging",
    published: "2023-06-22", words: "~250", audited: "2026-08-19",
    h1: "C-P Flexible Packaging Enables Rapid Development of Voortman's Resealable Cookie Packaging  (keep H1)",
    title: "Voortman Resealable Cookie Packaging Case | C-P Flexible", titleChars: 56,
    meta: "How C-P Flexible Packaging developed Voortman's resealable cookie packaging in 6 months—easy-open, easy-reseal, no capital investment.", metaChars: 133,
    slug: "c-p-flexible-packaging-enables-rapid-development-of-voortmans-resealable-cookie-packaging",
    images: [
      ["hostess-c-p.webp", "Voortman Bakery cookies in recloseable packaging supplied by C-P Flexible Packaging. According to research, consumers required a package that was both easy to open and easy to reseal.", "Voortman Bakery cookies in resealable packaging by C-P Flexible Packaging", "72"],
    ],
    links: ["/products/printed-rollstock/peel-reseal-rollstock", "/markets/cookies-bakery-products/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~113 chars) — replace with recommended.", "Current image alt is ~180 chars (too long for alt) — trim to the recommended version; the long text could become a caption instead.", "Strong customer-proof — link to peel/reseal rollstock + cookies market."],
  },
  {
    section: "News",
    name: "Highest BRCGS Ratings for Quality & Safety",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-awarded-highest-achievable-brcgs-ratings-for-product-quality-and-safety",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-awarded-highest-achievable-brcgs-ratings-for-product-quality-and-safety",
    published: "2024-11-14", words: "~450", audited: "2026-08-19",
    h1: "CURRENT: “C-P Flexible Packaging Awarded Highest Achievable BRCGS Ratings for Product Quality and Safety”  →  ADD THE YEAR to distinguish from the 2025 post: “C-P Awarded Highest BRCGS Ratings — 2024 (AA+/AA)”",
    title: "C-P Earns Highest BRCGS Ratings 2024 (AA+/AA) | C-P Flexible", titleChars: 59,
    meta: "C-P Flexible Packaging earns the highest BRCGS ratings—AA+ (Fond du Lac) and AA (Lakeville)—for food-safety and packaging quality.", metaChars: 129,
    slug: "c-p-flexible-packaging-awarded-highest-achievable-brcgs-ratings-for-product-quality-and-safety",
    images: [
      ["brcgs-c-p-fond-du-lac-south-view.jpg", "C-P Flexible Packaging BRCGS Certified", "C-P Flexible Packaging BRCGS-certified plant in Fond du Lac, Wisconsin", "68"],
    ],
    links: ["/about-us", "/locations/fond-du-lac-wisconsin/", "/locations/lakeville-minnesota/"],
    notes: ["THIS IS THE 2024 BRCGS POST. ⚠ CANNIBALIZATION: a separate 2025 BRCGS post exists ('Earns Top BRCGS Rating (AA+)'). Put the YEAR in this title + H1 (done above), and cross-link the two so Google treats them as distinct annual announcements.", "Mentions Fond du Lac + Lakeville plants — link to both location pages (local SEO).", "Image is .jpg — convert to WebP."],
  },
  {
    section: "News",
    name: "New Poly Bag Manufacturing at C-P Buffalo",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-invests-in-new-poly-bag-manufacturing-capabilities",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-invests-in-new-poly-bag-manufacturing-capabilities",
    published: "2021-05-11", words: "~350", audited: "2026-08-19",
    h1: "C-P Flexible Packaging Invests in New Poly Bag Manufacturing Capabilities  (keep H1)",
    title: "New Poly Bag Manufacturing at C-P Buffalo | C-P Flexible", titleChars: 55,
    meta: "C-P Flexible Packaging expands poly bag manufacturing in Buffalo with 3 new Hudson-Sharp lines for food, healthcare & industrial demand.", metaChars: 135,
    slug: "c-p-flexible-packaging-invests-in-new-poly-bag-manufacturing-capabilities",
    images: [
      ["bags-group.webp", "tortilla poly bag, die-cut handle bag, poly bag for cleaning pads, medical bag", "Assorted poly bags: tortilla, die-cut handle, cleaning-pad and medical", "70"],
      ["poly-bag-machines.jpg", "(none)", "New Hudson-Sharp poly bag converting lines at C-P Buffalo", "57"],
      ["poly-bag-dept-low-res.webp", "poly bag manufacturing in buffalo, new york", "Poly bag manufacturing department at C-P Buffalo, New York", "57"],
    ],
    links: ["/products/bags/poly-bags", "/locations/c-p-buffalo/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~97 chars) — replace with recommended.", "Links to the Buffalo location + poly-bags product (this is a Buffalo-plant story).", "poly-bag-machines.jpg — convert to WebP."],
  },
  {
    section: "News",
    name: "High-Performance Lidding for Refrigerated Meals",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/high-performance-packaging-for-extended-shelf-life-refrigerated-meals",
    prodUrl: "https://gcpflexpack.com/news/high-performance-packaging-for-extended-shelf-life-refrigerated-meals",
    published: "2023-02-16", words: "~650", audited: "2026-08-19",
    h1: "C-P Flexible Packaging Collaborates with Northwest Frozen, LLC in Successful Launch of High-Performance Packaging for Extended Shelf-Life Refrigerated Meals  (H1 is ~155 chars — consider shortening to 'High-Performance Lidding for Extended Shelf-Life Refrigerated Meals')",
    title: "High-Performance Lidding for Refrigerated Meals | C-P Flexible", titleChars: 61,
    meta: "C-P's Preferred Packaging + Northwest Frozen launched Affirm lidding film for extended shelf-life refrigerated meals—incl. Oprah's Favorite Things 2022.", metaChars: 150,
    slug: "high-performance-packaging-for-extended-shelf-life-refrigerated-meals",
    images: [],
    links: ["/products/printed-rollstock/lidding-films", "/products/printed-rollstock/high-barrier-flexible-packaging"],
    notes: ["BOTH title tag AND H1 are extremely long (~155–180 chars). Replace the title tag; strongly consider shortening the H1 too.", "Great hook: 'Oprah's Favorite Things 2022' — keep it in the meta."],
  },
  {
    section: "News",
    name: "HPP Lidding Films at IDDBA 2023",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-showcases-proprietary-lidding-films-for-high-pressure-pasteurization-at-iddba-2023",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-showcases-proprietary-lidding-films-for-high-pressure-pasteurization-at-iddba-2023",
    published: "2023-06-01", words: "~280", audited: "2026-08-19",
    h1: "C-P Flexible Packaging Showcases Proprietary Lidding Films for High Pressure Pasteurization at IDDBA 2023  (keep H1)",
    title: "HPP Lidding Films at IDDBA 2023 | C-P Flexible Packaging", titleChars: 55,
    meta: "C-P Flexible Packaging showcased proprietary high-pressure pasteurization (HPP) lidding films at IDDBA 2023 for fresh, minimally-processed foods.", metaChars: 145,
    slug: "c-p-flexible-packaging-showcases-proprietary-lidding-films-for-high-pressure-pasteurization-at-iddba-2023",
    images: [
      ["c-p-iddba-preferred.webp", "IDDBA exhibitor - C-P Flexible Packaging Booth 4983", "C-P Flexible Packaging booth 4983 at IDDBA 2023", "47"],
      ["hpp-films.webp", "The Best Performing High Pressure Pasteurization Films", "High-pressure pasteurization (HPP) lidding films by C-P Flexible Packaging", "73"],
      ["pulp-food-trays.webp", "GreenStream Sustainable Natural Plant-based Pulp Trays for Food Packaging", "GreenStream plant-based pulp food trays from C-P Flexible Packaging", "66"],
    ],
    links: ["/products/printed-rollstock/lidding-films", "/sustainable-packaging/greenstream-products/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~130 chars) — replace with recommended.", "Event news (IDDBA 2023) — dated; lower evergreen value but fine to optimize lightly."],
  },
  {
    section: "News",
    name: "C-P Earns Top BRCGS AA+ Rating (2025)",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-earns-top-brcgs-rating-aa-for-food-packaging-quality-and-safety",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-earns-top-brcgs-rating-aa-for-food-packaging-quality-and-safety",
    published: "2025-07-11", words: "~280", audited: "2026-08-19",
    h1: "CURRENT: “C-P Flexible Packaging Earns Top BRCGS Rating (AA+) for Food Packaging Quality and Safety”  →  ADD THE YEAR: “C-P Earns Top BRCGS Rating — 2025 (AA+)” to distinguish from the 2024 post",
    title: "C-P Earns Top BRCGS AA+ Rating 2025 | C-P Flexible", titleChars: 50,
    meta: "C-P Flexible Packaging earns the top BRCGS rating (AA+) in a 2025 unannounced audit—affirming its food-safety and packaging quality.", metaChars: 130,
    slug: "c-p-flexible-packaging-earns-top-brcgs-rating-aa-for-food-packaging-quality-and-safety",
    images: [
      ["brcgs-c-p-flexible-packaging.webp", "BRCGS Certified Manufacturing - CP Flexible Packaging", "BRCGS-certified manufacturing at C-P Flexible Packaging", "54"],
    ],
    links: ["/about-us"],
    notes: ["THIS IS THE 2025 BRCGS POST. Year is now in the title + H1 (above) to distinguish it from the 2024 post.", "⚠ CANNIBALIZATION FIX: cross-link this 2025 post and the 2024 post to each other (e.g. 'See our 2024 BRCGS results'). Google then treats them as distinct annual updates rather than duplicates."],
  },
  {
    section: "News",
    name: "C-P Acquires Preferred Packaging (Georgia)",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-acquires-preferred-packaging-in-georgia",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-acquires-preferred-packaging-in-georgia",
    published: "2021-10-22", words: "~420", audited: "2026-08-19",
    h1: "C-P Flexible Packaging Acquires Preferred Packaging in Georgia  (keep H1)",
    title: "C-P Acquires Preferred Packaging (Georgia) | C-P Flexible", titleChars: 56,
    meta: "C-P Flexible Packaging acquires Preferred Packaging in Georgia—expanding refrigerated & frozen meal packaging with thermoformed solutions.", metaChars: 137,
    slug: "c-p-flexible-packaging-acquires-preferred-packaging-in-georgia",
    images: [],
    links: ["/locations/preferred-packaging/", "/about-us/our-companies", "/products/thermoformed-food-trays/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~87 chars) — replace with recommended.", "Acquisition PR — corporate growth story; link to the Preferred Packaging location + Our Companies."],
  },
  {
    section: "News",
    name: "C-P Acquires Fruth & Cleanroom Film and Bag",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-acquires-fruth-custom-packaging-inc-and-cleanroom-film-and-bag-inc",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-acquires-fruth-custom-packaging-inc-and-cleanroom-film-and-bag-inc",
    published: "2021-08-24", words: "~650", audited: "2026-08-19",
    h1: "C-P Flexible Packaging Acquires Fruth Custom Packaging, Inc. and Cleanroom Film and Bag, Inc.  (keep H1)",
    title: "C-P Acquires Fruth & Cleanroom Film and Bag | C-P Flexible", titleChars: 57,
    meta: "C-P Flexible Packaging acquires Fruth Custom Packaging & Cleanroom Film and Bag—expanding West Coast medical, pharma & electronics packaging.", metaChars: 140,
    slug: "c-p-flexible-packaging-acquires-fruth-custom-packaging-inc-and-cleanroom-film-and-bag-inc",
    images: [],
    links: ["/locations/placentia-california/", "/about-us/our-companies", "/markets/medical-electronics-cleanroom-packaging/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~116 chars) — replace with recommended.", "Mentions Cleanroom (out of core scope) but this is a legit acquisition story — fine to keep and optimize."],
  },
  {
    section: "News",
    name: "Fruth Expands Autoclave & Biohazard Bag Capacity",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-expands-medical-packaging-capabilities-through-fruth-division-to-meet-rising-demand-for-biohazard-autoclave-bags",
    prodUrl: "https://gcpflexpack.com/news/c-p-expands-medical-packaging-capabilities-through-fruth-division-to-meet-rising-demand-for-biohazard-autoclave-bags",
    published: "2025-05-05", words: "~400", audited: "2026-08-19",
    h1: "C-P Expands Medical Packaging Capabilities Through Fruth Division to Meet Rising Demand for Biohazard & Autoclave Bags  (keep H1)",
    title: "Fruth Expands Autoclave & Biohazard Bag Capacity | C-P", titleChars: 54,
    meta: "C-P's Fruth division expands U.S.-made autoclave & biohazard bag capacity—medical-grade packaging built for stringent sterilization standards.", metaChars: 142,
    slug: "c-p-expands-medical-packaging-capabilities-through-fruth-division-to-meet-rising-demand-for-biohazard-autoclave-bags",
    images: [],
    links: ["/products/bags/autoclave-bags", "/locations/placentia-california/", "/markets/medical-electronics-cleanroom-packaging/"],
    notes: ["Current title tag ('Autoclave Bags | News | C-P Flexible Packaging') is already good — recommended is an optional alternative.", "Strong product tie-in: link to the Autoclave Bags product page + Fruth location."],
  },
  {
    section: "News",
    name: "C-P Commits to 50% Solar-Powered U.S. Plants by 2025",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-announces-commitment-to-convert-50-of-companys-u-s-plants-to-solar-power-by-2025",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-announces-commitment-to-convert-50-of-companys-u-s-plants-to-solar-power-by-2025",
    published: "2022-09-28", words: "~310", audited: "2026-08-19",
    h1: "C-P Flexible Packaging Announces Commitment to Convert 50% of Company's U.S. Plants to Solar Power by 2025  (keep H1)",
    title: "C-P Commits to 50% Solar-Powered U.S. Plants by 2025 | C-P", titleChars: 57,
    meta: "C-P Flexible Packaging commits to converting 50% of its U.S. plants to solar power by 2025—with installations already live in California & Minnesota.", metaChars: 150,
    slug: "c-p-flexible-packaging-announces-commitment-to-convert-50-of-companys-u-s-plants-to-solar-power-by-2025",
    images: [],
    links: ["/sustainable-packaging/solar-powered-manufacturing", "/sustainable-packaging/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~150 chars) — replace with recommended.", "Sustainability PR — link to the Solar-Powered Manufacturing page."],
  },
  {
    section: "News",
    name: "Virtual Press Check Capabilities",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/introducing-virtual-press-check-capabilities-for-customers-in-critical-infrastructure-industries",
    prodUrl: "https://gcpflexpack.com/news/introducing-virtual-press-check-capabilities-for-customers-in-critical-infrastructure-industries",
    published: "2020-03-23", words: "~250", audited: "2026-08-19",
    h1: "Introducing Virtual Press Check Capabilities for Customers in Critical Infrastructure Industries  (keep H1)",
    title: "Virtual Press Check Capabilities | C-P Flexible Packaging", titleChars: 56,
    meta: "C-P Flexible Packaging launches virtual press-check capabilities—approve packaging graphics remotely via video, built for critical-infrastructure customers.", metaChars: 155,
    slug: "introducing-virtual-press-check-capabilities-for-customers-in-critical-infrastructure-industries",
    images: [
      ["auto-registration.jpg", "HD extended gamut flexographic printing", "Automatic registration on an HD flexographic press at C-P Flexible Packaging", "75"],
    ],
    links: ["/capabilities/high-definition-flexographic-printing/", "/capabilities/flexible-packaging-prepress-services/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~119 chars) — replace with recommended.", "COVID-era (2020) — dated, low evergreen value; optimize lightly.", "auto-registration.jpg — convert to WebP."],
  },
  {
    section: "News",
    name: "C-P Acquires Genpak Flexible",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-announces-acquisition-of-genpak-flexible",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-announces-acquisition-of-genpak-flexible",
    published: "2020-07-31", words: "~520", audited: "2026-08-26",
    h1: "C-P Flexible Packaging Announces Acquisition of Genpak Flexible  (keep H1)",
    title: "C-P Acquires Genpak Flexible | C-P Flexible Packaging", titleChars: 52,
    meta: "C-P Flexible Packaging acquires Genpak Flexible from The Jim Pattison Group—expanding to 6 North American sites & sustainable packaging.", metaChars: 134,
    slug: "c-p-flexible-packaging-announces-acquisition-of-genpak-flexible",
    images: [
      ["Genpak-logo-300x98.webp", "Genpak Flexible logo", "Genpak Flexible company logo", "28"],
    ],
    links: ["/about-us/our-companies", "/sustainable-packaging/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~87 chars) — replace with recommended.", "Acquisition PR — link to Our Companies."],
  },
  {
    section: "News",
    name: "C-P Launches IntelliGraphix Systems",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-announces-intelligraphix-systems",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-announces-intelligraphix-systems",
    published: "2024-01-23", words: "~450", audited: "2026-08-26",
    h1: "C-P Flexible Packaging Announces Intelligraphix Systems  (keep H1)",
    title: "C-P Launches IntelliGraphix Systems (Prepress) | C-P Flexible", titleChars: 60,
    meta: "C-P Flexible Packaging launches IntelliGraphix Systems—a prepress, platemaking & color-management division for flexographic printers.", metaChars: 132,
    slug: "c-p-flexible-packaging-announces-intelligraphix-systems",
    images: [
      ["intelligraphix-300x200.webp", "Intelligraphix Systems", "IntelliGraphix Systems division logo", "37"],
      ["flexo-graphics-prepress.jpg", "Intelligraphix Systems, New Prepress Division of C-P Flexible Packaging", "IntelliGraphix prepress and platemaking for flexographic printing", "64"],
    ],
    links: ["/capabilities/flexible-packaging-prepress-services/", "/capabilities/high-definition-flexographic-printing/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~80 chars) — replace with recommended.", "Division launch — link to prepress + HD flexo capability pages.", "flexo-graphics-prepress.jpg — convert to WebP."],
  },
  {
    section: "News",
    name: "C-P Acquires Bass Flexible Packaging",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-announces-acquisition-of-bass-flexible-packaging-inc",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-announces-acquisition-of-bass-flexible-packaging-inc",
    published: "2022-03-04", words: "~420", audited: "2026-08-26",
    h1: "C-P Flexible Packaging Announces Acquisition of Bass Flexible Packaging, Inc.  (keep H1)",
    title: "C-P Acquires Bass Flexible Packaging | C-P Flexible", titleChars: 50,
    meta: "C-P Flexible Packaging acquires Bass Flexible Packaging—adding agile, short-lead-time capabilities for confectionery and health & beauty.", metaChars: 138,
    slug: "c-p-flexible-packaging-announces-acquisition-of-bass-flexible-packaging-inc",
    images: [],
    links: ["/about-us/our-companies", "/markets/confectionery", "/markets/health-wellness-packaging/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~101 chars) — replace with recommended.", "Acquisition PR — link to Our Companies + confectionery/health markets."],
  },
  {
    section: "News",
    name: "C-P Acquires Prestige-Pak (Wisconsin)",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-announces-acquisition-of-prestige-pak-inc",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-announces-acquisition-of-prestige-pak-inc",
    published: "2021-08-04", words: "~425", audited: "2026-08-26",
    h1: "C-P Flexible Packaging Announces Acquisition of Prestige-Pak, Inc.  (keep H1)",
    title: "C-P Acquires Prestige-Pak (Wisconsin) | C-P Flexible", titleChars: 51,
    meta: "C-P Flexible Packaging acquires Wisconsin's Prestige-Pak—a 53-year family converter of printed films for snacks, confectionery & baked goods.", metaChars: 140,
    slug: "c-p-flexible-packaging-announces-acquisition-of-prestige-pak-inc",
    images: [
      ["prestige-pak-768x576.webp", "(none)", "Prestige-Pak flexible packaging facility in Wisconsin", "53"],
    ],
    links: ["/about-us/our-companies", "/markets/snack-products/", "/markets/confectionery"],
    notes: ["Title tag = full H1 + ' - C-P...' (~90 chars) — replace with recommended.", "Acquisition PR — link to Our Companies + snack/confectionery markets."],
  },
  {
    section: "News",
    name: "GreenStream Pulp Trays (Preferred Packaging)",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/preferred-packaging-a-division-of-c-p-flexible-packaging-launches-new-sustainable-greenstream-pulp-trays",
    prodUrl: "https://gcpflexpack.com/news/preferred-packaging-a-division-of-c-p-flexible-packaging-launches-new-sustainable-greenstream-pulp-trays",
    published: "2022-07-07", words: "~550", audited: "2026-08-26",
    h1: "Preferred Packaging, a division of C-P Flexible Packaging, Launches New Sustainable GreenStream™ Pulp Trays  (keep H1)",
    title: "GreenStream Pulp Trays from Preferred Packaging | C-P Flexible", titleChars: 61,
    meta: "Preferred Packaging launches GreenStream compostable plant-fiber pulp trays—an eco alternative to plastic for school-meal & food packaging.", metaChars: 138,
    slug: "preferred-packaging-a-division-of-c-p-flexible-packaging-launches-new-sustainable-greenstream-pulp-trays",
    images: [
      ["pulp-food-trays.webp", "GreenStream Sustainable Natural Plant-based Pulp Trays for Food Packaging", "GreenStream plant-based pulp food trays from Preferred Packaging", "63"],
    ],
    links: ["/sustainable-packaging/greenstream-products/", "/products/thermoformed-food-trays/"],
    notes: ["Title tag = full H1 + ' - C-P...' (~130 chars) — replace with recommended.", "⚠ NEAR-DUPLICATE of the 2025 'GreenStream Fiber Trays' post — both are GreenStream sustainable trays from Preferred. Differentiate (pulp 2022 vs bagasse-fiber 2025) + cross-link, or consolidate."],
  },
  {
    section: "News",
    name: "GreenStream Compostable Fiber Trays",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/preferred-packaging-launches-greenstream-fiber-trays-a-new-standard-in-sustainable-meal-packaging",
    prodUrl: "https://gcpflexpack.com/news/preferred-packaging-launches-greenstream-fiber-trays-a-new-standard-in-sustainable-meal-packaging",
    published: "2025-07-17", words: "~220", audited: "2026-08-26",
    h1: "GreenStream™ Compostable Fiber Trays Launched by Preferred Packaging  (good — keep, drop the trailing '| C-P Flexible Packaging' from the H1)",
    title: "GreenStream Compostable Fiber Trays | C-P Flexible Packaging", titleChars: 59,
    meta: "Preferred Packaging launches GreenStream bagasse fiber trays—compostable, curbside-recyclable, PFAS-free & compatible with sustainable lidding.", metaChars: 143,
    slug: "preferred-packaging-launches-greenstream-fiber-trays-a-new-standard-in-sustainable-meal-packaging",
    images: [
      ["poly-bag-dept-low-res.webp", "GreenStream Fiber Tray Manufactured by Preferred Packaging, A Division of C-P Flexible Packaging", "GreenStream compostable bagasse fiber trays by Preferred Packaging", "65"],
    ],
    links: ["/sustainable-packaging/greenstream-products/", "/products/thermoformed-food-trays/"],
    notes: ["⚠ WRONG IMAGE: uses poly-bag-dept-low-res.webp (a poly-bag department photo — also used on the Poly Bag news). Swap for an actual fiber-tray image, then apply the recommended alt.", "⚠ NEAR-DUPLICATE of the 2022 'GreenStream Pulp Trays' post — differentiate/cross-link or consolidate.", "Light article (~220 words) — consider expanding."],
  },
  {
    section: "News",
    name: "Hand Sanitizer Packaging in Record Time",
    url: "https://gcpflexpack-24024882.hs-sites.com/news/c-p-flexible-packaging-teams-up-with-hr-pharmaceuticals-to-donate-hand-sanitizer-in-record-time",
    prodUrl: "https://gcpflexpack.com/news/c-p-flexible-packaging-teams-up-with-hr-pharmaceuticals-to-donate-hand-sanitizer-in-record-time",
    published: "2020-04-06", words: "~1,100", audited: "2026-08-26",
    h1: "C-P Flexible Packaging and HR Pharmaceuticals Donate Hand Sanitizer in Record Time  (keep H1)",
    title: "Hand Sanitizer Packaging Supplied in Record Time | C-P Flexible", titleChars: 62,
    meta: "How C-P Flexible Packaging & HR Pharmaceuticals produced hand-sanitizer packets in record time for frontline workers during COVID-19.", metaChars: 132,
    slug: "c-p-flexible-packaging-teams-up-with-hr-pharmaceuticals-to-donate-hand-sanitizer-in-record-time",
    images: [
      ["Hand-Sanitizer-Sachets.jpg", "Hand sanitizer packaging supplied by C-P Flexible Packaging", "Hand-sanitizer sachets supplied by C-P Flexible Packaging", "57"],
    ],
    links: ["/markets/health-wellness-packaging/", "/capabilities/finishing-slitting-flexible-packaging/"],
    notes: ["Current title tag ('Hand sanitizer packaging supplied in record time') is decent but has no brand — recommended adds it.", "COVID-era but substantial (~1,100 words) — real content, not just fluff. Image .jpg — convert to WebP."],
  },
];

// ---------------------------------------------------------------------------
function label(text) { return new Paragraph({ spacing: { before: 160, after: 40 }, children: [new TextRun({ text, bold: true, color: BRAND })] }); }
function mono(text) { return new Paragraph({ spacing: { after: 60 }, shading: { fill: "F4F6F4", type: ShadingType.CLEAR }, children: [new TextRun({ text, font: "Consolas", size: 19 })] }); }
function codeBlock(text) {
  return text.split("\n").map(line => new Paragraph({ spacing: { after: 0 }, shading: { fill: "F4F6F4", type: ShadingType.CLEAR },
    children: [new TextRun({ text: line || " ", font: "Consolas", size: 17 })] }));
}
function schemaFor(a) {
  return `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "${a.name}",
  "description": "${a.meta.replace(/"/g, '\\"')}",
  "datePublished": "${a.published}",
  "url": "${a.prodUrl}",
  "mainEntityOfPage": { "@type": "WebPage", "@id": "${a.prodUrl}" },
  "author": { "@type": "Organization", "name": "C-P Flexible Packaging" },
  "publisher": {
    "@type": "Organization",
    "name": "C-P Flexible Packaging",
    "logo": { "@type": "ImageObject", "url": "https://gcpflexpack.com/hubfs/logo.png" }
  }
}
</script>`;
}

const today = new Date().toISOString().slice(0, 10);
const body = [];

// Cover
body.push(new Paragraph({ spacing: { before: 1200, after: 0 }, alignment: AlignmentType.CENTER,
  children: [new TextRun({ text: "C-P Flexible Packaging", bold: true, size: 56, color: BRAND })] }));
body.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 120 },
  children: [new TextRun({ text: "Blog SEO Audit — Running Log", size: 32 })] }));
body.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 40 },
  children: [new TextRun({ text: "Learning-Center & News articles (HubSpot Blog tool)", italics: true, size: 22, color: "666666" })] }));
body.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 40 },
  children: [new TextRun({ text: `Last updated: ${today}   •   Articles audited: ${BLOG.length}`, size: 20, color: "666666" })] }));

// Intro guidance
body.push(new Paragraph({ heading: HeadingLevel.HEADING_2, pageBreakBefore: true, children: [new TextRun("How to Use This Log")] }));
[
  "Schema type for blog articles is BlogPosting. IMPORTANT: HubSpot blog templates often auto-output BlogPosting/Article JSON-LD — confirm before adding, and verify fields rather than duplicate.",
  "Cold-Seal topic cluster: four cold-seal articles + the /products/cold-seal-packaging page + the cold-seal guide should all interlink. Hub = 'How to Choose a Cold-Seal Supplier'.",
  "Blog titles: keep ≤70 chars and end with '| C-P Flexible Packaging' where space allows.",
].forEach(t => body.push(new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 40 }, children: [new TextRun(t)] })));

// Per-article sections
BLOG.forEach(a => {
  body.push(new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true, children: [new TextRun(`${a.section}: ${a.name}`)] }));
  body.push(new Paragraph({ spacing: { after: 20 }, children: [
    new TextRun({ text: "Live URL: ", bold: true }),
    new ExternalHyperlink({ link: a.url, children: [new TextRun({ text: a.url, style: "Hyperlink", size: 18 })] }) ]}));
  body.push(new Paragraph({ spacing: { after: 40 }, children: [
    new TextRun({ text: "Published: ", bold: true }), new TextRun({ text: a.published }),
    new TextRun({ text: "      Word count: ", bold: true }), new TextRun({ text: a.words }),
    new TextRun({ text: "      Audited: ", bold: true }), new TextRun({ text: a.audited }) ]}));

  body.push(label("Current H1"));
  body.push(new Paragraph({ spacing: { after: 40 }, children: [new TextRun({ text: a.h1 })] }));
  body.push(label(`Recommended Page Title (${a.titleChars} chars)`));
  body.push(mono(a.title));
  body.push(label(`Recommended Meta Description (${a.metaChars} chars)`));
  body.push(mono(a.meta));
  body.push(label("Recommended Structured Data (BlogPosting schema)"));
  codeBlock(schemaFor(a)).forEach(p => body.push(p));
  body.push(label("Image Alt Text"));
  if (a.images && a.images.length) {
    a.images.forEach(im => body.push(new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 30 }, children: [
      new TextRun({ text: im[0] + " — ", font: "Consolas", size: 17, bold: true }),
      new TextRun({ text: "current: ", italics: true, size: 18 }),
      new TextRun({ text: (im[1] || "(none)") + "   →   ", size: 18, color: "B00000" }),
      new TextRun({ text: im[2], bold: true, color: BRAND, size: 18 }),
      new TextRun({ text: "  (" + im[3] + " chars)", size: 16, color: "666666" }),
    ] })));
  } else {
    body.push(new Paragraph({ spacing: { after: 30 }, children: [new TextRun({ text: "No content images (text-only article).", italics: true, color: "666666" })] }));
  }

  body.push(label("Internal Links to Add"));
  a.links.forEach(l => body.push(new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 20 }, children: [new TextRun({ text: l, font: "Consolas", size: 18 })] })));
  if (a.notes && a.notes.length) {
    body.push(label("Notes"));
    a.notes.forEach(n => body.push(new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 30 }, children: [new TextRun(n)] })));
  }
});

const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 30, bold: true, font: "Arial", color: BRAND },
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
      children: [new TextRun({ text: "C-P Flexible Packaging — Blog SEO Audit", size: 16, color: "999999" })] })] }) },
    footers: { default: new Footer({ children: [new Paragraph({ alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: "Prepared by Start Advertising   ·   Page ", size: 16, color: "999999" }), new TextRun({ children: [PageNumber.CURRENT], size: 16, color: "999999" })] })] }) },
    children: body,
  }],
});

const out = process.argv[2] || "C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Garlock/Garlock-Blog-SEO-Audit-Running-Log.docx";
Packer.toBuffer(doc).then(b => { fs.writeFileSync(out, b); console.log("WROTE " + out + " (" + BLOG.length + " article[s])"); });
