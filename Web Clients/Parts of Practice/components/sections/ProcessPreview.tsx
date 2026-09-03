'use client'

import Link from 'next/link'
import { motion, useInView } from 'framer-motion'
import { useRef } from 'react'

const steps = [
  {
    step: 'Step 1',
    title: 'We Listen',
    body: 'It starts with a free consultation — unhurried, curious, and genuinely exploratory. We want to understand your practice, your values, your ideal clients, and any parts of you that might have something to say about visibility and marketing.',
    detail: '30-min free consultation',
  },
  {
    step: 'Step 2',
    title: 'We Align',
    body: "Together, we build a strategy that fits. Website architecture, copywriting direction, SEO foundations, and brand voice — all aligned with who you are, not who someone else thinks a therapist's website should look like.",
    detail: 'Strategy & design phase',
  },
  {
    step: 'Step 3',
    title: 'We Build',
    body: 'We bring the plan to life — thoughtfully, transparently, and with regular check-ins. You review, we refine. When we launch, you feel proud of it. Then we stay: supporting your site, growing your SEO, and being there as your practice evolves.',
    detail: 'Build, launch & ongoing support',
  },
]

const fadeUp = {
  hidden: { opacity: 0, y: 24 },
  show: (i: number) => ({
    opacity: 1,
    y: 0,
    transition: { duration: 0.6, delay: i * 0.14, ease: [0.22, 1, 0.36, 1] },
  }),
}

export default function ProcessPreview() {
  const ref = useRef(null)
  const inView = useInView(ref, { once: true, margin: '-80px' })

  return (
    <section className="bg-cream-200 section-padding" aria-labelledby="process-heading">
      <div className="container-wide px-6">
        <div className="text-center mb-16">
          <p className="text-mauve-500 text-sm font-semibold uppercase tracking-widest mb-3">
            How We Work
          </p>
          <h2
            id="process-heading"
            className="font-serif text-4xl md:text-5xl text-sage-700 font-medium text-balance mb-5"
          >
            A calm, transparent process — from first hello to long-term support.
          </h2>
          <p className="text-stone-500 text-lg max-w-xl mx-auto leading-relaxed">
            We don&apos;t rush. We don&apos;t over-promise. We just do good work, together.
          </p>
        </div>

        <div ref={ref} className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-14">
          {steps.map((step, i) => (
            <motion.div
              key={step.step}
              custom={i}
              variants={fadeUp}
              initial="hidden"
              animate={inView ? 'show' : 'hidden'}
            >
              <div className="bg-white rounded-2xl p-8 border border-cream-300 h-full">
                <div className="flex items-center gap-3 mb-5">
                  <div className="w-10 h-10 rounded-full bg-sage-600 text-cream-100 flex items-center justify-center flex-shrink-0">
                    <span className="text-sm font-bold">{i + 1}</span>
                  </div>
                  <div>
                    <p className="text-stone-400 text-xs font-semibold uppercase tracking-wider">
                      {step.step}
                    </p>
                    <h3 className="font-serif text-xl text-sage-700 font-medium">{step.title}</h3>
                  </div>
                </div>
                <p className="text-stone-500 text-sm leading-relaxed mb-4">{step.body}</p>
                <p className="text-mauve-500 text-xs font-semibold uppercase tracking-wide border-t border-cream-300 pt-4">
                  {step.detail}
                </p>
              </div>
            </motion.div>
          ))}
        </div>

        <div className="text-center">
          <Link
            href="/how-we-work"
            className="inline-flex items-center gap-2 text-sage-600 font-medium hover:text-sage-800 transition-colors underline underline-offset-4"
          >
            See the full process →
          </Link>
        </div>
      </div>
    </section>
  )
}
