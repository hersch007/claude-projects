import type { Metadata } from 'next'
import Link from 'next/link'

export const metadata: Metadata = {
  title: 'Privacy Policy',
  description: 'How Parts of Practice collects, uses, and protects your personal information.',
  alternates: { canonical: 'https://partsofpractice.com/privacy' },
  robots: { index: false, follow: false },
}

const lastUpdated = 'June 2026'

export default function PrivacyPage() {
  return (
    <div className="pt-20 bg-cream-100">
      <div className="max-w-3xl mx-auto px-6 py-16">

        <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">Legal</p>
        <h1 className="font-serif text-4xl md:text-5xl text-sage-700 font-medium leading-tight mb-4">
          Privacy Policy
        </h1>
        <p className="text-stone-400 text-sm mb-12">Last updated: {lastUpdated}</p>

        <div className="space-y-10 text-stone-500 leading-relaxed">

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Who we are</h2>
            <p>
              Parts of Practice is a website design, SEO, and copywriting studio serving IFS-informed
              therapists in private practice. We are owned and operated by Stephanie Brashear and
              Richard Brashear. Our website is <strong className="text-sage-700 font-medium">partsofpractice.com</strong>.
            </p>
            <p className="mt-3">
              Questions about this policy? Email us at{' '}
              <a href="mailto:contact@partsofpractice.com" className="text-bark-500 hover:underline">
                contact@partsofpractice.com
              </a>
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">What information we collect</h2>
            <p>We collect information in the following ways:</p>
            <ul className="list-disc pl-6 space-y-3 mt-4">
              <li>
                <strong className="text-sage-700 font-medium">Contact form submissions</strong> — your name,
                email address, and any message you choose to send us.
              </li>
              <li>
                <strong className="text-sage-700 font-medium">Consultation bookings</strong> — when you
                book a free consultation via Calendly, Calendly collects your name, email, and scheduling
                preferences under their own privacy policy.
              </li>
              <li>
                <strong className="text-sage-700 font-medium">Website analytics</strong> — we may collect
                anonymous usage data (pages visited, time on site, browser type) to understand how our
                site is used. We do not use tools that track you across other websites.
              </li>
              <li>
                <strong className="text-sage-700 font-medium">Cookies</strong> — our site uses only
                essential cookies required for the site to function. We do not use advertising or
                tracking cookies.
              </li>
            </ul>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">How we use your information</h2>
            <p>We use the information you provide solely to:</p>
            <ul className="list-disc pl-6 space-y-2 mt-4">
              <li>Respond to your inquiries and messages</li>
              <li>Schedule and conduct free consultations</li>
              <li>Deliver the services you have engaged us for</li>
              <li>Send occasional updates relevant to your project (with your consent)</li>
              <li>Improve our website and service offerings</li>
            </ul>
            <p className="mt-5 bg-cream-200 rounded-2xl border border-cream-300 p-5 font-medium text-sage-700">
              We will never sell, rent, or share your personal information with third parties for
              marketing purposes.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Third-party services</h2>
            <p>Our website interacts with the following third-party services:</p>
            <ul className="list-disc pl-6 space-y-3 mt-4">
              <li>
                <strong className="text-sage-700 font-medium">Calendly</strong> — for booking free
                consultations. Calendly processes scheduling data under their own privacy policy.
              </li>
              <li>
                <strong className="text-sage-700 font-medium">WordPress</strong> — our blog content is
                served from a WordPress installation. No personal visitor data is passed through
                the public blog feed.
              </li>
              <li>
                <strong className="text-sage-700 font-medium">Google Fonts</strong> — our site loads
                fonts from Google&apos;s servers, which may log your IP address as part of the request.
              </li>
            </ul>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Data retention</h2>
            <p>
              We retain contact form submissions and consultation records for as long as necessary to
              provide services or respond to your inquiry. If you would like your information removed,
              email us at{' '}
              <a href="mailto:contact@partsofpractice.com" className="text-bark-500 hover:underline">
                contact@partsofpractice.com
              </a>{' '}
              and we will delete it promptly.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Your rights</h2>
            <p>You have the right to:</p>
            <ul className="list-disc pl-6 space-y-2 mt-4">
              <li>Request a copy of the personal data we hold about you</li>
              <li>Request correction of inaccurate data</li>
              <li>Request deletion of your data</li>
              <li>Opt out of any communications at any time</li>
            </ul>
            <p className="mt-4">
              To exercise any of these rights, contact us at{' '}
              <a href="mailto:contact@partsofpractice.com" className="text-bark-500 hover:underline">
                contact@partsofpractice.com
              </a>.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Children&apos;s privacy</h2>
            <p>
              Our services are intended for licensed mental health professionals and business owners.
              We do not knowingly collect personal information from anyone under the age of 18.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Changes to this policy</h2>
            <p>
              We may update this policy from time to time. When we do, we will revise the
              &ldquo;last updated&rdquo; date at the top of this page.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Contact us</h2>
            <div className="bg-cream-200 rounded-2xl border border-cream-300 p-6 mt-2">
              <p className="font-medium text-sage-700">Parts of Practice</p>
              <p className="mt-2">
                <a href="mailto:contact@partsofpractice.com" className="text-bark-500 hover:underline">
                  contact@partsofpractice.com
                </a>
              </p>
              <p className="mt-1">
                <Link href="/contact" className="text-bark-500 hover:underline">
                  partsofpractice.com/contact
                </Link>
              </p>
            </div>
          </section>

        </div>
      </div>
    </div>
  )
}
