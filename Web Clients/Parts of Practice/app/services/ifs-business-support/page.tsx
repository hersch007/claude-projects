import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Check } from 'lucide-react'

export const metadata: Metadata = {
  title: 'IFS Business Coaching for Therapists | Parts Mapping for Private Practice',
  description: 'IFS-informed business coaching for therapists building private practices. Work with your inner parts around visibility, fees, marketing, and growth — led by a licensed IFS practitioner.',
  alternates: { canonical: 'https://partsofpractice.com/services/ifs-business-support' },
  openGraph: {
    title: 'IFS Business Coaching for Therapists | Parts of Practice',
    description: 'Parts mapping for your private practice. Led by Stephanie Brashear, LPCC-S, LMHC, IFS Practitioner.',
    url: 'https://partsofpractice.com/services/ifs-business-support',
  },
}

const partsWeWork = [
  { part: 'The manager', description: 'who needs everything to look professional enough before you put it online' },
  { part: 'The protector', description: 'who worries that marketing feels pushy or inauthentic' },
  { part: 'The exile', description: 'who quietly wonders if anyone will actually want to work with you' },
  { part: 'The firefighter', description: 'who wants to overhaul everything the moment you feel unseen' },
]

const whatYouGet = [
  'Parts mapping sessions tailored to practice-building',
  'Support around fees, raising rates, and boundaries',
  'Visibility coaching from an IFS-informed lens',
  'Free Parts Mapping Starter Guide',
  'CEU-eligible trainings for IFS professionals',
  'Ongoing support as your practice evolves',
]

export default function IFSBusinessSupportPage() {
  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">IFS Business Support</p>
              <h1 className="font-serif text-5xl md:text-6xl text-sage-700 font-medium leading-tight mb-6">
                Every part of you belongs{' '}
                <em className="italic" style={{ color: '#a75d90' }}>in your practice.</em>
              </h1>
              <p className="text-stone-500 text-lg leading-relaxed mb-8">
                Generic business coaching ignores the inner landscape. We don&apos;t. Parts of Practice
                uses IFS to help you map the voices that show up when you think about growing your practice â€”
                and work with them, not against them.
              </p>
              <div className="flex flex-col sm:flex-row gap-4">
                <Link href="/contact" className="inline-flex items-center justify-center gap-2 bg-bark-500 text-white px-8 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
                  Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
                </Link>
                <Link href="#free-guide" className="inline-flex items-center justify-center gap-2 bg-cream-200 text-sage-700 px-8 py-4 rounded-full text-base font-medium hover:bg-cream-300 border border-cream-300 transition-colors">
                  Get the Free Guide
                </Link>
              </div>
            </div>
            <div className="relative rounded-3xl overflow-hidden shadow-xl aspect-video lg:aspect-auto lg:h-[420px]">
              <Image src="/ifs-informed-business-support-therapy-practice.png" alt="IFS-informed therapy practice support" fill className="object-cover object-center" />
            </div>
          </div>
        </div>
      </section>

      {/* Parts we work with */}
      <section className="bg-sage-700 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-14">
            <p className="text-bark-300 text-sm font-semibold uppercase tracking-widest mb-3">Sound Familiar?</p>
            <h2 className="font-serif text-4xl text-cream-100 font-medium mb-4">You might recognize these parts.</h2>
            <p className="text-cream-300 max-w-xl mx-auto">Building a practice activates all kinds of internal voices. We work with them, not around them.</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            {partsWeWork.map(item => (
              <div key={item.part} className="bg-sage-600/50 rounded-2xl p-7 border border-sage-500/40">
                <p className="font-serif text-xl text-bark-300 font-medium mb-2 italic">{item.part}</p>
                <p className="text-cream-300 text-sm leading-relaxed">{item.description}</p>
              </div>
            ))}
          </div>
          <p className="text-center text-cream-300 mt-10 text-lg font-serif italic">
            &ldquo;Every part of you deserves to belong in your work.&rdquo;
          </p>
          <p className="text-center text-cream-400 text-sm mt-2">â€” Stephanie Brashear, LPCC-S, LMHC</p>
        </div>
      </section>

      {/* What you get */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
            <div>
              <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">What&apos;s Included</p>
              <h2 className="font-serif text-4xl text-sage-700 font-medium mb-6">Build your practice from the inside out.</h2>
              <div className="space-y-4 text-stone-500 leading-relaxed mb-8">
                <p>
                  Stephanie leads all IFS-informed business support. As a licensed therapist and IFS practitioner
                  who built her own private practice, she understands the internal terrain of practice-building
                  in a way that no generic business coach ever could.
                </p>
                <p>
                  Sessions are spacious, curious, and completely free of hustle culture. We help you map
                  what&apos;s happening inside, identify what&apos;s getting in the way, and take aligned
                  action that feels like your whole Self â€” not just a performing part of it.
                </p>
              </div>
              <ul className="space-y-3">
                {whatYouGet.map(item => (
                  <li key={item} className="flex items-start gap-3 text-stone-500 text-sm">
                    <div className="w-5 h-5 rounded-full bg-bark-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                      <Check size={12} className="text-bark-500" />
                    </div>
                    {item}
                  </li>
                ))}
              </ul>
            </div>
            <div className="relative rounded-3xl overflow-hidden shadow-lg aspect-square max-w-md mx-auto lg:mx-0">
              <Image src="/stephanie-brashear-lpcc-s-lmhc-parts-of-practice-founder.png" alt="Stephanie Brashear, IFS Practitioner" fill className="object-cover object-top" />
            </div>
          </div>
        </div>
      </section>

      {/* Free guide */}
      <section id="free-guide" className="section-padding">
        <div className="max-w-3xl mx-auto px-6">
          <div className="bg-cream-200 rounded-3xl border border-cream-300 p-10 md:p-14 text-center">
            <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Free Resource</p>
            <h2 className="font-serif text-4xl text-sage-700 font-medium mb-5">Start with the Parts Mapping Guide.</h2>
            <p className="text-stone-500 text-lg leading-relaxed mb-8 max-w-xl mx-auto">
              A free introductory guide to help you explore the internal dynamics affecting your practice decisions â€”
              around money, visibility, boundaries, and growth. A gentle first step, no commitment required.
            </p>
            <Link href="/contact?resource=mapping-guide" className="inline-flex items-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
              Get the Free Guide <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
            </Link>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-2xl mx-auto px-6 text-center">
          <h2 className="font-serif text-4xl text-sage-700 font-medium mb-5">Ready to lead your practice from Self?</h2>
          <p className="text-stone-500 text-lg leading-relaxed mb-8">
            Book a free call with Stephanie. We&apos;ll start by listening â€” to you, and to all the parts that have something to say.
          </p>
          <Link href="/contact" className="inline-flex items-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group">
            Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
          </Link>
        </div>
      </section>

    </div>
  )
}

