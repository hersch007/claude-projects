'use client'

import Link from 'next/link'
import { motion, useInView } from 'framer-motion'
import { useRef } from 'react'
import { ArrowRight } from 'lucide-react'

export default function HomeCTA() {
  const ref = useRef(null)
  const inView = useInView(ref, { once: true, margin: '-80px' })

  return (
    <section className="bg-sage-600 section-padding" aria-labelledby="cta-heading">
      <div className="container-narrow px-6">
        <motion.div
          ref={ref}
          initial={{ opacity: 0, y: 28 }}
          animate={inView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
          className="text-center"
        >
          {/* Decorative rings */}
          <div className="relative inline-flex items-center justify-center mb-8" aria-hidden="true">
            <div className="w-20 h-20 rounded-full bg-sage-500/50" />
            <div className="absolute w-14 h-14 rounded-full bg-sage-500/60" />
            <div className="absolute w-8 h-8 rounded-full bg-mauve-500" />
          </div>

          <h2
            id="cta-heading"
            className="font-serif text-4xl md:text-5xl lg:text-6xl text-cream-100 font-medium leading-tight text-balance mb-6"
          >
            Ready to build something that feels like{' '}
            <em style={{ fontStyle: 'italic', color: '#a75d90' }}>you?</em>
          </h2>

          <p className="text-cream-300 text-lg leading-relaxed max-w-xl mx-auto mb-10 text-balance">
            A free 30-minute consultation with Stephanie and Richard. No pressure, no pitch — just a
            genuine conversation about your practice and whether we&apos;re a good fit.
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/contact"
              className="inline-flex items-center justify-center gap-2 bg-cream-100 text-sage-700 px-9 py-4 rounded-full text-base font-medium hover:bg-white transition-all duration-200 hover:gap-3 group"
            >
              Book a Free Consultation
              <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
            </Link>
            <Link
              href="/about"
              className="inline-flex items-center justify-center gap-2 border border-cream-300 text-cream-100 px-9 py-4 rounded-full text-base font-medium hover:bg-sage-500 transition-colors duration-200"
            >
              Meet Stephanie &amp; Richard
            </Link>
          </div>

          <p className="mt-8 text-cream-300 text-sm">
            Prefer email?{' '}
            <a
              href="mailto:hello@partsofpractice.com"
              className="text-cream-100 hover:text-white underline underline-offset-2 transition-colors"
            >
              hello@partsofpractice.com
            </a>
          </p>
        </motion.div>
      </div>
    </section>
  )
}
