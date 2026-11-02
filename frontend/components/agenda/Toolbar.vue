<script setup lang="ts">
import { Ban, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import type { AgendaView } from '~/types/agenda'

defineProps<{
  rangeLabel: string
  canBlock?: boolean
}>()

defineEmits<{
  previous: []
  next: []
  today: []
  block: []
}>()

const view = defineModel<AgendaView>('view', { required: true })

const viewOptions: { value: AgendaView, label: string }[] = [
  { value: 'day', label: 'Dia' },
  { value: 'week', label: 'Semana' },
  { value: 'month', label: 'Mês' },
]

const iconButtonClass = 'grid h-[34px] w-[34px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft'
</script>

<template>
  <div class="mb-[18px] flex items-start justify-between gap-2 sm:items-center">
    <div class="flex items-center gap-2">
      <button type="button" :class="iconButtonClass" aria-label="Período anterior" @click="$emit('previous')">
        <ChevronLeft :size="17" />
      </button>
      <button
        type="button"
        class="inline-flex items-center rounded-[7px] border border-line bg-surface px-3 py-2 text-[10px] font-semibold text-body"
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
        v-if="canBlock"
        type="button"
        class="inline-flex items-center gap-[7px] rounded-[7px] border border-line bg-surface px-[9px] py-[7px] text-[10px] text-body"
        @click="$emit('block')"
      >
        <Ban :size="15" /> Bloquear horário
      </button>
      <div class="flex rounded-lg border border-line bg-surface p-[3px]" role="group" aria-label="Visualização da agenda">
        <button
          v-for="option in viewOptions"
          :key="option.value"
          type="button"
          class="rounded-[5px] px-[11px] py-1.5 text-[10px]"
          :class="view === option.value ? 'bg-brand-soft font-bold text-brand-text' : 'text-muted'"
          :aria-pressed="view === option.value"
          @click="view = option.value"
        >
          {{ option.label }}
        </button>
      </div>
    </div>
  </div>
</template>
