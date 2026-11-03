<script setup lang="ts">
import type { PatientTone } from '~/types/patients'

defineProps<{
  initials: string
  tone: PatientTone
}>()

const toneClasses: Record<PatientTone, string> = {
  blue: 'bg-brand-soft text-brand-text',
  amber: 'bg-tone-amber-bg text-tone-amber-text',
  green: 'bg-tone-green-bg text-tone-green-text',
  violet: 'bg-tone-violet-bg text-tone-violet-text',
  pink: 'bg-tone-pink-bg text-tone-pink-text',
}
</script>

<template>
  <div
    class="grid h-8 w-8 flex-none place-items-center rounded-[9px] text-[10px] font-bold"
    :class="toneClasses[tone]"
  >
    {{ initials }}
  </div>
</template>
