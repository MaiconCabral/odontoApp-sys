<script setup lang="ts">
withDefaults(defineProps<{
  tone?: 'error' | 'success'
}>(), {
  tone: 'error',
})
</script>

<template>
  <p
    :role="tone === 'error' ? 'alert' : 'status'"
    class="mb-5 rounded-lg border px-3.5 py-3 text-[13px] leading-[1.45]"
    :class="tone === 'error'
      ? 'border-[#f3c9c0] bg-[#fdf1ee] text-[#a8402c] dark:border-[#5c2a20] dark:bg-[#2a1512] dark:text-[#f0a899]'
      : 'border-[#cdeee1] bg-[#ecfaf4] text-[#0f6b4f] dark:border-[#1f4a3a] dark:bg-[#0f2a20] dark:text-[#6fd3ad]'"
  >
    <slot />
  </p>
</template>
