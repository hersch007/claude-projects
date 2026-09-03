import type { Metadata } from 'next'
import Link from 'next/link'

export const metadata: Metadata = {
  title: 'Terms of Service',
  description: 'Terms and conditions for using Parts of Practice services.',
  alternates: { canonical: 'https://partsofpractice.com/terms' },
  robots: { index: false, follow: false },
}

const lastUpdated = 'June 2026'

export default function TermsPage() {
  return (
    <div className="pt-20 bg-cream-100">
      <div className="max-w-3xl mx-auto px-6 py-16">

        <p className="text-bark-500 text-sm font-semibold uppercase tracking-widest mb-4">Legal</p>
        <h1 className="font-serif text-4xl md:text-5xl text-sage-700 font-medium leading-tight mb-4">
          Terms of Service
        </h1>
        <p className="text-stone-400 text-sm mb-12">Last updated: {lastUpdated}</p>

        <div className="space-y-10 text-stone-500 leading-relaxed">

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Agreement to terms</h2>
            <p>
              By accessing or using the Parts of Practice website at partsofpractice.com, or by
              engaging our services, you agree to be bound by these Terms of Service. If you do not
              agree, please do not use our site or services.
            </p>
            <p className="mt-3">
              Parts of Practice is owned and operated by Stephanie Brashear and Richard Brashear.
              Questions? Email{' '}
              <a href="mailto:contact@partsofpractice.com" className="text-bark-500 hover:underline">
                contact@partsofpractice.com
              </a>.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Our services</h2>
            <p>
              Parts of Practice provides website design, search engine optimization (SEO), copywriting,
              and IFS-informed business coaching for therapists in private practice. The scope, pricing,
              timeline, and deliverables for each engagement are agreed upon in writing prior to
              commencement of work.
            </p>
            <p className="mt-3">
              We reserve the right to decline any project at our discretion, including projects that
              conflict with our values or fall outside our areas of expertise.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Payment and billing</h2>
            <ul className="list-disc pl-6 space-y-3 mt-2">
              <li>
                Monthly service plans are billed in advance on a recurring basis. You may cancel
                at any time with 30 days written notice.
              </li>
              <li>
                Project-based work (copywriting, one-time design) may require a deposit before work
                begins, with the remainder due upon completion.
              </li>
              <li>
                All prices are in US Dollars. We reserve the right to adjust pricing with 30 days
                notice to existing clients.
              </li>
              <li>
                Overdue invoices beyond 30 days may result in a pause or suspension of services.
              </li>
            </ul>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Client responsibilities</h2>
            <p>To allow us to deliver quality work, clients agree to:</p>
            <ul className="list-disc pl-6 space-y-2 mt-4">
              <li>Provide timely feedback and approvals when requested</li>
              <li>Supply accurate information about their practice, credentials, and offerings</li>
              <li>Ensure they hold the rights to any images, logos, or content they provide to us</li>
              <li>
                Notify us promptly of any changes to their practice that affect the services we provide
              </li>
            </ul>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Intellectual property</h2>
            <p>
              Upon receipt of final payment, clients receive full ownership of all original content
              and design assets created specifically for their project. Parts of Practice retains the
              right to display completed work in our portfolio unless the client requests otherwise
              in writing.
            </p>
            <p className="mt-3">
              All content on the Parts of Practice website — including text, images, design, and code —
              is our property and may not be reproduced without written permission.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Confidentiality</h2>
            <p>
              We treat all client information as confidential. We will not share details about your
              practice, your clients, or your business with any third party without your consent,
              except as required by law.
            </p>
            <p className="mt-3">
              As a courtesy to our clientele of licensed mental health professionals, we are mindful
              of the sensitivity of the work you do and the populations you serve.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Limitation of liability</h2>
            <p>
              Parts of Practice provides services in good faith based on best practices in web design,
              SEO, and copywriting. We cannot guarantee specific outcomes such as search ranking
              positions, website traffic levels, or client acquisition rates, as these depend on many
              factors outside our control.
            </p>
            <p className="mt-3">
              To the fullest extent permitted by law, our total liability for any claim arising from
              our services is limited to the amount you paid us in the 90 days preceding the claim.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Not a substitute for professional advice</h2>
            <p>
              The content on this website — including blog posts, guides, and resources — is for
              informational purposes only. It does not constitute legal, financial, or clinical advice.
              IFS-informed business coaching provided by Parts of Practice is coaching, not therapy,
              and does not create a therapeutic relationship.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Termination</h2>
            <p>
              Either party may terminate an ongoing service agreement with 30 days written notice.
              We reserve the right to terminate immediately in cases of non-payment, abusive conduct,
              or requests that conflict with our values.
            </p>
            <p className="mt-3">
              Upon termination, we will deliver all completed work product and any assets belonging
              to the client. No refund will be issued for work already completed.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Governing law</h2>
            <p>
              These terms are governed by the laws of the State of North Carolina, United States,
              without regard to conflict of law provisions.
            </p>
          </section>

          <div className="border-t border-cream-300" />

          <section>
            <h2 className="font-serif text-2xl text-sage-700 font-medium mb-4">Changes to these terms</h2>
            <p>
              We may update these terms from time to time. Continued use of our services after
              changes are posted constitutes acceptance of the revised terms. The &ldquo;last
              updated&rdquo; date at the top of this page will reflect any changes.
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
