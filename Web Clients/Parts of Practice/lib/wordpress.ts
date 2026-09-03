const WP_API = 'https://partsofpractice.com/wp-json/wp/v2'

// ─── Blog Posts ───────────────────────────────────────────────────────────────

export interface WPPost {
  id: number
  slug: string
  date: string
  title: { rendered: string }
  excerpt: { rendered: string }
  content: { rendered: string }
  categories: number[]
  _embedded?: {
    'wp:featuredmedia'?: Array<{ source_url: string; alt_text: string }>
    'wp:term'?: Array<Array<{ id: number; name: string; slug: string }>>
  }
}

export interface Post {
  id: number
  slug: string
  date: string
  title: string
  excerpt: string
  content: string
  featuredImage: string | null
  featuredImageAlt: string
  categories: string[]
}

function formatPost(post: WPPost): Post {
  const media = post._embedded?.['wp:featuredmedia']?.[0]
  const terms = post._embedded?.['wp:term']?.[0] ?? []
  return {
    id: post.id,
    slug: post.slug,
    date: post.date,
    title: post.title.rendered.replace(/&#8217;/g, "'").replace(/&#8211;/g, '–').replace(/&amp;/g, '&'),
    excerpt: post.excerpt.rendered.replace(/<[^>]+>/g, '').replace(/&#8217;/g, "'").replace(/&#8211;/g, '–').trim(),
    content: post.content.rendered,
    featuredImage: media?.source_url ?? null,
    featuredImageAlt: media?.alt_text ?? '',
    categories: terms.map(t => t.name),
  }
}

export async function getPosts(perPage = 12): Promise<Post[]> {
  try {
    const res = await fetch(`${WP_API}/posts?per_page=${perPage}&_embed`, {
      next: { revalidate: 3600 },
    })
    if (!res.ok) return []
    const posts: WPPost[] = await res.json()
    return posts.map(formatPost)
  } catch {
    return []
  }
}

export async function getPost(slug: string): Promise<Post | null> {
  try {
    const res = await fetch(`${WP_API}/posts?slug=${slug}&_embed`, {
      next: { revalidate: 3600 },
    })
    if (!res.ok) return null
    const posts: WPPost[] = await res.json()
    if (!posts.length) return null
    return formatPost(posts[0])
  } catch {
    return null
  }
}

export async function getPostSlugs(): Promise<string[]> {
  try {
    const res = await fetch(`${WP_API}/posts?per_page=100&_fields=slug`, {
      next: { revalidate: 3600 },
    })
    if (!res.ok) return []
    const posts: { slug: string }[] = await res.json()
    return posts.map(p => p.slug)
  } catch {
    return []
  }
}

// ─── Page Content via ACF ─────────────────────────────────────────────────────

export interface AboutPageContent {
  // Hero
  hero_heading: string
  hero_subheading: string
  // Stephanie
  stephanie_bio_p1: string
  stephanie_bio_p2: string
  stephanie_bio_p3: string
  stephanie_photo_url: string
  stephanie_photo_alt: string
  // Richard
  richard_bio_p1: string
  richard_bio_p2: string
  richard_bio_p3: string
  richard_photo_url: string
  richard_photo_alt: string
  // Together photo
  together_photo_url: string
  together_photo_alt: string
  // Story
  story_p1: string
  story_p2: string
  story_p3: string
  story_p4: string
  story_p5: string
  // CTA
  cta_heading: string
  cta_subtext: string
}

// Fallback content — used when WordPress ACF fields are not yet configured
export const aboutPageFallback: AboutPageContent = {
  hero_heading: "A father, a daughter, and a shared belief",
  hero_subheading: "Parts of Practice was born from a simple frustration: the marketing advice available to therapists was almost entirely at odds with the therapeutic values that make good therapy possible. So Stephanie and her dad Richard decided to build something different.",
  stephanie_bio_p1: "Stephanie is a licensed therapist and IFS practitioner who built her own private practice while completing IFS Level 1 training. Along the way, she experienced firsthand how complex running a practice could be — parts wanting structured schedules and income, other parts hesitant about visibility, raising rates, being seen.",
  stephanie_bio_p2: "Parts work became her solution. Instead of forcing herself to follow generic business advice, she learned to map the voices inside her own system and let them collaborate instead of compete. The practice she built from that process felt like her — and she wanted to help other therapists build theirs the same way.",
  stephanie_bio_p3: "Stephanie leads our clinical copywriting, brand voice development, and IFS-informed business coaching. She helps therapists find language that sounds like their whole Self — not a polished performance of it.",
  stephanie_photo_url: "/stephanie-brashear-lpcc-s-lmhc-parts-of-practice-founder.png",
  stephanie_photo_alt: "Stephanie Brashear, LPCC-S, LMHC — Co-Founder of Parts of Practice",
  richard_bio_p1: "Richard brings 30+ years of digital strategy, design, and marketing leadership to Parts of Practice — including time as a Chief Marketing Officer for startups and growing brands. He knows how to turn complex ideas into functional, beautiful businesses.",
  richard_bio_p2: "When his daughter Stephanie asked him to help build something that supported therapists the right way, he said yes. His role: make the tech, SEO, and digital infrastructure feel a whole lot less stressful — so therapists can focus on their clients instead of their websites.",
  richard_bio_p3: "Richard handles all web development, technical SEO, site performance, and digital strategy. His approach to marketing mirrors Stephanie's clinical approach: thoughtful, never manipulative, and always in service of the real human on the other side of the screen.",
  richard_photo_url: "/richard-brashear-parts-of-practice-digital-strategist-seo.jpg",
  richard_photo_alt: "Richard Brashear — Co-Founder of Parts of Practice",
  together_photo_url: "/stephanie-and-richard-brashear-parts-of-practice-ifs-therapy-website-design.png",
  together_photo_alt: "Stephanie and Richard Brashear, co-founders of Parts of Practice",
  story_p1: "When Stephanie was building her private practice, she kept running into the same wall: every piece of marketing advice she found felt wrong. Hustle. Visibility. Personal brand. None of it fit the way she worked, the way she thought about her clients, or the values she held as a therapist.",
  story_p2: "She had parts that got activated around all of it — protectors who worried it would feel pushy, managers who insisted everything had to look “professional enough,” exiles who quietly wondered if anyone would actually want to work with her.",
  story_p3: "So she called her dad.",
  story_p4: "Richard had spent decades helping brands find their voice and build their digital presence. Together, they started asking a different question: what would it look like to build a therapy practice online the way a good therapist actually works — with curiosity, care, and without forcing anything?",
  story_p5: "Parts of Practice is the answer. A small, intentional studio where clinical wisdom and technical expertise work side by side — helping IFS-informed therapists build practices that feel like their whole Self.",
  cta_heading: "Ready to work together?",
  cta_subtext: "Start with a free 30-minute conversation. No pitch, no pressure — just a father-daughter team who genuinely care about your practice, listening.",
}

export async function getAboutPageContent(): Promise<AboutPageContent> {
  try {
    const res = await fetch(`${WP_API}/pages?slug=about-content&acf_format=standard&_fields=acf`, {
      next: { revalidate: 3600 },
    })
    if (!res.ok) return aboutPageFallback
    const pages: Array<{ acf?: Partial<AboutPageContent> }> = await res.json()
    if (!pages.length || !pages[0].acf) return aboutPageFallback
    // Merge WP fields over fallback so missing fields still have defaults
    return { ...aboutPageFallback, ...pages[0].acf }
  } catch {
    return aboutPageFallback
  }
}

// ─── Utilities ────────────────────────────────────────────────────────────────

export function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
