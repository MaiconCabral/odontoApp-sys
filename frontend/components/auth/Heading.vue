<script setup lang="ts">
defineProps<{
  title: string
  description: string
}>()
</script>

<template>
  <div class="mb-7">
    <h1 class="font-display text-[26px] font-extrabold leading-[1.2] tracking-[-.6px] text-heading">
      {{ title }}
    </h1>
    <p class="mt-2 text-[14px] leading-[1.5] text-muted">
      {{ description }}
    </p>
  </div>
</template>
