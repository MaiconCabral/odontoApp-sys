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
          DEFAULT: '#0b8cad',
          hover: '#087993',
          text: '#0785a5',
        },
      },
      fontFamily: {
        sans: ['"DM Sans"', 'sans-serif'],
        display: ['Manrope', 'sans-serif'],
      },
    },
  },
  plugins: [],
} satisfies Config
