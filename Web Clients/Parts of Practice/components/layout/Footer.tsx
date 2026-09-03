import Link from 'next/link'

const serviceLinks = [
  { href: '/services/website-design', label: 'Custom Website Design' },
  { href: '/services/seo', label: 'SEO for Therapists' },
  { href: '/services/copywriting', label: 'Ethical Copywriting' },
  { href: '/services/ifs-business-support', label: 'IFS Business Support' },
]

const companyLinks = [
  { href: '/about', label: 'About Us' },
  { href: '/how-we-work', label: 'How We Work' },
  { href: '/resources', label: 'Resources' },
  { href: '/contact', label: 'Contact' },
]

export default function Footer() {
  return (
    <footer className="bg-sage-800 text-cream-200 mt-0">
      <div className="max-w-6xl mx-auto px-6 py-16">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-10 mb-14">
          {/* Brand */}
          <div className="md:col-span-2">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-8 h-8 rounded-full bg-sage-500 flex items-center justify-center flex-shrink-0">
                <div className="w-3.5 h-3.5 rounded-full border-2 border-cream-100" />
              </div>
              <span className="font-serif text-lg text-cream-100 font-semibold">Parts of Practice</span>
            </div>
            <p className="text-cream-300 text-sm leading-relaxed max-w-xs">
              Custom website design, SEO, and ethical copywriting for IFS-informed therapists building
              Self-led private practices. Built by therapists, for therapists.
            </p>
            <p className="mt-5 text-cream-400 text-xs">
              Stephanie Brashear, LPCC-S, LMHC &amp; Richard
            </p>
          </div>

          {/* Services */}
          <div>
            <h3 className="text-cream-100 font-semibold text-sm uppercase tracking-widest mb-4">Services</h3>
            <ul className="space-y-2.5">
              {serviceLinks.map((link) => (
                <li key={link.href}>
                  <Link
                    href={link.href}
                    className="text-cream-300 hover:text-cream-100 text-sm transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Company */}
          <div>
            <h3 className="text-cream-100 font-semibold text-sm uppercase tracking-widest mb-4">Company</h3>
            <ul className="space-y-2.5">
              {companyLinks.map((link) => (
                <li key={link.href}>
                  <Link
                    href={link.href}
                    className="text-cream-300 hover:text-cream-100 text-sm transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="border-t border-sage-700 pt-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <p className="text-cream-400 text-xs">
            &copy; {new Date().getFullYear()} Parts of Practice. All rights reserved.
          </p>
          <div className="flex gap-6">
            <Link href="/privacy" className="text-cream-400 hover:text-cream-100 text-xs transition-colors">
              Privacy Policy
            </Link>
            <Link href="/terms" className="text-cream-400 hover:text-cream-100 text-xs transition-colors">
              Terms of Service
            </Link>
          </div>
        </div>
      </div>
    </footer>
  )
}
