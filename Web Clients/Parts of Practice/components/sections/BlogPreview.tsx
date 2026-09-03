'use client'

import Link from 'next/link'
import { motion, useInView } from 'framer-motion'
import { useRef } from 'react'
import { ArrowRight } from 'lucide-react'

const posts = [
  {
    category: 'IFS & Marketing',
    title: 'What IFS Teaches Us About Marketing Your Private Practice',
    excerpt:
      'Most therapists have parts that get activated around visibility — the protectors that worry it will feel pushy, or the exiles that wonder if anyone will care. Here\'s how we work with those parts, not against them.',
    readTime: '6 min read',
    href: '/resources/ifs-and-marketing',
    tagBg: 'bg-sage-100 text-sage-600',
  },
  {
    category: 'SEO for Therapists',
    title: 'The Quiet Power of SEO: How Private Practice Therapists Get Found on Google',
    excerpt:
      "SEO doesn't have to feel like a performance. When done ethically, it's simply a way of making sure the people who need you can actually find you — without you having to shout.",
    readTime: '8 min read',
    href: '/resources/therapist-seo-guide',
    tagBg: 'bg-mauve-100 text-mauve-600',
  },
  {
    category: 'Website Design',
    title: 'Why Your Therapy Website Might Be Speaking to the Wrong Parts of Your Clients',
    excerpt:
      'If your website triggers anxiety rather than calm — even subconsciously — potential clients will leave before they ever contact you. Here\'s what to look for, and how to fix it.',
    readTime: '5 min read',
    href: '/resources/therapy-website-design-tips',
    tagBg: 'bg-bark-100 text-bark-600',
  },
]

const fadeUp = {
  hidden: { opacity: 0, y: 24 },
  show: (i: number) => ({
    opacity: 1,
    y: 0,
    transition: { duration: 0.6, delay: i * 0.11, ease: [0.22, 1, 0.36, 1] },
  }),
}

export default function BlogPreview() {
  const ref = useRef(null)
  const inView = useInView(ref, { once: true, margin: '-80px' })

  return (
    <section className="bg-cream-200 section-padding" aria-labelledby="resources-heading">
      <div className="container-wide px-6">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
          <div>
            <p className="text-mauve-500 text-sm font-semibold uppercase tracking-widest mb-3">
              Resources
            </p>
            <h2
              id="resources-heading"
              className="font-serif text-4xl md:text-5xl text-sage-700 font-medium text-balance max-w-lg"
            >
              Thinking out loud about practice, presence, and parts.
            </h2>
          </div>
          <Link
            href="/resources"
            className="flex items-center gap-2 text-sage-600 font-medium hover:text-sage-800 transition-colors shrink-0 underline underline-offset-4"
          >
            All resources <ArrowRight size={15} />
          </Link>
        </div>

        <div ref={ref} className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {posts.map((post, i) => (
            <motion.article
              key={post.title}
              custom={i}
              variants={fadeUp}
              initial="hidden"
              animate={inView ? 'show' : 'hidden'}
              className="group bg-white rounded-2xl border border-cream-300 overflow-hidden hover:border-sage-300 hover:shadow-md transition-all duration-300 flex flex-col"
            >
              <div className={`px-6 py-3 ${post.tagBg}`}>
                <span className="text-xs font-semibold uppercase tracking-wider">{post.category}</span>
              </div>
              <div className="p-7 flex flex-col flex-1">
                <h3 className="font-serif text-xl text-sage-700 font-medium leading-snug mb-3 group-hover:text-sage-600 transition-colors">
                  {post.title}
                </h3>
                <p className="text-stone-500 text-sm leading-relaxed flex-1 mb-5">{post.excerpt}</p>
                <div className="flex items-center justify-between mt-auto pt-4 border-t border-cream-300">
                  <span className="text-stone-400 text-xs">{post.readTime}</span>
                  <Link
                    href={post.href}
                    className="text-sage-600 text-sm font-medium hover:text-sage-800 transition-colors"
                    aria-label={`Read: ${post.title}`}
                  >
                    Read →
                  </Link>
                </div>
              </div>
            </motion.article>
          ))}
        </div>
      </div>
    </section>
  )
}
