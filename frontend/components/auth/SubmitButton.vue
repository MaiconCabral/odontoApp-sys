<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next'

defineProps<{
  label: string
  loadingLabel: string
  loading: boolean
}>()
</script>

<template>
  <button
    type="submit"
    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand px-4 text-[14px] font-bold text-white shadow-[0_5px_14px_rgba(3,105,161,.18)] transition duration-200 hover:bg-brand-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-accent disabled:cursor-not-allowed disabled:opacity-70"
    :disabled="loading"
    :aria-busy="loading"
  >
    <LoaderCircle v-if="loading" :size="17" class="animate-spin" />
    {{ loading ? loadingLabel : label }}
  </button>
</template>
