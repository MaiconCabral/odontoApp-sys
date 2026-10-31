<script setup lang="ts">
defineProps<{
  title: string
  subtitle: string
}>()
</script>

<template>
  <section class="min-w-0 rounded-xl border border-[#e7edf3] bg-surface shadow-[0_4px_14px_rgba(42,64,91,.025)] dark:border-line">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h2 class="font-display text-[14px] font-bold leading-[normal] text-heading">
          {{ title }}
        </h2>
        <p class="mt-[5px] text-[11px] text-[#95a1b0] dark:text-muted">
          {{ subtitle }}
        </p>
      </div>
      <slot name="action" />
    </div>
    <slot />
  </section>
</template>
