<script setup lang="ts">
import { ArrowUpRight } from 'lucide-vue-next'

defineProps<{
  to: string
}>()
</script>

<template>
  <NuxtLink :to="to" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-brand-text">
    <slot />
    <ArrowUpRight :size="15" />
  </NuxtLink>
</template>
