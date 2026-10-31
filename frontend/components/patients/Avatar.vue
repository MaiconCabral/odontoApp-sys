<script setup lang="ts">
import type { PatientTone } from '~/types/patients'

defineProps<{
  initials: string
  tone: PatientTone
}>()

const toneClasses: Record<PatientTone, string> = {
  blue: 'bg-[#ddf3f7] text-[#087b9b]',
  amber: 'bg-[#fff0d1] text-[#b98027]',
  green: 'bg-[#e1f6ed] text-[#218d69]',
  violet: 'bg-[#eeeafb] text-[#776fb1]',
  pink: 'bg-[#fce9ed] text-[#b86d7e]',
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
