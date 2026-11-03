import type { Config } from 'tailwindcss'

export default {
  darkMode: 'class',
  content: [
    './components/**/*.{vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './composables/**/*.ts',
    './app.vue',
  ],
  theme: {
    // Same breakpoints as the reference layout (560px, 820px and 1100px).
    screens: {
      sm: '561px',
      md: '821px',
      lg: '1101px',
    },
    extend: {
      colors: {
        canvas: 'var(--color-canvas)',
        surface: {
          DEFAULT: 'var(--color-surface)',
          soft: 'var(--color-surface-soft)',
        },
        line: {
          DEFAULT: 'var(--color-line)',
          soft: 'var(--color-line-soft)',
        },
        ink: 'var(--color-ink)',
        heading: 'var(--color-heading)',
        body: 'var(--color-body)',
        muted: 'var(--color-muted)',
        faint: 'var(--color-faint)',
        brand: {
          // Sky 700: white text on it reaches 5.9:1 (WCAG AA). Sky 600 stayed at 4.1:1.
          DEFAULT: '#0369a1',
          hover: '#075985',
          accent: '#0ea5e9',
          text: 'var(--color-brand-text)',
          tint: 'var(--color-brand-tint)',
          soft: 'var(--color-brand-soft)',
          line: 'var(--color-brand-line)',
        },
        // Status colors that follow the theme: bg-tone-green-bg, text-tone-green-text...
        tone: Object.fromEntries(['green', 'amber', 'red', 'violet', 'pink'].map(tone => [tone, {
          bg: `var(--tone-${tone}-bg)`,
          text: `var(--tone-${tone}-text)`,
          line: `var(--tone-${tone}-line)`,
          solid: `var(--tone-${tone}-solid)`,
        }])),
      },
      fontFamily: {
        sans: ['"DM Sans"', 'sans-serif'],
        display: ['Manrope', 'sans-serif'],
      },
    },
  },
  plugins: [],
} satisfies Config
