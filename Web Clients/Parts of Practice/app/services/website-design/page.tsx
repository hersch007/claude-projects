import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Check } from 'lucide-react'

export const metadata: Metadata = {
  title: 'Custom Website Design for Therapists | IFS & Private Practice Sites',
  description: 'Custom-built websites for IFS-informed therapists in private practice. No templates — a site designed around your voice, values, and ideal clients. Starting at $55/month.',
  alternates: { canonical: 'https://partsofpractice.com/services/website-design' },
  openGraph: {
    title: 'Custom Website Design for Therapists | Parts of Practice',
    description: 'Custom therapy websites starting at $55/month. Built for IFS, trauma, and private practice clinicians.',
    url: 'https://partsofpractice.com/services/website-design',
  },
}

const plans = [
  {
    name: 'Root',
    price: '$95/mo',
    note: 'Year 1 Â· $55/mo after',
    description: 'A clean, professional foundation for a growing practice.',
    features: ['Custom website design', 'Mobile-responsive', 'SSL + secure hosting', 'Contact form', 'Blog-ready structure', 'Basic SEO setup', 'No setup fees'],
  },
  {
    name: 'Grow',
    price: '$149/mo',
    note: 'Year 1 Â· $99/mo after',
    description: 'For practices ready to grow their local visibility.',
    features: ['Everything in Root', 'SEO titles + meta tags', 'Image optimization', 'Local keyword research', 'Google Maps integration', 'Analytics setup', 'Monthly updates'],
    highlighted: true,
  },
  {
    name: 'Thrive',
    price: '$199/mo',
    note: 'Year 1 Â· $159/mo after',
    description: 'Full-service support for an established practice.',
    features: ['Everything in Grow', 'Advanced SEO strategy', 'Specialty keyword targeting', 'One blog post/month', 'Competitor insights', 'Monthly reporting'],
  },
]

const included = [
  'Fully custom design â€” no templates',
  'Built around your voice and values',
  'Mobile-responsive on all devices',
  'Fast load times + Core Web Vitals',
  'SSL certificate + secure hosting',
  'Contact forms + calendar integration',
  'Blog-ready from day one',
  'ADA accessibility standards',
]

export default function WebsiteDesignPage() {
  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">Website Design</p>
              <h1 className="font-serif text-5xl md:text-6xl text-sage-700 font-medium leading-tight mb-6">
                A website as unique as your practice.
              </h1>
              <p className="text-stone-500 text-lg leading-relaxed mb-8">
                Every therapist has a distinct voice, a specific specialty, and clients they&apos;re best suited to serve.
                Your website should reflect all of that â€” not look like every other therapist in your city.
                We build custom sites from scratch, designed around you.
              </p>
              <Link href="/contact" className="inline-flex items-center gap-2 bg-bark-500 text-white px-8 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
                Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
              </Link>
            </div>
            <div className="relative rounded-3xl overflow-hidden shadow-xl aspect-video lg:aspect-auto lg:h-[420px]">
              <Image src="/therapist-website-design-ifs-emdr-asheville-nc.png" alt="Custom therapy website design examples" fill className="object-cover" />
            </div>
          </div>
        </div>
      </section>

      {/* What's included */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">What&apos;s Included</p>
              <h2 className="font-serif text-4xl text-sage-700 font-medium mb-6">Every site we build includes:</h2>
              <ul className="space-y-3">
                {included.map(item => (
                  <li key={item} className="flex items-start gap-3 text-stone-500">
                    <div className="w-5 h-5 rounded-full bg-bark-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                      <Check size={12} className="text-bark-500" />
                    </div>
                    {item}
                  </li>
                ))}
              </ul>
            </div>
            <div className="relative rounded-3xl overflow-hidden shadow-lg aspect-video lg:aspect-auto lg:h-[380px]">
              <Image src="/how-to-get-found-on-google-therapist-seo.png" alt="Therapist working on website" fill className="object-cover" />
            </div>
          </div>
        </div>
      </section>

      {/* Pricing */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-14">
            <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Pricing</p>
            <h2 className="font-serif text-4xl text-sage-700 font-medium mb-4">Simple, transparent pricing.</h2>
            <p className="text-stone-500 max-w-xl mx-auto">No setup fees. No hidden charges. No rigid templates. Just a monthly rate that includes your site and ongoing support.</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {plans.map(plan => (
              <div key={plan.name} className={`rounded-3xl p-8 border flex flex-col ${plan.highlighted ? 'bg-sage-700 border-sage-600' : 'bg-white border-cream-300'}`}>
                <p className={`text-xs font-semibold uppercase tracking-widest mb-2 ${plan.highlighted ? 'text-bark-300' : 'text-bark-500'}`}>{plan.name}</p>
                <p className={`font-serif text-4xl font-medium mb-1 ${plan.highlighted ? 'text-cream-100' : 'text-sage-700'}`}>{plan.price}</p>
                <p className={`text-xs mb-4 ${plan.highlighted ? 'text-cream-400' : 'text-stone-400'}`}>{plan.note}</p>
                <p className={`text-sm leading-relaxed mb-6 ${plan.highlighted ? 'text-cream-300' : 'text-stone-500'}`}>{plan.description}</p>
                <ul className="space-y-2 flex-1 mb-8">
                  {plan.features.map(f => (
                    <li key={f} className={`flex items-start gap-2 text-sm ${plan.highlighted ? 'text-cream-200' : 'text-stone-500'}`}>
                      <Check size={14} className={`flex-shrink-0 mt-0.5 ${plan.highlighted ? 'text-bark-300' : 'text-bark-400'}`} />
                      {f}
                    </li>
                  ))}
                </ul>
                <Link href="/contact" className={`text-center py-3 rounded-full text-sm font-medium transition-colors ${plan.highlighted ? 'bg-bark-500 text-white hover:bg-bark-600' : 'bg-cream-200 text-sage-700 hover:bg-cream-300 border border-cream-300'}`}>
                  Get started
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-2xl mx-auto px-6 text-center">
          <h2 className="font-serif text-4xl text-sage-700 font-medium mb-5">Ready to build something that feels like you?</h2>
          <p className="text-stone-500 text-lg leading-relaxed mb-8">Start with a free 30-minute call. We&apos;ll listen first, then talk about what makes sense for your practice.</p>
          <Link href="/contact" className="inline-flex items-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
            Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
          </Link>
        </div>
      </section>

    </div>
  )
}

