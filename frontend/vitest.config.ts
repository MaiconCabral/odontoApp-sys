import { fileURLToPath } from 'node:url'
import { defineVitestConfig } from '@nuxt/test-utils/config'

export default defineVitestConfig({
  resolve: {
    alias: {
      'vue3-apexcharts': fileURLToPath(new URL('./tests/stubs/apexcharts.ts', import.meta.url)),
    },
  },
  test: {
    environment: 'nuxt',
    include: ['tests/**/*.test.ts'],
    setupFiles: ['tests/setup.ts'],
  },
})
