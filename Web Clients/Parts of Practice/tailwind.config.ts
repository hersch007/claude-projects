import type { Config } from 'tailwindcss'
import typography from '@tailwindcss/typography'

// Brand colors from Parts of Practice Canva brand kit
// Primary: #2C3E50 | Accent: #a75d90 | BG: #F8F5F2 | Earth: #A47551 | Neutral: #D4CFC5 | Text: #4A4A4A

const config: Config = {
  content: [
    './pages/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}',
    './app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        // Primary scale — built from brand #2C3E50 (dark navy-slate)
        sage: {
          50:  '#f0f2f5',
          100: '#d8dfe8',
          200: '#b0bfcc',
          300: '#849eb3',
          400: '#5c7d97',
          500: '#3d6178',
          600: '#2C3E50', // exact brand color
          700: '#1e2d3a',
          800: '#141e27',
          900: '#0a1119',
        },
        // Background scale — built from brand #F8F5F2 (warm cream)
        cream: {
          50:  '#fdfcfb',
          100: '#F8F5F2', // exact brand color — main background
          200: '#edeae5',
          300: '#D4CFC5', // exact brand color — light neutral
          400: '#bdb8ae',
          500: '#a8a399',
        },
        // Earth/warm accent — built from brand #A47551
        bark: {
          100: '#f6ede4',
          200: '#e8d0b8',
          300: '#d4af8a',
          400: '#bb9068',
          500: '#A47551', // exact brand color
          600: '#7d5a3e',
          700: '#5c412c',
          800: '#3d2b1d',
        },
        // Mauve/purple accent — built from brand #a75d90
        mauve: {
          50:  '#fdf0f8',
          100: '#f5d5ec',
          200: '#e8aad3',
          300: '#d47db8',
          400: '#be559f',
          500: '#a75d90', // exact brand color
          600: '#874973',
          700: '#663758',
          800: '#45253b',
        },
        // Text / neutral — built from brand #4A4A4A
        stone: {
          100: '#f2f2f2',
          200: '#d9d9d9',
          300: '#b5b5b5',
          400: '#8f8f8f',
          500: '#6b6b6b',
          600: '#4A4A4A', // exact brand color — body text
          700: '#333333',
          800: '#1a1a1a',
        },
      },
      fontFamily: {
        serif: ['var(--font-playfair)', 'Georgia', 'serif'],
        sans:  ['var(--font-inter)', 'system-ui', 'sans-serif'],
      },
      maxWidth: {
        '8xl': '88rem',
      },
      spacing: {
        '18': '4.5rem',
        '22': '5.5rem',
      },
    },
  },
  plugins: [typography],
}

export default config
