<script setup lang="ts">
import type { AgendaEvent } from '~/types/agenda'

const props = defineProps<{
  date: string
  events: AgendaEvent[]
}>()

const eventDates = computed(() => new Set(props.events.map(event => event.date)))
const cells = computed(() => monthGrid(props.date))

const lineClass = 'border-[#edf1f4] dark:border-[#202e3d]'
</script>

<template>
  <div class="grid grid-cols-7" data-testid="month-grid">
    <div
      v-for="weekday in WEEKDAY_LABELS"
      :key="weekday"
      class="border-b p-[13px] text-[10px] text-[#9da8b6]"
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
        cell === date ? 'bg-[#f1fbfc] font-bold text-[#087f9e] dark:bg-surface-soft' : 'text-[#8794a5] dark:text-[#718096]',
      ]"
    >
      <template v-if="cell">
        {{ dayOfMonth(cell) }}
        <i v-if="eventDates.has(cell)" class="mt-[13px] block h-[5px] w-[5px] rounded-full bg-[#1698b4]" />
      </template>
    </div>
  </div>
</template>
