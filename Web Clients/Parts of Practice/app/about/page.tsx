import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Heart, Lightbulb, Shield } from 'lucide-react'
import { getAboutPageContent } from '@/lib/wordpress'

export const revalidate = 3600

export const metadata: Metadata = {
  title: 'About Stephanie & Richard Brashear | IFS Therapist Website Designers',
  description: 'Meet Stephanie Brashear (LPCC-S, LMHC) and her father Richard — a licensed IFS therapist and 30-year digital strategist who built Parts of Practice to help therapists grow with integrity.',
  alternates: { canonical: 'https://partsofpractice.com/about' },
  openGraph: {
    title: 'About Stephanie & Richard Brashear | Parts of Practice',
    description: 'A licensed IFS therapist and a 30-year digital strategist. Meet the father-daughter team behind Parts of Practice.',
    url: 'https://partsofpractice.com/about',
  },
}

const values = [
  {
    icon: Heart,
    title: 'Parts-aware from the start',
    body: 'We know that visibility, marketing, and putting yourself online can activate parts. We hold space for that — we never push, pitch, or pressure.',
  },
  {
    icon: Lightbulb,
    title: 'Ethical over algorithmic',
    body: "Every word we write and every strategy we build is grounded in what's true about you and helpful to your clients — not what performs best.",
  },
  {
    icon: Shield,
    title: 'Built for the long term',
    body: "We're not a one-and-done agency. We build relationships with the practices we support, showing up consistently as your practice grows.",
  },
]

export default async function AboutPage() {
  const content = await getAboutPageContent()

  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="relative rounded-3xl overflow-hidden shadow-xl mb-14 aspect-video max-w-4xl mx-auto">
            <Image
              src={content.together_photo_url}
              alt={content.together_photo_alt}
              fill
              className="object-cover object-top"
              priority
            />
          </div>
          <div className="text-center mb-16 max-w-3xl mx-auto">
            <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">About Us</p>
            <h1 className="font-serif text-4xl md:text-5xl text-sage-700 font-medium leading-tight mb-6">
              {content.hero_heading.includes('shared belief') ? (
                <>
                  A father, a daughter, and{' '}
                  <em className="italic" style={{ color: '#a75d90' }}>a shared belief</em>
                </>
              ) : (
                content.hero_heading
              )}
            </h1>
            <p className="text-stone-500 text-lg leading-relaxed">
              {content.hero_subheading}
            </p>
          </div>
        </div>
      </section>

      {/* Stephanie */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div className="relative">
              <div className="absolute -top-6 -left-6 w-64 h-64 rounded-full border border-bark-200/60" aria-hidden="true" />
              <div className="absolute -bottom-6 -right-6 w-48 h-48 rounded-full border border-mauve-200/60" aria-hidden="true" />
              <div className="relative rounded-3xl overflow-hidden shadow-xl aspect-[4/5] max-w-sm mx-auto lg:mx-0">
                <Image
                  src={content.stephanie_photo_url}
                  alt={content.stephanie_photo_alt}
                  fill
                  className="object-cover object-top"
                />
              </div>
            </div>
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Co-Founder &middot; The Side That Guides Your Practice</p>
              <h2 className="font-serif text-4xl text-sage-700 font-medium mb-2">Stephanie Brashear</h2>
              <p className="text-mauve-500 font-medium mb-6">LPCC-S, LMHC &middot; IFS Practitioner</p>
              <div className="space-y-4 text-stone-500 leading-relaxed">
                <p>{content.stephanie_bio_p1}</p>
                <p>{content.stephanie_bio_p2}</p>
                <p>{content.stephanie_bio_p3}</p>
              </div>
              <div className="mt-8 flex flex-wrap gap-3">
                {['LPCC-S, LMHC', 'IFS Practitioner', 'Clinical Copywriter', 'Practice Coach'].map(tag => (
                  <span key={tag} className="bg-white border border-cream-300 text-sage-600 text-xs font-semibold px-4 py-2 rounded-full">
                    {tag}
                  </span>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Richard */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div className="order-2 lg:order-1">
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Co-Founder &middot; The Side That Powers Your Practice</p>
              <h2 className="font-serif text-4xl text-sage-700 font-medium mb-2">Richard Brashear</h2>
              <p className="text-mauve-500 font-medium mb-6">Digital Strategist &amp; Technical Director</p>
              <div className="space-y-4 text-stone-500 leading-relaxed">
                <p>{content.richard_bio_p1}</p>
                <p>{content.richard_bio_p2}</p>
                <p>{content.richard_bio_p3}</p>
              </div>
              <div className="mt-8 flex flex-wrap gap-3">
                {['30+ Years Experience', 'Former CMO', 'Web Development', 'Technical SEO'].map(tag => (
                  <span key={tag} className="bg-cream-200 border border-cream-300 text-sage-600 text-xs font-semibold px-4 py-2 rounded-full">
                    {tag}
                  </span>
                ))}
              </div>
            </div>
            <div className="relative order-1 lg:order-2">
              <div className="absolute -top-6 -right-6 w-64 h-64 rounded-full border border-sage-200/60" aria-hidden="true" />
              <div className="absolute -bottom-6 -left-6 w-48 h-48 rounded-full border border-bark-200/60" aria-hidden="true" />
              <div className="relative rounded-3xl overflow-hidden shadow-xl aspect-[4/5] max-w-sm mx-auto lg:ml-auto lg:mr-0">
                <Image
                  src={content.richard_photo_url}
                  alt={content.richard_photo_alt}
                  fill
                  className="object-cover object-top"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Our Story */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-4xl mx-auto px-6">
          <div className="text-center mb-10">
            <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">Our Story</p>
            <h2 className="font-serif text-4xl md:text-5xl text-sage-700 font-medium">
              Why a therapist called her dad.
            </h2>
          </div>
          <div className="bg-white rounded-3xl border border-cream-300 p-10 md:p-14 space-y-5 text-stone-500 text-lg leading-relaxed">
            <p>{content.story_p1}</p>
            <p>{content.story_p2}</p>
            <p>{content.story_p3}</p>
            <p>{content.story_p4}</p>
            <p>{content.story_p5}</p>
          </div>
        </div>
      </section>

      {/* Values */}
      <section className="bg-sage-700 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-14">
            <p className="text-bark-300 text-sm font-semibold uppercase tracking-widest mb-3">What We Believe</p>
            <h2 className="font-serif text-4xl md:text-5xl text-cream-100 font-medium">
              The values behind the work.
            </h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {values.map((v) => (
              <div key={v.title} className="bg-sage-600/50 rounded-2xl p-8 border border-sage-500/40">
                <div className="w-12 h-12 rounded-full bg-bark-500/20 flex items-center justify-center mb-5">
                  <v.icon size={22} className="text-bark-300" />
                </div>
                <h3 className="font-serif text-xl text-cream-100 font-medium mb-3">{v.title}</h3>
                <p className="text-cream-300 text-sm leading-relaxed">{v.body}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section-padding bg-cream-100">
        <div className="max-w-2xl mx-auto px-6 text-center">
          <h2 className="font-serif text-4xl text-sage-700 font-medium mb-5">
            {content.cta_heading}
          </h2>
          <p className="text-stone-500 text-lg leading-relaxed mb-8">
            {content.cta_subtext}
          </p>
          <Link
            href="/contact"
            className="inline-flex items-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group"
          >
            Book a Free Consultation
            <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
          </Link>
        </div>
      </section>

    </div>
  )
}
