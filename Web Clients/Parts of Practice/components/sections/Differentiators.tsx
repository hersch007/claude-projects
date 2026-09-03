'use client'

import { motion, useInView } from 'framer-motion'
import { useRef } from 'react'

const differentiators = [
  {
    number: '01',
    title: 'Built by therapists — not just for them.',
    body: "Stephanie (LPCC-S, LMHC) brings deep clinical knowledge and lived experience of private practice to every project. Richard brings the technical and SEO expertise. Together, we understand what it actually feels like to build a practice — the excited parts and the hesitant ones.",
  },
  {
    number: '02',
    title: 'Authentic IFS integration, not surface-level calm.',
    body: "It's easy to slap a nature photo on a website and call it \"therapist-friendly.\" We go deeper. IFS language, values, and principles run through everything we build — because we believe your marketing should be as congruent as your clinical work.",
  },
  {
    number: '03',
    title: 'No templates. No hustle. No hype.',
    body: "We don't use themes, drag-and-drop builders, or cookie-cutter copy. Every website we design is built from scratch to reflect you. And we do all of it without urgency tactics, fear-based marketing, or language that would make your supervisor raise an eyebrow.",
  },
  {
    number: '04',
    title: 'Self-led from start to finish.',
    body: "We move at a pace that works for you. Our process is transparent, collaborative, and calm. We check in with you rather than push through. If a part of you feels uncertain or overwhelmed, we slow down — not speed up.",
  },
]

const fadeUp = {
  hidden: { opacity: 0, y: 28 },
  show: (i: number) => ({
    opacity: 1,
    y: 0,
    transition: { duration: 0.65, delay: i * 0.1, ease: [0.22, 1, 0.36, 1] },
  }),
}

export default function Differentiators() {
  const ref = useRef(null)
  const inView = useInView(ref, { once: true, margin: '-60px' })

  return (
    <section className="bg-sage-700 section-padding" aria-labelledby="why-us-heading">
      <div className="container-wide px-6">
        <div className="grid grid-cols-1 lg:grid-cols-5 gap-14 items-start">
          <div className="lg:col-span-2">
            <p className="text-cream-300 text-sm font-semibold uppercase tracking-widest mb-4">
              Why Parts of Practice
            </p>
            <h2
              id="why-us-heading"
              className="font-serif text-4xl md:text-5xl text-cream-100 font-medium leading-tight text-balance mb-6"
            >
              There are a lot of therapist marketing agencies.{' '}
              <em style={{ fontStyle: 'italic', color: '#a75d90' }}>We are not that.</em>
            </h2>
            <p className="text-cream-300 text-base leading-relaxed">
              The therapist website industry is full of templates, upsells, and one-size-fits-all
              approaches. We built Parts of Practice because we believed something different was
              possible — and necessary.
            </p>
          </div>

          <div ref={ref} className="lg:col-span-3 space-y-8">
            {differentiators.map((item, i) => (
              <motion.div
                key={item.number}
                custom={i}
                variants={fadeUp}
                initial="hidden"
                animate={inView ? 'show' : 'hidden'}
                className="flex gap-6 items-start"
              >
                <span className="font-serif text-3xl text-mauve-400 font-medium flex-shrink-0 leading-none mt-1">
                  {item.number}
                </span>
                <div>
                  <h3 className="font-serif text-xl text-cream-100 font-medium mb-2 leading-snug">
                    {item.title}
                  </h3>
                  <p className="text-cream-300 text-sm leading-relaxed">{item.body}</p>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
