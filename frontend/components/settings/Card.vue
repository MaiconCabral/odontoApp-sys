<script setup lang="ts">
defineProps<{
  title: string
  subtitle: string
}>()
</script>

<template>
  <div class="rounded-xl border border-line bg-surface p-4 shadow-[0_4px_14px_rgba(42,64,91,.025)] sm:p-[22px]">
    <div class="flex items-start justify-between gap-4 border-b border-line-soft pb-[19px]">
      <div>
        <h2 class="font-display text-[14px] font-bold leading-[normal] text-heading">
          {{ title }}
        </h2>
        <p class="mt-[5px] text-[11px] text-muted">
          {{ subtitle }}
        </p>
      </div>
      <slot name="action" />
    </div>
    <slot />
  </div>
</template>
