'use client'

import Link from 'next/link'
import Image from 'next/image'
import { motion, useInView } from 'framer-motion'
import { useRef } from 'react'
import { ArrowRight } from 'lucide-react'

export default function MeetTheTeam() {
  const ref = useRef(null)
  const inView = useInView(ref, { once: true, margin: '-80px' })

  return (
    <section className="bg-cream-100 section-padding" aria-labelledby="team-heading">
      <div className="max-w-6xl mx-auto px-6">
        <motion.div
          ref={ref}
          initial={{ opacity: 0, y: 20 }}
          animate={inView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
          className="text-center mb-12"
        >
          <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-3">Who We Are</p>
          <h2 id="team-heading" className="font-serif text-4xl md:text-5xl text-sage-700 font-medium">
            A father and daughter who both said <em className="italic" style={{ color: '#a75d90' }}>yes.</em>
          </h2>
        </motion.div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
          {/* Stephanie */}
          <motion.div
            initial={{ opacity: 0, y: 24 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            transition={{ duration: 0.6, delay: 0.1, ease: [0.22, 1, 0.36, 1] }}
            className="bg-cream-200 rounded-3xl overflow-hidden border border-cream-300 group"
          >
            <div className="relative h-72 overflow-hidden">
              <Image
                src="/stephanie-brashear-lpcc-s-lmhc-parts-of-practice-founder.png"
                alt="Stephanie Brashear, LPCC-S, LMHC"
                fill
                className="object-cover object-top group-hover:scale-105 transition-transform duration-500"
              />
            </div>
            <div className="p-7">
              <p className="text-bark-500 text-xs font-semibold uppercase tracking-widest mb-1">Co-Founder</p>
              <h3 className="font-serif text-2xl text-sage-700 font-medium mb-1">Stephanie Brashear</h3>
              <p className="text-mauve-500 text-sm font-medium mb-4">LPCC-S, LMHC Â· IFS Practitioner</p>
              <p className="text-stone-500 text-sm leading-relaxed">
                A licensed therapist who built her own IFS-informed practice â€” and founded Parts of Practice
                so other therapists could do the same, without losing themselves in the process.
              </p>
            </div>
          </motion.div>

          {/* Richard */}
          <motion.div
            initial={{ opacity: 0, y: 24 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            transition={{ duration: 0.6, delay: 0.2, ease: [0.22, 1, 0.36, 1] }}
            className="bg-cream-200 rounded-3xl overflow-hidden border border-cream-300 group"
          >
            <div className="relative h-72 overflow-hidden">
              <Image
                src="/richard-brashear-parts-of-practice-digital-strategist-seo.jpg"
                alt="Richard Brashear, Digital Strategist"
                fill
                className="object-cover object-top group-hover:scale-105 transition-transform duration-500"
              />
            </div>
            <div className="p-7">
              <p className="text-bark-500 text-xs font-semibold uppercase tracking-widest mb-1">Co-Founder</p>
              <h3 className="font-serif text-2xl text-sage-700 font-medium mb-1">Richard Brashear</h3>
              <p className="text-mauve-500 text-sm font-medium mb-4">Digital Strategist Â· Former CMO</p>
              <p className="text-stone-500 text-sm leading-relaxed">
                30+ years of digital strategy and marketing leadership â€” brought to bear specifically
                for therapists who want to grow their practice without the hustle.
              </p>
            </div>
          </motion.div>
        </div>

        <motion.div
          initial={{ opacity: 0 }}
          animate={inView ? { opacity: 1 } : {}}
          transition={{ duration: 0.6, delay: 0.35 }}
          className="text-center mt-10"
        >
          <Link
            href="/about"
            className="inline-flex items-center gap-2 text-sage-600 font-medium hover:text-sage-800 transition-colors underline underline-offset-4"
          >
            Read our story <ArrowRight size={15} />
          </Link>
        </motion.div>
      </div>
    </section>
  )
}

