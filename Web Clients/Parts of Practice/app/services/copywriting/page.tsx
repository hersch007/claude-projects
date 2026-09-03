import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Check } from 'lucide-react'

export const metadata: Metadata = {
  title: 'Copywriting for Therapists | IFS-Informed Website Copy & Content',
  description: 'Website copy written by a licensed IFS therapist for therapists in private practice. Authentic, warm, and completely free of pushy sales language — words that sound like your whole Self.',
  alternates: { canonical: 'https://partsofpractice.com/services/copywriting' },
  openGraph: {
    title: 'Copywriting for Therapists | Parts of Practice',
    description: 'Website copy written by a licensed IFS therapist. Warm, authentic, and free of hype.',
    url: 'https://partsofpractice.com/services/copywriting',
  },
}

const plans = [
  {
    name: 'Conversation',
    price: '$150/page',
    description: 'Single-page copy for a therapist who needs clear, client-friendly language.',
    features: ['Polished service descriptions', 'Clear About page language', 'Client-friendly tone', 'No clinical jargon', 'One round of revisions'],
  },
  {
    name: 'Story',
    price: '$500/project',
    description: 'Full-voice copy for a therapist ready to show up as their whole Self.',
    features: ['Expanded About page + bio', 'Narrative-driven homepage', 'Values + specialty weaving', 'IFS-aligned language throughout', 'Two rounds of revisions'],
    highlighted: true,
  },
  {
    name: 'Narrative+',
    price: 'Custom',
    description: 'Ongoing content for a practice investing in long-term authority.',
    features: ['Blog post series', 'Email nurture sequences', 'Brand story copy', 'SEO-informed content strategy', 'Ongoing support retainer'],
  },
]

export default function CopywritingPage() {
  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">Copywriting</p>
              <h1 className="font-serif text-5xl md:text-6xl text-sage-700 font-medium leading-tight mb-6">
                Words that sound like <em className="italic" style={{ color: '#a75d90' }}>you.</em>
              </h1>
              <p className="text-stone-500 text-lg leading-relaxed mb-4">
                Your clients are already searching for you â€” the right words make sure they recognize you when they arrive.
              </p>
              <p className="text-stone-500 text-lg leading-relaxed mb-8">
                We write IFS-informed copy that translates your clinical expertise into language that resonates
                with the humans on the other side of the screen â€” without sounding like a sales pitch.
              </p>
              <Link href="/contact" className="inline-flex items-center gap-2 bg-bark-500 text-white px-8 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
                Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
              </Link>
            </div>
            <div className="relative rounded-3xl overflow-hidden shadow-xl aspect-video lg:aspect-auto lg:h-[420px]">
              <Image src="/therapist-copywriting-services-woman-at-laptop.png" alt="Therapist copywriting services" fill className="object-cover object-center" />
            </div>
          </div>
        </div>
      </section>

      {/* How we write */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div className="relative rounded-3xl overflow-hidden shadow-lg aspect-video lg:aspect-auto lg:h-[380px]">
              <Image src="/therapist-copywriting-services-writing-notes.png" alt="IFS-informed copywriting process" fill className="object-cover" />
            </div>
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Our Process</p>
              <h2 className="font-serif text-4xl text-sage-700 font-medium mb-6">We listen before we write.</h2>
              <div className="space-y-5 text-stone-500 leading-relaxed">
                <p>
                  Every project starts with a conversation. We want to understand how you talk about your work,
                  what draws you to the clients you serve, and what parts of you might have something to say
                  about putting yourself out there in words.
                </p>
                <p>
                  From there, we do keyword research â€” not to force your voice into SEO molds, but to find
                  the natural overlap between how you speak and how your ideal clients search.
                </p>
                <p>
                  What comes out sounds like your whole Self: warm, specific, grounded â€” and findable.
                </p>
              </div>
              <ul className="mt-8 space-y-3">
                {['Client interview to capture your authentic voice', 'Keyword research integrated naturally', 'IFS-aware language throughout', 'Revision support included'].map(item => (
                  <li key={item} className="flex items-start gap-3 text-stone-500 text-sm">
                    <div className="w-5 h-5 rounded-full bg-bark-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                      <Check size={12} className="text-bark-500" />
                    </div>
                    {item}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* Pricing */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-14">
            <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Pricing</p>
            <h2 className="font-serif text-4xl text-sage-700 font-medium mb-4">Copy that pays for itself.</h2>
            <p className="text-stone-500 max-w-xl mx-auto">One client who found you through great copy more than covers the investment. Here&apos;s what we offer.</p>
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
          <h2 className="font-serif text-4xl text-sage-700 font-medium mb-5">
            Ready to find your words?
          </h2>
          <p className="text-stone-500 text-lg leading-relaxed mb-8">
            Let&apos;s start with a conversation about your practice, your voice, and what you want your website to say.
          </p>
          <Link href="/contact" className="inline-flex items-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
            Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
          </Link>
        </div>
      </section>

    </div>
  )
}

