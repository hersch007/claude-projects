'use client'

import { useState } from 'react'
import { ArrowRight } from 'lucide-react'

type FormState = 'idle' | 'submitting' | 'success' | 'error'

export default function ContactForm() {
  const [state, setState] = useState<FormState>('idle')
  const [form, setForm] = useState({
    firstName: '',
    lastName: '',
    email: '',
    service: '',
    message: '',
  })

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setState('submitting')
    // TODO: wire to email service (Resend, Formspree, etc.)
    await new Promise(r => setTimeout(r, 1200))
    setState('success')
  }

  if (state === 'success') {
    return (
      <div className="text-center py-12">
        <div className="w-14 h-14 rounded-full bg-bark-100 flex items-center justify-center mx-auto mb-5">
          <span className="text-bark-500 text-2xl">✓</span>
        </div>
        <h3 className="font-serif text-2xl text-sage-700 font-medium mb-3">Message received.</h3>
        <p className="text-stone-500 leading-relaxed max-w-sm mx-auto">
          Thank you for reaching out. Stephanie or Richard will be in touch within one business day.
        </p>
      </div>
    )
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label htmlFor="firstName" className="block text-sm font-medium text-sage-700 mb-1.5">
            First name <span className="text-bark-400">*</span>
          </label>
          <input
            id="firstName"
            name="firstName"
            type="text"
            required
            value={form.firstName}
            onChange={handleChange}
            className="w-full px-4 py-3 rounded-xl border border-cream-300 bg-cream-100 text-stone-600 text-sm placeholder:text-stone-300 focus:outline-none focus:ring-2 focus:ring-bark-300 focus:border-bark-400 transition"
            placeholder="Stephanie"
          />
        </div>
        <div>
          <label htmlFor="lastName" className="block text-sm font-medium text-sage-700 mb-1.5">
            Last name <span className="text-bark-400">*</span>
          </label>
          <input
            id="lastName"
            name="lastName"
            type="text"
            required
            value={form.lastName}
            onChange={handleChange}
            className="w-full px-4 py-3 rounded-xl border border-cream-300 bg-cream-100 text-stone-600 text-sm placeholder:text-stone-300 focus:outline-none focus:ring-2 focus:ring-bark-300 focus:border-bark-400 transition"
            placeholder="Brashear"
          />
        </div>
      </div>

      <div>
        <label htmlFor="email" className="block text-sm font-medium text-sage-700 mb-1.5">
          Email address <span className="text-bark-400">*</span>
        </label>
        <input
          id="email"
          name="email"
          type="email"
          required
          value={form.email}
          onChange={handleChange}
          className="w-full px-4 py-3 rounded-xl border border-cream-300 bg-cream-100 text-stone-600 text-sm placeholder:text-stone-300 focus:outline-none focus:ring-2 focus:ring-bark-300 focus:border-bark-400 transition"
          placeholder="you@yourpractice.com"
        />
      </div>

      <div>
        <label htmlFor="service" className="block text-sm font-medium text-sage-700 mb-1.5">
          What are you interested in?
        </label>
        <select
          id="service"
          name="service"
          value={form.service}
          onChange={handleChange}
          className="w-full px-4 py-3 rounded-xl border border-cream-300 bg-cream-100 text-stone-600 text-sm focus:outline-none focus:ring-2 focus:ring-bark-300 focus:border-bark-400 transition"
        >
          <option value="">Select a service (optional)</option>
          <option value="website-design">Website Design</option>
          <option value="seo">SEO for Therapists</option>
          <option value="copywriting">Copywriting</option>
          <option value="ifs-support">IFS Business Support</option>
          <option value="multiple">Multiple services</option>
          <option value="not-sure">Not sure yet</option>
        </select>
      </div>

      <div>
        <label htmlFor="message" className="block text-sm font-medium text-sage-700 mb-1.5">
          Tell us about your practice <span className="text-bark-400">*</span>
        </label>
        <textarea
          id="message"
          name="message"
          required
          rows={5}
          value={form.message}
          onChange={handleChange}
          className="w-full px-4 py-3 rounded-xl border border-cream-300 bg-cream-100 text-stone-600 text-sm placeholder:text-stone-300 focus:outline-none focus:ring-2 focus:ring-bark-300 focus:border-bark-400 transition resize-none"
          placeholder="Share whatever feels relevant — where you are in your practice, what you're hoping to build, any parts that might have something to say about it..."
        />
      </div>

      <p className="text-stone-400 text-xs">
        No spam, ever. Your information is only used to respond to your inquiry.
      </p>

      <button
        type="submit"
        disabled={state === 'submitting'}
        className="inline-flex items-center gap-2 bg-bark-500 text-white px-8 py-4 rounded-full text-base font-medium hover:bg-bark-600 transition-all duration-200 hover:gap-3 group disabled:opacity-60 disabled:cursor-not-allowed w-full justify-center"
      >
        {state === 'submitting' ? 'Sending...' : 'Send Message'}
        {state !== 'submitting' && (
          <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform" />
        )}
      </button>
    </form>
  )
}
