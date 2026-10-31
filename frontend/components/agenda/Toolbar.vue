<script setup lang="ts">
import { ChevronLeft, ChevronRight, Filter } from 'lucide-vue-next'
import type { AgendaView } from '~/types/agenda'

defineProps<{
  rangeLabel: string
}>()

defineEmits<{
  previous: []
  next: []
  today: []
}>()

const view = defineModel<AgendaView>('view', { required: true })

const viewOptions: { value: AgendaView, label: string }[] = [
  { value: 'day', label: 'Dia' },
  { value: 'week', label: 'Semana' },
  { value: 'month', label: 'Mês' },
]

const iconButtonClass = 'grid h-[34px] w-[34px] place-items-center rounded-lg text-[#738095] transition duration-200 hover:bg-[#f1f5f9] hover:text-[#167d9d] dark:hover:bg-[#172b37]'
</script>

<template>
  <div class="mb-[18px] flex items-start justify-between gap-2 sm:items-center">
    <div class="flex items-center gap-2">
      <button type="button" :class="iconButtonClass" aria-label="Período anterior" @click="$emit('previous')">
        <ChevronLeft :size="17" />
      </button>
      <button
        type="button"
        class="inline-flex items-center rounded-[7px] border border-[#e6ebf1] bg-surface px-3 py-2 text-[10px] font-semibold text-[#3f4e62] dark:border-line dark:text-[#acb8c8]"
        @click="$emit('today')"
      >
        Hoje
      </button>
      <button type="button" :class="iconButtonClass" aria-label="Próximo período" @click="$emit('next')">
        <ChevronRight :size="17" />
      </button>
      <strong class="ml-[7px] hidden text-[13px] sm:block" aria-live="polite">{{ rangeLabel }}</strong>
    </div>

    <div class="flex flex-col-reverse items-end gap-2 sm:flex-row sm:items-center">
      <button
        type="button"
        class="inline-flex items-center gap-[7px] rounded-[7px] border border-[#e6ebf1] bg-surface px-[9px] py-[7px] text-[10px] text-[#6e7b8c] dark:border-line dark:text-[#acb8c8]"
      >
        <Filter :size="15" /> Filtros
      </button>
      <div class="flex rounded-lg border border-[#e5ebf0] bg-surface p-[3px] dark:border-line" role="group" aria-label="Visualização da agenda">
        <button
          v-for="option in viewOptions"
          :key="option.value"
          type="button"
          class="rounded-[5px] px-[11px] py-1.5 text-[10px]"
          :class="view === option.value ? 'bg-[#e6f6f9] font-bold text-[#087e9f]' : 'text-[#8592a3] dark:text-[#acb8c8]'"
          :aria-pressed="view === option.value"
          @click="view = option.value"
        >
          {{ option.label }}
        </button>
      </div>
    </div>
  </div>
</template>
