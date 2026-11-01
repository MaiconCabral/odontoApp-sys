<script setup lang="ts">
import { Stethoscope } from 'lucide-vue-next'

defineProps<{
  compact?: boolean
}>()
</script>

<template>
  <div class="grid h-[30px] w-[30px] flex-none place-items-center rounded-[9px] bg-brand text-white shadow-[0_4px_12px_rgba(2,132,199,.2)]">
    <Stethoscope :size="19" :stroke-width="2.5" />
  </div>
  <div v-if="!compact" class="font-display text-[21px] font-extrabold leading-[normal] tracking-[-1.2px] text-ink">
    sorriso<span class="text-brand-text">.</span>
  </div>
</template>
