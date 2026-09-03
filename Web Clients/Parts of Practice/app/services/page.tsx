import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Monitor, Search, PenLine, Heart } from 'lucide-react'

export const metadata: Metadata = {
  title: 'Services for Therapists | Website Design, SEO & Copywriting',
  description: 'Website design, SEO, copywriting, and IFS-informed business coaching for therapists in private practice. Ethical, parts-aware support built for IFS, EMDR, and trauma-informed clinicians.',
  alternates: { canonical: 'https://partsofpractice.com/services' },
  openGraph: {
    title: 'Services for Therapists | Parts of Practice',
    description: 'Website design, SEO, copywriting, and IFS business support for private practice therapists.',
    url: 'https://partsofpractice.com/services',
  },
}

const services = [
  {
    icon: Monitor,
    label: 'Website Design',
    title: 'A website that actually sounds like you.',
    description: 'Custom-built therapy websites that reflect your clinical voice, your values, and the clients you most want to reach. No templates, no cookie-cutter layouts â€” just a site that feels right.',
    features: ['Fully custom design', 'Mobile-responsive', 'Blog-ready structure', 'Contact forms + SSL', 'Basic copywriting included'],
    href: '/services/website-design',
    from: '$95/mo',
    image: '/therapist-website-design-ifs-emdr-asheville-nc.png',
  },
  {
    icon: Search,
    label: 'SEO for Therapists',
    title: 'Help the right clients find you.',
    description: 'Search engine optimization designed specifically for therapy practices â€” blending therapeutic language with proven search strategy so you show up when someone searches "IFS therapist near me."',
    features: ['Local keyword research', 'Google Business setup', 'Meta tags + image SEO', 'Monthly monitoring', 'No setup fees'],
    href: '/services/seo',
    from: '$55/mo',
    image: '/seo-for-therapists-private-practice-responsive-website.png',
  },
  {
    icon: PenLine,
    label: 'Copywriting',
    title: 'The right words make all the difference.',
    description: 'Your clients are already searching for you â€” the right words make sure they recognize you when they arrive. IFS-informed copy that translates your clinical expertise into language that resonates.',
    features: ['Homepage + About page', 'Specialty service pages', 'IFS-aligned voice', 'SEO keyword integration', 'Revision support'],
    href: '/services/copywriting',
    from: '$150/page',
    image: '/therapist-copywriting-services-woman-at-laptop.png',
  },
  {
    icon: Heart,
    label: 'IFS Business Support',
    title: 'Build your practice from the inside out.',
    description: 'Parts-informed coaching that addresses the internal barriers â€” around fees, visibility, boundaries, and growth â€” that generic business advice completely ignores.',
    features: ['Parts mapping sessions', 'Fee + boundary support', 'Visibility coaching', 'Free starter guide', 'CEU-eligible trainings'],
    href: '/services/ifs-business-support',
    from: 'Free guide to start',
    image: '/ifs-informed-business-support-therapy-practice.png',
  },
]

export default function ServicesPage() {
  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">What We Do</p>
          <h1 className="font-serif text-5xl md:text-6xl text-sage-700 font-medium leading-tight mb-6">
            Everything your practice needs â€”{' '}
            <em className="italic" style={{ color: '#a75d90' }}>nothing that doesn&apos;t fit.</em>
          </h1>
          <p className="text-stone-500 text-lg leading-relaxed max-w-2xl mx-auto">
            Four core services, each built specifically for IFS-informed therapists.
            Start with one or combine them â€” whatever makes sense for where you are right now.
          </p>
        </div>
      </section>

      {/* Services */}
      <section className="pb-24">
        <div className="max-w-6xl mx-auto px-6 space-y-8">
          {services.map((service, i) => (
            <div key={service.label} className="grid grid-cols-1 lg:grid-cols-2 rounded-3xl overflow-hidden border border-cream-300 shadow-sm">
              <div className={`relative h-64 lg:h-auto min-h-[320px] ${i % 2 !== 0 ? 'lg:order-2' : ''}`}>
                <Image src={service.image} alt={service.title} fill className="object-cover" />
              </div>
              <div className={`bg-white p-10 lg:p-12 flex flex-col justify-center ${i % 2 !== 0 ? 'lg:order-1' : ''}`}>
                <div className="flex items-center gap-3 mb-5">
                  <div className="w-10 h-10 rounded-full bg-cream-200 flex items-center justify-center">
                    <service.icon size={18} className="text-bark-500" />
                  </div>
                  <span className="text-bark-500 text-xs font-semibold uppercase tracking-widest">{service.label}</span>
                </div>
                <h2 className="font-serif text-3xl text-sage-700 font-medium mb-4">{service.title}</h2>
                <p className="text-stone-500 leading-relaxed mb-6">{service.description}</p>
                <ul className="space-y-2 mb-8">
                  {service.features.map(f => (
                    <li key={f} className="flex items-center gap-2 text-sm text-stone-500">
                      <span className="w-1.5 h-1.5 rounded-full bg-bark-400 flex-shrink-0" />
                      {f}
                    </li>
                  ))}
                </ul>
                <div className="flex items-center justify-between">
                  <span className="text-sage-600 font-semibold text-sm">Starting at {service.from}</span>
                  <Link href={service.href} className="inline-flex items-center gap-2 bg-bark-500 text-white px-6 py-3 rounded-full text-sm font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
                    Learn more <ArrowRight size={14} className="group-hover:translate-x-0.5 transition-transform" />
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* CTA */}
      <section className="bg-sage-700 section-padding">
        <div className="max-w-2xl mx-auto px-6 text-center">
          <h2 className="font-serif text-4xl text-cream-100 font-medium mb-5">Not sure where to start?</h2>
          <p className="text-cream-300 text-lg leading-relaxed mb-8">
            Book a free 30-minute call and we&apos;ll help you figure out what your practice actually needs right now. No pitch â€” just a real conversation.
          </p>
          <Link href="/contact" className="inline-flex items-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
            Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
          </Link>
        </div>
      </section>

    </div>
  )
}

