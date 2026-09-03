import type { Metadata } from 'next'
import { Inter, Playfair_Display } from 'next/font/google'
import Script from 'next/script'
import './globals.css'
import Header from '@/components/layout/Header'
import Footer from '@/components/layout/Footer'

const inter = Inter({
  subsets: ['latin'],
  variable: '--font-inter',
  display: 'swap',
})

const playfair = Playfair_Display({
  subsets: ['latin'],
  variable: '--font-playfair',
  display: 'swap',
  weight: ['400', '500', '600', '700'],
  style: ['normal', 'italic'],
})

export const metadata: Metadata = {
  metadataBase: new URL('https://partsofpractice.com'),
  title: {
    default: 'Parts of Practice | IFS-Informed Website Design & SEO for Therapists',
    template: '%s | Parts of Practice',
  },
  description:
    'Custom website design, SEO, and ethical copywriting for IFS-informed therapists building Self-led private practices. Built by a therapist and a digital strategist — for therapists.',
  keywords: [
    'therapist website design',
    'SEO for therapists',
    'IFS informed marketing',
    'private practice website',
    'therapist marketing',
    'IFS therapist website',
    'website design for therapists',
    'parts-aware practice building',
    'therapist copywriting',
    'private practice SEO',
    'IFS business support',
    'therapy practice website',
  ],
  authors: [{ name: 'Parts of Practice' }],
  alternates: {
    canonical: 'https://partsofpractice.com',
  },
  openGraph: {
    type: 'website',
    locale: 'en_US',
    url: 'https://partsofpractice.com',
    siteName: 'Parts of Practice',
    title: 'Parts of Practice | IFS-Informed Website Design & SEO for Therapists',
    description:
      'Custom website design, SEO, and ethical copywriting for IFS-informed therapists. No templates — just alignment.',
    images: [
      {
        url: '/og-image.svg',
        width: 1200,
        height: 630,
        alt: 'Parts of Practice — IFS-Informed Website Design & SEO for Therapists',
      },
    ],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Parts of Practice | IFS-Informed Website Design & SEO for Therapists',
    description:
      'Custom website design, SEO, and ethical copywriting for IFS-informed therapists. No templates — just alignment.',
    images: ['/og-image.svg'],
  },
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      'max-image-preview': 'large',
      'max-snippet': -1,
    },
  },
}

const localBusinessSchema = {
  '@context': 'https://schema.org',
  '@type': 'ProfessionalService',
  name: 'Parts of Practice',
  description:
    'IFS-informed website design, SEO, and copywriting for therapists building Self-led private practices.',
  url: 'https://partsofpractice.com',
  logo: 'https://partsofpractice.com/logo.png',
  founder: [
    {
      '@type': 'Person',
      name: 'Stephanie Brashear',
      jobTitle: 'Co-Founder, Clinical Copywriter & IFS Business Coach',
      hasCredential: ['LPCC-S', 'LMHC'],
    },
    {
      '@type': 'Person',
      name: 'Richard Brashear',
      jobTitle: 'Co-Founder, Digital Strategist & Technical Director',
    },
  ],
  serviceType: [
    'Therapist Website Design',
    'SEO for Therapists',
    'Therapist Copywriting',
    'IFS-Informed Business Coaching',
  ],
  areaServed: {
    '@type': 'Country',
    name: 'United States',
  },
  sameAs: ['https://partsofpractice.com'],
  contactPoint: {
    '@type': 'ContactPoint',
    contactType: 'customer service',
    url: 'https://partsofpractice.com/contact',
  },
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" className={`${inter.variable} ${playfair.variable}`}>
      <head>
        <Script
          id="local-business-schema"
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(localBusinessSchema) }}
        />
      </head>
      <body className="bg-cream-100 text-stone-600 font-sans antialiased">
        <Header />
        <main>{children}</main>
        <Footer />
      </body>
    </html>
  )
}
