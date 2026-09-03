import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Check } from 'lucide-react'

export const metadata: Metadata = {
  title: 'How We Work',
  description: 'Our calm, transparent process for building therapist websites and SEO strategies â€” from the first conversation to long-term support.',
}

const steps = [
  {
    number: '01',
    phase: 'The First Conversation',
    title: 'We listen before we plan.',
    body: [
      'Every engagement starts with a free 30-minute call â€” unhurried, genuinely curious, and completely free of pressure or pitch. We want to understand your practice, your values, the clients you most want to serve, and where you are right now.',
      'We also want to know if any parts of you have something to say about this. Visibility, marketing, putting yourself online â€” these things activate internal voices in almost every therapist we talk to. We hold space for that from the very beginning.',
    ],
    detail: 'Free 30-minute consultation',
    image: '/stephanie-brashear-lpcc-s-lmhc-parts-of-practice-founder.png',
  },
  {
    number: '02',
    phase: 'Strategy & Alignment',
    title: 'We build a plan that actually fits.',
    body: [
      'Once we understand who you are and what you need, we build a strategy tailored specifically to your practice. Website architecture, copywriting direction, SEO foundations, brand voice â€” all of it shaped around you, not a template.',
      "This is collaborative. You review, you respond, you tell us when something doesn't feel right. We refine until the plan feels aligned â€” because a strategy you don't believe in won't work, no matter how technically sound it is.",
    ],
    detail: 'Strategy + design phase (1â€“2 weeks)',
    image: '/how-to-get-found-on-google-therapist-seo.png',
  },
  {
    number: '03',
    phase: 'We Build',
    title: 'Transparent, steady, no surprises.',
    body: [
      "We bring the plan to life thoughtfully and transparently. You'll always know what we're working on, what's coming next, and why we're making the decisions we're making. Regular check-ins. Clear communication. No disappearing acts.",
      'For websites, that means custom design, copywriting, SEO setup, and testing â€” all done before we launch. For ongoing SEO, it means consistent monthly work with clear reporting on what\'s changing and why.',
    ],
    detail: 'Build phase (2â€“6 weeks depending on scope)',
    image: '/therapist-website-design-ifs-emdr-asheville-nc.png',
  },
  {
    number: '04',
    phase: 'Launch & Beyond',
    title: 'We stay.',
    body: [
      "Launch isn't the end â€” it's the beginning. We remain available as your practice grows, evolves, and changes. New specialty pages, updated copy, SEO adjustments, technical support â€” we're here for all of it.",
      'Most of our clients have been with us for years. That long-term relationship is built into how we work from day one.',
    ],
    detail: 'Ongoing monthly support',
    image: '/therapist-copywriting-services-woman-at-laptop.png',
  },
]

const principles = [
  {
    title: 'Slow is safe.',
    body: "We don't rush. A website built quickly without real understanding of who you are will need to be rebuilt. We take the time to get it right the first time.",
  },
  {
    title: 'Your parts are welcome here.',
    body: "If a protector flares up mid-project, we don't push through it. We slow down, name it, and figure out what it needs. This is IFS-informed work in practice, not just in name.",
  },
  {
    title: 'We say what we mean.',
    body: "No vague deliverables, no hidden fees, no surprises. You'll always know exactly what we're doing, what it costs, and what to expect next.",
  },
  {
    title: "We're not the right fit for everyone.",
    body: "And that's okay. If your practice needs something we can't offer well, we'll say so â€” and help you find someone who can. No pitch. No pressure.",
  },
]

const faqs = [
  {
    q: 'How long does a website project take?',
    a: 'Most website projects take 2â€“6 weeks from strategy to launch, depending on scope. We\'ll give you a clear timeline at the start of every project.',
  },
  {
    q: 'Do I need to know anything about websites or SEO?',
    a: 'Not at all. Our job is to handle the technical complexity so you don\'t have to. We explain things clearly when it\'s useful â€” and we never talk over your head.',
  },
  {
    q: 'Can I start with just one service?',
    a: 'Absolutely. Many clients start with just a website or just copywriting, and add SEO support over time. We\'ll help you figure out what makes the most sense for where your practice is right now.',
  },
  {
    q: 'What if I\'m not sure my practice is ready?',
    a: 'That\'s exactly the kind of thing we\'d love to talk through on a free consultation call. There\'s no perfect moment â€” but there\'s usually a good-enough moment, and we can help you find it.',
  },
  {
    q: 'How is this different from working with a regular web agency?',
    a: 'We work exclusively with therapists, and we\'re led by a therapist. We understand IFS, EMDR, private practice dynamics, and the internal barriers that come with being visible online. A general agency doesn\'t â€” and that difference shows in the work.',
  },
]

export default function HowWeWorkPage() {
  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">How We Work</p>
          <h1 className="font-serif text-5xl md:text-6xl text-sage-700 font-medium leading-tight mb-6">
            Calm, transparent, and built around{' '}
            <em className="italic" style={{ color: '#a75d90' }}>you.</em>
          </h1>
          <p className="text-stone-500 text-lg leading-relaxed max-w-2xl mx-auto">
            We don&apos;t rush. We don&apos;t over-promise. We listen first, build a plan that fits,
            and stay with you long after launch. Here&apos;s exactly what working with us looks like.
          </p>
        </div>
      </section>

      {/* Process steps */}
      <section className="pb-8">
        <div className="max-w-6xl mx-auto px-6 space-y-6">
          {steps.map((step, i) => (
            <div key={step.number} className="grid grid-cols-1 lg:grid-cols-2 rounded-3xl overflow-hidden border border-cream-300 shadow-sm">
              <div className={`relative h-64 lg:h-auto min-h-[280px] ${i % 2 !== 0 ? 'lg:order-2' : ''}`}>
                <Image src={step.image} alt={step.title} fill className="object-cover object-center" />
                <div className="absolute inset-0 bg-sage-800/30" />
                <div className="absolute top-6 left-6">
                  <span className="font-serif text-6xl text-white/30 font-medium leading-none">{step.number}</span>
                </div>
                <div className="absolute bottom-6 left-6 right-6">
                  <span className="inline-block bg-bark-500 text-white text-xs font-semibold uppercase tracking-widest px-4 py-2 rounded-full">
                    {step.detail}
                  </span>
                </div>
              </div>
              <div className={`bg-white p-10 lg:p-12 flex flex-col justify-center ${i % 2 !== 0 ? 'lg:order-1' : ''}`}>
                <p className="text-bark-500 text-xs font-semibold uppercase tracking-widest mb-3">{step.phase}</p>
                <h2 className="font-serif text-3xl text-sage-700 font-medium mb-5">{step.title}</h2>
                {step.body.map((para, j) => (
                  <p key={j} className="text-stone-500 leading-relaxed mb-4 last:mb-0">{para}</p>
                ))}
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* Principles */}
      <section className="bg-sage-700 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-14">
            <p className="text-bark-300 text-sm font-semibold uppercase tracking-widest mb-3">Our Principles</p>
            <h2 className="font-serif text-4xl text-cream-100 font-medium">The way we show up, always.</h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {principles.map(p => (
              <div key={p.title} className="bg-sage-600/50 rounded-2xl p-8 border border-sage-500/40">
                <h3 className="font-serif text-xl text-bark-300 font-medium mb-3 italic">{p.title}</h3>
                <p className="text-cream-300 text-sm leading-relaxed">{p.body}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* FAQ */}
      <section className="bg-cream-200 section-padding">
        <div className="max-w-3xl mx-auto px-6">
          <div className="text-center mb-12">
            <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">FAQ</p>
            <h2 className="font-serif text-4xl text-sage-700 font-medium">Common questions.</h2>
          </div>
          <div className="space-y-5">
            {faqs.map(faq => (
              <div key={faq.q} className="bg-white rounded-2xl border border-cream-300 p-7">
                <h3 className="font-serif text-lg text-sage-700 font-medium mb-3">{faq.q}</h3>
                <p className="text-stone-500 text-sm leading-relaxed">{faq.a}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="bg-cream-200 rounded-3xl border border-cream-300 p-12 md:p-16 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <div>
              <h2 className="font-serif text-4xl text-sage-700 font-medium mb-5">
                Ready to take the first step?
              </h2>
              <p className="text-stone-500 text-lg leading-relaxed">
                It starts with a free 30-minute call. No pitch, no pressure â€” just a genuine conversation
                about your practice and whether we&apos;re a good fit for each other.
              </p>
            </div>
            <div className="flex flex-col gap-4 lg:items-end">
              <Link href="/contact" className="inline-flex items-center justify-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group w-full lg:w-auto">
                Book a Free Consultation <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
              </Link>
              <Link href="/services" className="inline-flex items-center justify-center gap-2 bg-white text-sage-700 px-9 py-4 rounded-full text-base font-medium hover:bg-cream-100 border border-cream-300 transition-colors w-full lg:w-auto">
                See our services
              </Link>
              <p className="text-stone-400 text-sm lg:text-right">
                Or email us at{' '}
                <a href="mailto:hello@partsofpractice.com" className="text-sage-600 underline underline-offset-2 hover:text-sage-800 transition-colors">
                  hello@partsofpractice.com
                </a>
              </p>
            </div>
          </div>
        </div>
      </section>

    </div>
  )
}

