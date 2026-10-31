<script setup lang="ts">
import type { SettingsProfessional } from '~/types/settings'

defineProps<{
  professionals: SettingsProfessional[]
}>()
</script>

<template>
  <SettingsCard
    title="Profissionais"
    subtitle="Quem atende na sua clínica e aparece na agenda."
  >
    <div class="mt-[5px]">
      <div
        v-for="professional in professionals"
        :key="professional.id"
        class="flex min-h-[55px] items-center justify-between gap-4 border-b border-[#edf1f4] px-[3px] text-[11px] text-[#526176] dark:border-line dark:text-ink"
      >
        <strong>{{ professional.name }}</strong>
        <span class="text-right text-[10px] text-[#9aa6b4] dark:text-muted">{{ professional.specialty }}</span>
      </div>
    </div>
  </SettingsCard>
</template>
