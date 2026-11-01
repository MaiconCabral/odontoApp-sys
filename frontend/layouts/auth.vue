<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next'

const { isDark, toggleTheme } = useTheme()
</script>

<template>
  <div class="flex min-h-screen bg-canvas text-ink">
    <div class="flex min-w-0 flex-1 flex-col px-5 py-5 md:px-10 md:py-7">
      <header class="flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <AppLogo />
        </div>
        <button
          type="button"
          class="grid h-10 w-10 place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text"
          aria-label="Alternar tema"
          @click="toggleTheme"
        >
          <Sun v-if="isDark" :size="18" />
          <Moon v-else :size="18" />
        </button>
      </header>

      <main class="mx-auto my-auto w-full max-w-[400px] py-10">
        <slot />
      </main>
    </div>

    <aside class="hidden w-[44%] max-w-[640px] flex-none flex-col justify-between bg-[#082f49] p-10 md:flex lg:p-14">
      <div class="mx-auto my-auto w-full max-w-[320px]">
        <AuthDentalArch />
      </div>
      <div>
        <p class="font-display text-[24px] font-extrabold leading-[1.25] tracking-[-.5px] text-white">
          Sua agenda pronta antes do primeiro paciente.
        </p>
        <p class="mt-3 text-[14px] leading-[1.55] text-[#bae6fd]">
          Consultas, pacientes e equipe da clínica em um só lugar.
        </p>
      </div>
    </aside>
  </div>
</template>
