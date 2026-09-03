import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { Mail, Phone, MapPin, Calendar } from 'lucide-react'
import ContactForm from '@/components/sections/ContactForm'

export const metadata: Metadata = {
  title: 'Book a Free Consultation',
  description: 'Schedule a free 30-minute consultation with Stephanie and Richard at Parts of Practice. No pressure — just a genuine conversation about your practice.',
}

const contactDetails = [
  {
    icon: Calendar,
    label: 'Book online',
    value: 'Free 30-minute consultation',
    href: 'https://calendly.com/stephaniebrashear/parts-of-practice-website-consultation',
    external: true,
  },
  {
    icon: Mail,
    label: 'Email us',
    value: 'contact@partsofpractice.com',
    href: 'mailto:contact@partsofpractice.com',
    external: false,
  },
  {
    icon: Phone,
    label: 'Call us',
    value: '(803) 415-5062',
    href: 'tel:+18034155062',
    external: false,
  },
  {
    icon: MapPin,
    label: 'Locations',
    value: 'Rock Hill, SC · Providence, RI',
    href: undefined,
    external: false,
  },
]

export default function ContactPage() {
  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding pb-10">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">Get in Touch</p>
          <h1 className="font-serif text-5xl md:text-6xl text-sage-700 font-medium leading-tight mb-6">
            Let&apos;s begin with{' '}
            <em className="italic" style={{ color: '#a75d90' }}>curiosity.</em>
          </h1>
          <p className="text-stone-500 text-lg leading-relaxed max-w-2xl mx-auto">
            Whether you&apos;re ready to get started or just exploring whether we might be a good fit —
            reach out. Our goal is simply to have a genuine conversation about your practice, with no pressure and no pitch.
          </p>
        </div>
      </section>

      {/* Main content */}
      <section className="pb-24">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-5 gap-10">

            {/* Left — contact info + Calendly CTA */}
            <div className="lg:col-span-2 space-y-6">

              {/* Calendly featured */}
              <div className="bg-sage-700 rounded-3xl p-8">
                <p className="text-bark-300 text-xs font-semibold uppercase tracking-widest mb-3">Easiest way to connect</p>
                <h2 className="font-serif text-2xl text-cream-100 font-medium mb-3">
                  Book a free 30-minute call
                </h2>
                <p className="text-cream-300 text-sm leading-relaxed mb-6">
                  Pick a time that works for you. Stephanie and Richard will both be there — no prep needed, just show up as you are.
                </p>
                <a
                  href="https://calendly.com/stephaniebrashear/parts-of-practice-website-consultation"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-2 bg-bark-500 text-white px-6 py-3 rounded-full text-sm font-medium hover:bg-bark-600 transition-colors w-full justify-center"
                >
                  <Calendar size={16} />
                  Schedule on Calendly
                </a>
              </div>

              {/* Contact details */}
              <div className="bg-white rounded-3xl border border-cream-300 p-8 space-y-5">
                <p className="text-sage-700 font-semibold text-sm">Other ways to reach us</p>
                {contactDetails.slice(1).map(item => (
                  <div key={item.label} className="flex items-start gap-4">
                    <div className="w-9 h-9 rounded-full bg-cream-200 flex items-center justify-center flex-shrink-0">
                      <item.icon size={16} className="text-bark-500" />
                    </div>
                    <div>
                      <p className="text-stone-400 text-xs font-semibold uppercase tracking-wide mb-0.5">{item.label}</p>
                      {item.href ? (
                        <a
                          href={item.href}
                          className="text-sage-700 text-sm font-medium hover:text-bark-500 transition-colors"
                        >
                          {item.value}
                        </a>
                      ) : (
                        <p className="text-sage-700 text-sm font-medium">{item.value}</p>
                      )}
                    </div>
                  </div>
                ))}
              </div>

              {/* Reassurance */}
              <div className="bg-cream-200 rounded-3xl border border-cream-300 p-8">
                <div className="relative rounded-2xl overflow-hidden aspect-video mb-4">
                  <Image
                    src="/stephanie-and-richard-brashear-parts-of-practice-ifs-therapy-website-design.png"
                    alt="Stephanie and Richard Brashear, founders of Parts of Practice"
                    fill
                    className="object-cover object-top"
                  />
                </div>
                <p className="font-serif text-base text-sage-700 font-medium italic">
                  &ldquo;No pressure. No pitch. Just two people who care about your practice, listening.&rdquo;
                </p>
                <p className="text-stone-400 text-xs mt-1">— Stephanie &amp; Richard Brashear</p>
              </div>
            </div>

            {/* Right — contact form */}
            <div className="lg:col-span-3">
              <div className="bg-white rounded-3xl border border-cream-300 p-8 md:p-10">
                <p className="text-bark-500 text-xs font-semibold uppercase tracking-widest mb-2">Send us a message</p>
                <h2 className="font-serif text-2xl text-sage-700 font-medium mb-6">
                  Prefer to write first? We&apos;d love to hear from you.
                </h2>
                <ContactForm />
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* What to expect */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-4xl mx-auto px-6">
          <div className="text-center mb-10">
            <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">What Happens Next</p>
            <h2 className="font-serif text-4xl text-sage-700 font-medium">What to expect after you reach out.</h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {[
              { step: '1', title: 'We respond within 24 hours', body: 'No automated replies. A real message from Stephanie or Richard, usually within one business day.' },
              { step: '2', title: 'We schedule a free call', body: "A 30-minute conversation — unhurried, curious, and genuinely exploratory. We want to understand where you are." },
              { step: '3', title: 'We figure out the fit', body: "If we're a good match, we'll share what working together might look like. If not, we'll say so honestly — and help where we can." },
            ].map(item => (
              <div key={item.step} className="bg-white rounded-2xl border border-cream-300 p-7">
                <div className="w-9 h-9 rounded-full bg-sage-600 text-cream-100 flex items-center justify-center font-bold text-sm mb-5">
                  {item.step}
                </div>
                <h3 className="font-serif text-lg text-sage-700 font-medium mb-2">{item.title}</h3>
                <p className="text-stone-500 text-sm leading-relaxed">{item.body}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

    </div>
  )
}
