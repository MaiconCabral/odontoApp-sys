<script setup lang="ts">
import { Plus } from 'lucide-vue-next'

defineProps<{
  eyebrow: string
  title: string
  description: string
  actionLabel?: string
}>()

defineEmits<{
  action: []
}>()
</script>

<template>
  <div class="mb-7 gap-5 sm:flex sm:items-end sm:justify-between">
    <div>
      <div class="text-[10px] font-bold uppercase tracking-[.08em] text-[#118eaa]">
        {{ eyebrow }}
      </div>
      <h1 class="mb-[7px] mt-1.5 font-display text-[24px] font-extrabold leading-[normal] tracking-[-1px] text-ink sm:text-[28px]">
        {{ title }}
      </h1>
      <p class="text-[13px] text-muted">
        {{ description }}
      </p>
    </div>
    <button
      v-if="actionLabel"
      type="button"
      @click="$emit('action')"
      class="mt-[18px] inline-flex flex-none items-center justify-center gap-2 rounded-lg bg-brand px-[15px] py-2.5 text-[12px] font-bold text-white shadow-[0_5px_14px_rgba(11,140,173,.18)] transition duration-200 hover:-translate-y-px hover:bg-brand-hover sm:mt-0"
    >
      <Plus :size="17" /> {{ actionLabel }}
    </button>
  </div>
</template>
