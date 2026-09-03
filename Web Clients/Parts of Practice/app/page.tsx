import type { Metadata } from 'next'
import Hero from '@/components/sections/Hero'
import MeetTheTeam from '@/components/sections/MeetTheTeam'
import ServicesOverview from '@/components/sections/ServicesOverview'
import Differentiators from '@/components/sections/Differentiators'
import ProcessPreview from '@/components/sections/ProcessPreview'
import Testimonials from '@/components/sections/Testimonials'
import BlogPreview from '@/components/sections/BlogPreview'
import HomeCTA from '@/components/sections/HomeCTA'

export const metadata: Metadata = {
  title: 'Parts of Practice | IFS-Informed Website Design & SEO for Therapists',
  description:
    'Custom website design, SEO for therapists, and ethical copywriting for IFS-informed private practices. No templates — just alignment. Built by a therapist, for therapists.',
  alternates: {
    canonical: 'https://partsofpractice.com',
  },
}

export default function HomePage() {
  return (
    <>
      <Hero />
      <MeetTheTeam />
      <ServicesOverview />
      <Differentiators />
      <ProcessPreview />
      <Testimonials />
      <BlogPreview />
      <HomeCTA />
    </>
  )
}
