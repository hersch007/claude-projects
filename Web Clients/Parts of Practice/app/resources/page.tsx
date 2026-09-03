import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Calendar } from 'lucide-react'
import { getPosts, formatDate } from '@/lib/wordpress'

export const metadata: Metadata = {
  title: 'Resources for Therapists',
  description: 'Articles, guides, and thinking on IFS-informed marketing, SEO for therapists, and building a Self-led private practice.',
}

export const revalidate = 3600

export default async function ResourcesPage() {
  const posts = await getPosts(12)
  const [featured, ...rest] = posts

  return (
    <div className="pt-20 bg-cream-100">

      {/* Hero */}
      <section className="section-padding pb-10">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">Resources</p>
          <h1 className="font-serif text-5xl md:text-6xl text-sage-700 font-medium leading-tight mb-6">
            Thinking out loud about practice,{' '}
            <em className="italic" style={{ color: '#a75d90' }}>presence, and parts.</em>
          </h1>
          <p className="text-stone-500 text-lg leading-relaxed max-w-2xl mx-auto">
            Articles, guides, and honest reflections on building a private practice that feels aligned —
            written by a licensed therapist and a digital strategist who have been in the room.
          </p>
        </div>
      </section>

      {/* Featured post */}
      {featured && (
        <section className="pb-10">
          <div className="max-w-6xl mx-auto px-6">
            <div className="grid grid-cols-1 lg:grid-cols-2 rounded-3xl overflow-hidden border border-cream-300 shadow-sm">
              <div className="relative h-64 lg:h-auto min-h-[320px] bg-cream-200">
                {featured.featuredImage ? (
                  <Image
                    src={featured.featuredImage}
                    alt={featured.featuredImageAlt || featured.title}
                    fill
                    className="object-cover"
                    priority
                  />
                ) : (
                  <div className="absolute inset-0 bg-sage-100 flex items-center justify-center">
                    <span className="font-serif text-sage-300 text-2xl italic">Parts of Practice</span>
                  </div>
                )}
                <div className="absolute top-6 left-6">
                  <span className="bg-bark-500 text-white text-xs font-semibold uppercase tracking-widest px-4 py-2 rounded-full">
                    Featured
                  </span>
                </div>
              </div>
              <div className="bg-white p-10 lg:p-12 flex flex-col justify-center">
                {featured.categories.length > 0 && (
                  <span className="text-bark-500 text-xs font-semibold uppercase tracking-widest mb-3">
                    {featured.categories[0]}
                  </span>
                )}
                <h2 className="font-serif text-3xl text-sage-700 font-medium leading-snug mb-4">
                  {featured.title}
                </h2>
                <p className="text-stone-500 leading-relaxed mb-6">{featured.excerpt}</p>
                <div className="flex items-center justify-between">
                  <span className="text-stone-400 text-xs flex items-center gap-1.5">
                    <Calendar size={12} />
                    {formatDate(featured.date)}
                  </span>
                  <Link
                    href={`/resources/${featured.slug}`}
                    className="inline-flex items-center gap-2 bg-bark-500 text-white px-6 py-3 rounded-full text-sm font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group"
                  >
                    Read article <ArrowRight size={14} className="group-hover:translate-x-0.5 transition-transform" />
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </section>
      )}

      {/* Article grid */}
      {rest.length > 0 && (
        <section className="pb-24">
          <div className="max-w-6xl mx-auto px-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {rest.map(post => (
                <article
                  key={post.id}
                  className="group bg-white rounded-2xl border border-cream-300 overflow-hidden hover:border-sage-300 hover:shadow-md transition-all duration-300 flex flex-col"
                >
                  <div className="relative h-48 overflow-hidden bg-cream-200">
                    {post.featuredImage ? (
                      <Image
                        src={post.featuredImage}
                        alt={post.featuredImageAlt || post.title}
                        fill
                        className="object-cover group-hover:scale-105 transition-transform duration-500"
                      />
                    ) : (
                      <div className="absolute inset-0 bg-sage-50 flex items-center justify-center">
                        <span className="font-serif text-sage-300 italic text-sm">Parts of Practice</span>
                      </div>
                    )}
                  </div>
                  <div className="p-7 flex flex-col flex-1">
                    {post.categories.length > 0 && (
                      <span className="self-start text-xs font-semibold uppercase tracking-wider px-3 py-1 rounded-full mb-4 bg-cream-200 text-sage-600">
                        {post.categories[0]}
                      </span>
                    )}
                    <h3 className="font-serif text-lg text-sage-700 font-medium leading-snug mb-3 group-hover:text-sage-600 transition-colors">
                      {post.title}
                    </h3>
                    <p className="text-stone-500 text-sm leading-relaxed flex-1 mb-5">{post.excerpt}</p>
                    <div className="flex items-center justify-between pt-4 border-t border-cream-300">
                      <span className="text-stone-400 text-xs flex items-center gap-1.5">
                        <Calendar size={11} />
                        {formatDate(post.date)}
                      </span>
                      <Link
                        href={`/resources/${post.slug}`}
                        className="text-sage-600 text-sm font-medium hover:text-sage-800 transition-colors flex items-center gap-1"
                      >
                        Read <ArrowRight size={13} />
                      </Link>
                    </div>
                  </div>
                </article>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Empty state */}
      {posts.length === 0 && (
        <section className="pb-24">
          <div className="max-w-xl mx-auto px-6 text-center py-20">
            <p className="font-serif text-2xl text-sage-600 italic mb-4">Articles coming soon.</p>
            <p className="text-stone-500">Check back shortly — we&apos;re publishing regularly.</p>
          </div>
        </section>
      )}

      {/* Free guide CTA */}
      <section className="bg-sage-700 section-padding">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <div>
              <p className="text-bark-300 text-sm font-semibold uppercase tracking-widest mb-3">Free Resource</p>
              <h2 className="font-serif text-4xl text-cream-100 font-medium mb-5">
                Start with the Parts Mapping Starter Guide.
              </h2>
              <p className="text-cream-300 text-lg leading-relaxed">
                A free guide to help you map the internal voices that show up around practice-building —
                around money, visibility, boundaries, and growth.
              </p>
            </div>
            <div className="flex flex-col gap-4 lg:items-end">
              <Link
                href="/services/ifs-business-support#free-guide"
                className="inline-flex items-center justify-center gap-2 bg-bark-500 text-white px-9 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group w-full lg:w-auto"
              >
                Get the Free Guide <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
              </Link>
              <Link
                href="/contact"
                className="inline-flex items-center justify-center gap-2 border border-cream-400 text-cream-100 px-9 py-4 rounded-full text-base font-medium hover:bg-sage-600 transition-colors w-full lg:w-auto"
              >
                Book a Free Consultation
              </Link>
            </div>
          </div>
        </div>
      </section>

    </div>
  )
}
