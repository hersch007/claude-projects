import type { Metadata } from 'next'
import Image from 'next/image'
import Link from 'next/link'
import { notFound } from 'next/navigation'
import { ArrowLeft, Calendar } from 'lucide-react'
import { getPost, getPostSlugs, formatDate } from '@/lib/wordpress'

export const revalidate = 3600

export async function generateStaticParams() {
  const slugs = await getPostSlugs()
  return slugs.map(slug => ({ slug }))
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params
  const post = await getPost(slug)
  if (!post) return { title: 'Article Not Found' }
  return {
    title: post.title,
    description: post.excerpt,
    openGraph: {
      title: post.title,
      description: post.excerpt,
      images: post.featuredImage ? [post.featuredImage] : [],
    },
  }
}

export default async function PostPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params
  const post = await getPost(slug)
  if (!post) notFound()

  return (
    <div className="pt-20 bg-cream-100">

      {/* Back link */}
      <div className="max-w-3xl mx-auto px-6 pt-8">
        <Link
          href="/resources"
          className="inline-flex items-center gap-2 text-stone-400 hover:text-sage-600 transition-colors text-sm"
        >
          <ArrowLeft size={14} /> Back to Resources
        </Link>
      </div>

      {/* Header */}
      <section className="max-w-3xl mx-auto px-6 py-10">
        {post.categories.length > 0 && (
          <span className="text-bark-500 text-xs font-semibold uppercase tracking-widest mb-4 block">
            {post.categories[0]}
          </span>
        )}
        <h1 className="font-serif text-4xl md:text-5xl text-sage-700 font-medium leading-tight mb-6">
          {post.title}
        </h1>
        <div className="flex items-center gap-2 text-stone-400 text-sm mb-8">
          <Calendar size={14} />
          <span>{formatDate(post.date)}</span>
          <span className="mx-2">·</span>
          <span>Parts of Practice</span>
        </div>
      </section>

      {/* Featured image */}
      {post.featuredImage && (
        <div className="max-w-4xl mx-auto px-6 mb-12">
          <div className="relative rounded-3xl overflow-hidden shadow-lg aspect-video">
            <Image
              src={post.featuredImage}
              alt={post.featuredImageAlt || post.title}
              fill
              className="object-cover"
              priority
            />
          </div>
        </div>
      )}

      {/* Content */}
      <article className="max-w-3xl mx-auto px-6 pb-20">
        <div
          className="prose prose-lg prose-stone max-w-none
            prose-headings:font-serif prose-headings:text-sage-700 prose-headings:font-medium
            prose-p:text-stone-500 prose-p:leading-relaxed
            prose-a:text-bark-500 prose-a:no-underline hover:prose-a:underline
            prose-strong:text-sage-700
            prose-li:text-stone-500
            prose-blockquote:border-l-bark-400 prose-blockquote:text-stone-500 prose-blockquote:italic"
          dangerouslySetInnerHTML={{ __html: post.content }}
        />

        {/* CTA at bottom of post */}
        <div className="mt-16 bg-cream-200 rounded-3xl border border-cream-300 p-10 text-center">
          <p className="text-bark-500 text-xs font-semibold uppercase tracking-widest mb-3">Ready to talk?</p>
          <h2 className="font-serif text-3xl text-sage-700 font-medium mb-4">
            Let&apos;s talk about your practice.
          </h2>
          <p className="text-stone-500 mb-7 leading-relaxed max-w-md mx-auto">
            A free 30-minute consultation with Stephanie and Richard. No pressure — just a genuine conversation.
          </p>
          <Link
            href="/contact"
            className="inline-flex items-center gap-2 bg-bark-500 text-white px-8 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200"
          >
            Book a Free Consultation
          </Link>
        </div>
      </article>

    </div>
  )
}
