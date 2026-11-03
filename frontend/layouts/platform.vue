<script setup lang="ts">
import { LogOut, Moon, Sun } from 'lucide-vue-next'

const route = useRoute()
const { admin, logout } = usePlatformSession()
const { isDark, toggleTheme } = useTheme()

const links = [
  { to: '/admin', label: 'Clínicas' },
  { to: '/admin/planos', label: 'Planos' },
]

// The clinics entry also covers the page of one clinic.
function isCurrent(path: string) {
  return path === '/admin' ? route.path === '/admin' || route.path.startsWith('/admin/clinicas') : route.path.startsWith(path)
}

const iconButtonClass = 'grid h-[34px] w-[34px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text'
</script>

<template>
  <div class="min-h-screen bg-canvas text-ink">
    <header class="flex h-[65px] items-center justify-between border-b border-line bg-surface px-[18px] md:px-10">
      <div class="flex items-center gap-4">
        <AppLogo />
        <span class="rounded-md bg-brand-soft px-2 py-1 text-[10px] font-bold uppercase tracking-[.06em] text-brand-text">Plataforma</span>
        <nav class="flex gap-1" aria-label="Painel global">
          <NuxtLink
            v-for="link in links"
            :key="link.to"
            :to="link.to"
            class="rounded-lg px-3 py-2 text-[12px] font-bold"
            :class="isCurrent(link.to) ? 'bg-brand-soft text-brand-text' : 'text-muted hover:text-body'"
            :aria-current="isCurrent(link.to) ? 'page' : undefined"
          >
            {{ link.label }}
          </NuxtLink>
        </nav>
      </div>
      <div class="flex items-center gap-2 text-[11px] text-muted">
        <span class="hidden md:inline">{{ admin?.name }}</span>
        <button type="button" :class="iconButtonClass" aria-label="Alternar tema" @click="toggleTheme">
          <Sun v-if="isDark" :size="18" />
          <Moon v-else :size="18" />
        </button>
        <button type="button" :class="iconButtonClass" aria-label="Sair" @click="logout">
          <LogOut :size="18" />
        </button>
      </div>
    </header>

    <main class="mx-auto max-w-[1100px] px-[18px] py-7 md:px-10">
      <slot />
    </main>

    <AppToast />
  </div>
</template>
