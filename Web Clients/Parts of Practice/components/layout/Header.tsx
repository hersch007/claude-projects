'use client'

import Link from 'next/link'
import Image from 'next/image'
import { useState, useEffect } from 'react'
import { Menu, X } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'
import { cn } from '@/lib/utils'

const navLinks = [
  { href: '/about', label: 'About' },
  { href: '/services', label: 'Services' },
  { href: '/how-we-work', label: 'How We Work' },
  { href: '/resources', label: 'Resources' },
]

export default function Header() {
  const [menuOpen, setMenuOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20)
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  return (
    <header
      className={cn(
        'fixed top-0 left-0 right-0 z-50 transition-all duration-300',
        scrolled
          ? 'bg-cream-100/96 backdrop-blur-md shadow-sm border-b border-cream-300'
          : 'bg-cream-100/90 backdrop-blur-sm'
      )}
    >
      <div className="max-w-6xl mx-auto px-6 h-18 flex items-center justify-between">
        {/* Logo */}
        <Link href="/" className="flex items-center gap-3 group" aria-label="Parts of Practice home">
          <Image
            src="/logo.png"
            alt="Parts of Practice logo"
            width={40}
            height={40}
            className="flex-shrink-0"
            priority
          />
          <span className="font-serif text-lg text-sage-700 font-semibold tracking-tight leading-tight">
            Parts of Practice
          </span>
        </Link>

        {/* Desktop nav */}
        <nav className="hidden md:flex items-center gap-7" aria-label="Main navigation">
          {navLinks.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className="text-sm text-stone-600 hover:text-sage-600 transition-colors duration-200 font-medium"
            >
              {link.label}
            </Link>
          ))}
          <Link
            href="/contact"
            className="text-sm bg-bark-500 text-white px-5 py-2.5 rounded-full hover:bg-bark-600 transition-colors duration-200 font-medium"
          >
            Free Consultation
          </Link>
        </nav>

        {/* Mobile menu toggle */}
        <button
          className="md:hidden text-stone-600 hover:text-sage-600 transition-colors p-1"
          onClick={() => setMenuOpen(!menuOpen)}
          aria-label={menuOpen ? 'Close menu' : 'Open menu'}
          aria-expanded={menuOpen}
        >
          {menuOpen ? <X size={22} strokeWidth={1.5} /> : <Menu size={22} strokeWidth={1.5} />}
        </button>
      </div>

      {/* Mobile menu */}
      <AnimatePresence>
        {menuOpen && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            transition={{ duration: 0.25, ease: 'easeInOut' }}
            className="md:hidden overflow-hidden bg-cream-100 border-t border-cream-300"
          >
            <nav className="flex flex-col px-6 pt-4 pb-6 gap-1" aria-label="Mobile navigation">
              {navLinks.map((link) => (
                <Link
                  key={link.href}
                  href={link.href}
                  className="text-stone-600 hover:text-sage-600 py-2.5 text-base border-b border-cream-300 last:border-0 transition-colors"
                  onClick={() => setMenuOpen(false)}
                >
                  {link.label}
                </Link>
              ))}
              <Link
                href="/contact"
                className="mt-4 bg-bark-500 text-white text-center py-3 rounded-full hover:bg-bark-600 transition-colors font-medium"
                onClick={() => setMenuOpen(false)}
              >
                Free Consultation
              </Link>
            </nav>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  )
}
