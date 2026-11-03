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
      ? 'border-tone-red-line bg-tone-red-bg text-tone-red-text'
      : 'border-tone-green-line bg-tone-green-bg text-tone-green-text'"
  >
    <slot />
  </p>
</template>
