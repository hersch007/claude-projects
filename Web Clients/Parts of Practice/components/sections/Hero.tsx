'use client'

import Link from 'next/link'
import Image from 'next/image'
import { motion } from 'framer-motion'
import { ArrowRight } from 'lucide-react'

const fadeUp = {
  hidden: { opacity: 0, y: 24 },
  show: (i: number) => ({
    opacity: 1,
    y: 0,
    transition: { duration: 0.7, delay: i * 0.12, ease: [0.22, 1, 0.36, 1] },
  }),
}

export default function Hero() {
  return (
    <section
      className="relative min-h-screen flex items-center overflow-hidden bg-cream-100 pt-20"
      aria-label="Hero"
    >
      {/* Subtle background rings */}
      <div className="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div className="absolute -top-40 -right-40 w-[600px] h-[600px] rounded-full border border-cream-300/60" />
        <div className="absolute -top-20 -right-20 w-[400px] h-[400px] rounded-full border border-cream-300/40" />
        <div
          className="absolute bottom-0 left-0 w-96 h-96 rounded-full opacity-20"
          style={{ background: 'radial-gradient(circle, #A47551 0%, transparent 70%)' }}
        />
      </div>

      <div className="relative z-10 max-w-6xl mx-auto px-6 py-16 md:py-24 w-full">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

          {/* Left â€” text */}
          <div>
            {/* Eyebrow */}
            <motion.div
              custom={0}
              variants={fadeUp}
              initial="hidden"
              animate="show"
              className="inline-flex items-center gap-2 bg-cream-200 text-sage-600 text-xs font-semibold uppercase tracking-widest px-4 py-2 rounded-full mb-7"
            >
              <span className="w-1.5 h-1.5 rounded-full bg-bark-500 flex-shrink-0" />
              Built by therapists, for therapists
            </motion.div>

            {/* Headline */}
            <motion.h1
              custom={1}
              variants={fadeUp}
              initial="hidden"
              animate="show"
              className="font-serif text-4xl md:text-5xl lg:text-6xl text-sage-700 font-medium leading-[1.1] tracking-tight mb-6"
            >
              Your practice deserves a website that reflects your{' '}
              <em style={{ fontStyle: 'italic', color: '#a75d90' }}>whole Self.</em>
            </motion.h1>

            {/* Subheadline */}
            <motion.p
              custom={2}
              variants={fadeUp}
              initial="hidden"
              animate="show"
              className="text-lg text-stone-500 leading-relaxed mb-10 max-w-lg"
            >
              We design custom websites, write ethical copy, and build SEO strategies for
              IFS-informed therapists who are ready to grow a practice that feels aligned â€” not exhausting.
            </motion.p>

            {/* CTAs */}
            <motion.div
              custom={3}
              variants={fadeUp}
              initial="hidden"
              animate="show"
              className="flex flex-col sm:flex-row gap-4"
            >
              <Link
                href="/contact"
                className="inline-flex items-center justify-center gap-2 bg-bark-500 text-white px-8 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group"
              >
                Schedule a Free Consultation
                <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
              </Link>
              <Link
                href="/services"
                className="inline-flex items-center justify-center gap-2 bg-cream-200 text-sage-700 px-8 py-4 rounded-full text-base font-medium hover:bg-cream-300 transition-colors duration-200 border border-cream-300"
              >
                Explore Our Services
              </Link>
            </motion.div>

            {/* Trust bar */}
            <motion.div
              custom={4}
              variants={fadeUp}
              initial="hidden"
              animate="show"
              className="mt-12 pt-8 border-t border-cream-300 flex flex-wrap gap-6"
            >
              {[
                { label: 'No templates', detail: 'Every site built from scratch' },
                { label: 'IFS-aligned', detail: 'Authentic, not performative' },
                { label: 'Self-led process', detail: 'Calm & collaborative' },
              ].map((item) => (
                <div key={item.label} className="flex flex-col gap-0.5">
                  <span className="text-bark-500 font-semibold text-sm">{item.label}</span>
                  <span className="text-stone-400 text-xs">{item.detail}</span>
                </div>
              ))}
            </motion.div>
          </div>

          {/* Right â€” photo */}
          <motion.div
            custom={2}
            variants={fadeUp}
            initial="hidden"
            animate="show"
            className="relative flex justify-center lg:justify-end"
          >
            {/* Decorative ring behind photo */}
            <div className="absolute inset-0 flex items-center justify-center" aria-hidden="true">
              <div className="w-[420px] h-[420px] rounded-full border-2 border-cream-300/70" />
            </div>
            <div className="absolute inset-0 flex items-center justify-center" aria-hidden="true">
              <div className="w-[340px] h-[340px] rounded-full border border-bark-300/40" />
            </div>

            {/* Photo */}
            <div className="relative w-80 h-96 lg:w-96 lg:h-[480px] rounded-3xl overflow-hidden shadow-xl">
              <Image
                src="/ifs-therapist-website-design-woman-at-desk.png"
                alt="IFS-informed therapist at work"
                fill
                className="object-cover object-center"
                priority
              />
            </div>

            {/* Floating badge */}
            <div className="absolute -bottom-4 -left-4 lg:left-4 bg-white rounded-2xl shadow-lg px-5 py-4 border border-cream-300">
              <p className="text-sage-700 font-semibold text-sm">IFS-Informed</p>
              <p className="text-stone-400 text-xs mt-0.5">Built by a licensed therapist</p>
            </div>
          </motion.div>

        </div>
      </div>
    </section>
  )
}

