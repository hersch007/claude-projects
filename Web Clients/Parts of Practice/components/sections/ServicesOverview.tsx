'use client'

import Link from 'next/link'
import { motion, useInView } from 'framer-motion'
import { useRef } from 'react'
import { Globe, Search, PenLine, Heart } from 'lucide-react'

const services = [
  {
    icon: Globe,
    title: 'Custom Website Design',
    tagline: 'No templates. Just alignment.',
    description:
      'A website built from who you actually are — your values, your voice, your approach. We design every element intentionally, so the therapists who need you most feel it the moment they land on your page.',
    href: '/services/website-design',
    iconBg: 'bg-sage-100 text-sage-600',
  },
  {
    icon: Search,
    title: 'SEO for Therapists',
    tagline: 'Be found by the clients already looking for you.',
    description:
      'Ethical, effective SEO that helps your private practice appear in search — without gaming the system. We build a foundation that grows quietly in the background while you focus on your work.',
    href: '/services/seo',
    iconBg: 'bg-mauve-100 text-mauve-600',
  },
  {
    icon: PenLine,
    title: 'Ethical Copywriting',
    tagline: 'Words that reflect your whole Self — and speak to theirs.',
    description:
      "Copy that sounds like you on your best day: warm, clear, and completely free of the pushy language that doesn’t sit right with most therapists. We write it. You recognize yourself in it.",
    href: '/services/copywriting',
    iconBg: 'bg-bark-100 text-bark-600',
  },
  {
    icon: Heart,
    title: 'IFS Business Support',
    tagline: 'Parts mapping for your practice, not just your clients.',
    description:
      'When parts of you get activated around visibility, marketing, or growing your practice — we help you get curious about them rather than push through them. Self-led business support for therapists.',
    href: '/services/ifs-business-support',
    iconBg: 'bg-cream-300 text-stone-600',
  },
]

const container = {
  hidden: {},
  show: { transition: { staggerChildren: 0.12 } },
}

const cardVariant = {
  hidden: { opacity: 0, y: 32 },
  show: { opacity: 1, y: 0, transition: { duration: 0.6, ease: [0.22, 1, 0.36, 1] } },
}

export default function ServicesOverview() {
  const ref = useRef(null)
  const inView = useInView(ref, { once: true, margin: '-80px' })

  return (
    <section className="bg-cream-100 section-padding" aria-labelledby="services-heading">
      <div className="container-wide px-6">
        <div className="text-center mb-14">
          <p className="text-mauve-500 text-sm font-semibold uppercase tracking-widest mb-3">
            What We Offer
          </p>
          <h2
            id="services-heading"
            className="font-serif text-4xl md:text-5xl text-sage-700 font-medium text-balance mb-5"
          >
            Everything your practice needs to be found, felt, and chosen.
          </h2>
          <p className="text-stone-500 text-lg max-w-2xl mx-auto leading-relaxed text-balance">
            We bring together website design, SEO, copywriting, and IFS-informed business support — so
            you can stop piecemealing your marketing and start building something cohesive.
          </p>
        </div>

        <motion.div
          ref={ref}
          variants={container}
          initial="hidden"
          animate={inView ? 'show' : 'hidden'}
          className="grid grid-cols-1 md:grid-cols-2 gap-6"
        >
          {services.map((service) => {
            const Icon = service.icon
            return (
              <motion.article
                key={service.title}
                variants={cardVariant}
                className="group bg-white rounded-2xl p-8 border border-cream-300 hover:border-sage-300 hover:shadow-md transition-all duration-300 flex flex-col"
              >
                <div className={`w-11 h-11 rounded-xl ${service.iconBg} flex items-center justify-center mb-5 flex-shrink-0`}>
                  <Icon size={20} strokeWidth={1.5} />
                </div>
                <p className="text-stone-400 text-xs font-semibold uppercase tracking-wider mb-2">
                  {service.tagline}
                </p>
                <h3 className="font-serif text-2xl text-sage-700 font-medium mb-3">{service.title}</h3>
                <p className="text-stone-500 text-sm leading-relaxed flex-1">{service.description}</p>
                <Link
                  href={service.href}
                  className="mt-6 inline-flex items-center text-sage-600 text-sm font-medium hover:text-sage-800 transition-colors group-hover:underline underline-offset-4"
                  aria-label={`Learn more about ${service.title}`}
                >
                  Learn more →
                </Link>
              </motion.article>
            )
          })}
        </motion.div>
      </div>
    </section>
  )
}
