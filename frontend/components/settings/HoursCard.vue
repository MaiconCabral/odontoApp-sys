<script setup lang="ts">
import type { OpeningHours } from '~/types/settings'

defineProps<{
  openingHours: OpeningHours[]
}>()
</script>

<template>
  <SettingsCard
    title="Horários de funcionamento"
    subtitle="Períodos em que a clínica recebe consultas."
  >
    <div class="mt-[5px]">
      <div
        v-for="period in openingHours"
        :key="period.id"
        class="flex min-h-[55px] items-center justify-between gap-4 border-b border-line-soft px-[3px] text-[11px] text-body"
      >
        <strong>{{ period.days }}</strong>
        <span class="text-right text-[10px] text-muted">{{ period.hours }}</span>
      </div>
    </div>
  </SettingsCard>
</template>
