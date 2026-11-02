<script setup lang="ts">
import type { AgendaEvent } from '~/types/agenda'

const props = defineProps<{
  date: string
  events: AgendaEvent[]
}>()

const emit = defineEmits<{
  openDay: [date: string]
}>()

const countByDate = computed(() => {
  const counts = new Map<string, number>()

  for (const event of props.events) {
    counts.set(event.date, (counts.get(event.date) ?? 0) + 1)
  }

  return counts
})

const cells = computed(() => monthGrid(props.date))

const lineClass = 'border-line-soft'
</script>

<template>
  <div class="grid grid-cols-7" data-testid="month-grid">
    <div
      v-for="weekday in WEEKDAY_LABELS"
      :key="weekday"
      class="border-b p-[13px] text-[10px] text-faint"
      :class="lineClass"
    >
      {{ weekday }}
    </div>
    <div
      v-for="(cell, index) in cells"
      :key="cell ?? `empty-${index}`"
      class="h-[93px] border-b border-r p-2.5 text-[10px]"
      :class="[
        lineClass,
        cell === date ? 'bg-brand-tint font-bold text-brand-text dark:bg-surface-soft' : 'text-muted',
      ]"
    >
      <template v-if="cell">
        {{ dayOfMonth(cell) }}
        <button
          v-if="countByDate.get(cell)"
          type="button"
          class="mt-2 block rounded bg-brand-soft px-1.5 py-0.5 text-[9px] font-semibold text-brand-text"
          data-testid="day-count"
          @click="emit('openDay', cell)"
        >
          {{ countByDate.get(cell) }} {{ countByDate.get(cell) === 1 ? 'consulta' : 'consultas' }}
        </button>
      </template>
    </div>
  </div>
</template>
