'use client'

import { motion, useInView } from 'framer-motion'
import { useRef } from 'react'

const testimonials = [
  {
    quote:
      "Working with Parts of Practice was the first time I didn't feel like I had to perform or hustle to get my marketing done. They really understood IFS — not just as a buzzword, but as a way of working. My website finally sounds like me.",
    name: 'Rachel M.',
    title: 'LCSW, Private Practice',
    initials: 'RM',
  },
  {
    quote:
      "I'd been putting off building a website for two years because everything I saw felt either generic or overwhelming. Stephanie and Richard made the whole process feel manageable. The SEO work alone has brought in clients I never would have found otherwise.",
    name: 'James T.',
    title: 'LPC, IFS-Certified',
    initials: 'JT',
  },
  {
    quote:
      "What surprised me most was how much the process itself felt like the therapy I practice. Curious, spacious, never pushy. I finally have a site I'm proud to share — and that actually reflects the kind of therapist I am.",
    name: 'Alicia D.',
    title: 'LMFT, Trauma Specialist',
    initials: 'AD',
  },
]

const fadeUp = {
  hidden: { opacity: 0, y: 24 },
  show: (i: number) => ({
    opacity: 1,
    y: 0,
    transition: { duration: 0.65, delay: i * 0.12, ease: [0.22, 1, 0.36, 1] },
  }),
}

export default function Testimonials() {
  const ref = useRef(null)
  const inView = useInView(ref, { once: true, margin: '-80px' })

  return (
    <section className="bg-cream-100 section-padding" aria-labelledby="testimonials-heading">
      <div className="container-wide px-6">
        <div className="text-center mb-14">
          <p className="text-mauve-500 text-sm font-semibold uppercase tracking-widest mb-3">
            From Therapists We&apos;ve Worked With
          </p>
          <h2
            id="testimonials-heading"
            className="font-serif text-4xl md:text-5xl text-sage-700 font-medium text-balance"
          >
            What it feels like to work with us.
          </h2>
        </div>

        <div ref={ref} className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {testimonials.map((t, i) => (
            <motion.blockquote
              key={t.name}
              custom={i}
              variants={fadeUp}
              initial="hidden"
              animate={inView ? 'show' : 'hidden'}
              className="bg-white rounded-2xl p-8 border border-cream-300 flex flex-col"
            >
              <span className="font-serif text-5xl text-mauve-200 leading-none mb-4 select-none" aria-hidden="true">
                &ldquo;
              </span>
              <p className="text-stone-500 text-sm leading-relaxed flex-1 italic mb-6">{t.quote}</p>
              <footer className="flex items-center gap-3 mt-auto pt-5 border-t border-cream-300">
                <div className="w-10 h-10 rounded-full bg-sage-100 text-sage-600 flex items-center justify-center font-semibold text-sm flex-shrink-0">
                  {t.initials}
                </div>
                <div>
                  <p className="text-sage-700 font-semibold text-sm">{t.name}</p>
                  <p className="text-stone-400 text-xs">{t.title}</p>
                </div>
              </footer>
            </motion.blockquote>
          ))}
        </div>

        <p className="text-center text-stone-400 text-xs mt-8">
          * Testimonials are illustrative examples. Real testimonials coming soon as client work grows.
        </p>
      </div>
    </section>
  )
}
