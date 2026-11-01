<script setup lang="ts">
import type { SettingsProfessional } from '~/types/settings'

defineProps<{
  professionals: SettingsProfessional[]
}>()
</script>

<template>
  <SettingsCard
    title="Profissionais"
    subtitle="Quem atende na sua clínica e aparece agenda."
  >
    <div class="mt-[5px]">
      <div
        v-for="professional in professionals"
        :key="professional.id"
        class="flex min-h-[55px] items-center justify-between gap-4 border-b border-line-soft px-[3px] text-[11px] text-body"
      >
        <strong>{{ professional.name }}</strong>
        <span class="text-right text-[10px] text-muted">{{ professional.specialty }}</span>
      </div>
    </div>
  </SettingsCard>
</template>
