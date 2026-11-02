// https://nuxt.com/docs/api/configuration/nuxt-config
const clientOnly = { ssr: false }

export default defineNuxtConfig({
  modules: ['@nuxtjs/tailwindcss', '@nuxtjs/color-mode', '@pinia/nuxt'],
  compatibilityDate: '2025-07-15',
  app: {
    head: {
      htmlAttrs: { lang: 'pt-BR' },
      titleTemplate: '%s · sorriso',
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap',
        },
      ],
    },
  },
  colorMode: {
    classSuffix: '',
    preference: 'system',
    fallback: 'light',
  },
  devtools: { enabled: true },
  devServer: {
    host: '127.0.0.1',
    port: 3000,
  },
  imports: {
    dirs: ['services'],
  },
  // The panel and the auth screens depend on the session, so they render in the browser.
  // SSR stays on by default for future public pages.
  routeRules: {
    '/app/**': clientOnly,
    '/login': clientOnly,
    '/cadastro': clientOnly,
    '/recuperar-senha': clientOnly,
    '/redefinir-senha': clientOnly,
    '/agendar': clientOnly,
  },
  runtimeConfig: {
    public: {
      // {host} is replaced by the host in the browser, so each clinic calls its own subdomain.
      apiBase: 'http://{host}:8000',
      centralDomain: 'localhost',
    },
  },
  tailwindcss: {
    cssPath: '~/assets/css/main.css',
  },
  typescript: {
    strict: true,
    typeCheck: false,
  },
})
