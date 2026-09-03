import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Check } from 'lucide-react'

export const metadata: Metadata = {
  title: 'SEO for Therapists | IFS, EMDR & Trauma Practice Search Optimization',
  description: 'Ethical SEO for IFS-informed therapists in private practice. Rank on Google for your specialty — IFS, EMDR, trauma, somatic therapy — without gaming the algorithm.',
  alternates: { canonical: 'https://partsofpractice.com/services/seo' },
  openGraph: {
    title: 'SEO for Therapists | Parts of Practice',
    description: 'Rank on Google for IFS, EMDR, and trauma therapy. Ethical SEO built for private practice clinicians.',
    url: 'https://partsofpractice.com/services/seo',
  },
}

const plans = [
  {
    name: 'Spark',
    price: '$55/mo',
    description: 'Local SEO essentials for a practice getting started.',
    features: ['Local keyword research', 'Optimized titles + meta tags', 'Heading structure', 'Image optimization', 'Monthly updates + monitoring'],
  },
  {
    name: 'Beacon',
    price: '$155/mo',
    description: 'For practices ready to grow their visibility and authority.',
    features: ['Everything in Spark', 'Specialty keyword strategy (IFS, EMDR, trauma)', 'One blog post or resource monthly', 'Backlink + citation building', 'Competitor analysis'],
    highlighted: true,
  },
  {
    name: 'Lighthouse',
    price: '$295/mo',
    description: 'Full-service SEO for an established or multi-specialty practice.',
    features: ['Everything in Beacon', 'Advanced content strategy', 'Pillar pages + blog series', 'Multi-location targeting', 'Quarterly audits + strategy sessions'],
  },
]

const whyItMatters = [
  { stat: '72%', detail: 'of people searching for a therapist use Google first' },
  { stat: '2â€“3 mo', detail: 'typical time to see initial SEO results' },
  { stat: '6â€“12 mo', detail: 'for stronger, compounding growth' },
  { stat: '$0', detail: 'setup fees â€” ever' },
]

export default function SEOPage() {
  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">SEO for Therapists</p>
              <h1 className="font-serif text-5xl md:text-6xl text-sage-700 font-medium leading-tight mb-6">
                Be found by the clients who need you most.
              </h1>
              <p className="text-stone-500 text-lg leading-relaxed mb-8">
                Most therapists have a website. Very few show up when someone in their city searches
                &ldquo;IFS therapist near me&rdquo; or &ldquo;trauma therapist in [city].&rdquo;
                We change that â€” ethically, thoughtfully, and without gaming the algorithm.
              </p>
              <Link href="/contact" className="inline-flex items-center gap-2 bg-bark-500 text-white px-8 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
                Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
              </Link>
            </div>
            <div className="relative rounded-3xl overflow-hidden shadow-xl aspect-video lg:aspect-auto lg:h-[400px]">
              <Image src="/seo-for-therapists-private-practice-responsive-website.png" alt="Parts of Practice website shown on multiple devices" fill className="object-cover object-center" />
            </div>
          </div>
        </div>
      </section>

      {/* Stats */}
      <section className="bg-sage-700 py-14">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            {whyItMatters.map(item => (
              <div key={item.stat}>
                <p className="font-serif text-4xl text-bark-300 font-medium mb-2">{item.stat}</p>
                <p className="text-cream-300 text-sm leading-snug">{item.detail}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How it works */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div className="relative rounded-3xl overflow-hidden shadow-lg aspect-video lg:aspect-auto lg:h-[380px]">
              <Image src="/how-to-get-found-on-google-therapist-seo.png" alt="SEO strategy for therapists" fill className="object-cover" />
            </div>
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Our Approach</p>
              <h2 className="font-serif text-4xl text-sage-700 font-medium mb-6">SEO that feels as good as it performs.</h2>
              <div className="space-y-5 text-stone-500 leading-relaxed">
                <p>We start by finding the exact terms your ideal clients are already searching â€” not just generic keywords, but the specific language that resonates with people looking for IFS, EMDR, trauma, and other specialty therapies.</p>
                <p>Then we build your visibility strategically: optimizing your existing pages, setting up your Google Business profile, and creating content that helps both search engines and potential clients understand what makes you the right fit.</p>
                <p>No manipulation. No shortcuts. Just honest, ethical SEO that grows steadily over time â€” and keeps growing.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Pricing */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-14">
            <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Pricing</p>
            <h2 className="font-serif text-4xl text-sage-700 font-medium mb-4">Straightforward monthly rates.</h2>
            <p className="text-stone-500 max-w-xl mx-auto">No setup fees. No long-term contracts required. Just consistent, ethical SEO work every month.</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {plans.map(plan => (
              <div key={plan.name} className={`rounded-3xl p-8 border flex flex-col ${plan.highlighted ? 'bg-sage-700 border-sage-600' : 'bg-white border-cream-300'}`}>
                <p className={`text-xs font-semibold uppercase tracking-widest mb-2 ${plan.highlighted ? 'text-bark-300' : 'text-bark-500'}`}>{plan.name}</p>
                <p className={`font-serif text-4xl font-medium mb-4 ${plan.highlighted ? 'text-cream-100' : 'text-sage-700'}`}>{plan.price}</p>
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
          <h2 className="font-serif text-4xl text-sage-700 font-medium mb-5">Let&apos;s get you found.</h2>
          <p className="text-stone-500 text-lg leading-relaxed mb-8">Book a free consultation and we&apos;ll show you exactly where your practice stands in search â€” and what it would take to improve it.</p>
          <Link href="/contact" className="inline-flex items-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
            Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
          </Link>
        </div>
      </section>

    </div>
  )
}

