<script setup lang="ts">
import type { AgendaDay, AgendaEvent, AgendaEventTone } from '~/types/agenda'

const props = defineProps<{
  days: AgendaDay[]
  hours: string[]
  events: AgendaEvent[]
}>()

const toneClasses: Record<AgendaEventTone, string> = {
  blue: 'border-brand-accent bg-brand-soft text-brand-text',
  amber: 'border-[#dda646] bg-[#fff4dc] text-[#a3782c]',
  violet: 'border-[#8e83ca] bg-[#efedfb] text-[#7467af]',
}

const eventsBySlot = computed(() => {
  const slots = new Map<string, AgendaEvent[]>()

  for (const event of props.events) {
    const key = `${event.date}|${event.hour}`
    slots.set(key, [...(slots.get(key) ?? []), event])
  }

  return slots
})

const lineClass = 'border-line-soft'
</script>

<template>
  <div
    class="grid min-w-[720px] grid-rows-[47px_1fr]"
    :class="days.length === 1 ? 'grid-cols-[55px_minmax(400px,1fr)]' : 'grid-cols-[55px_repeat(7,minmax(90px,1fr))]'"
    data-testid="time-grid"
  >
    <div class="border-b border-r" :class="lineClass" />
    <div
      v-for="day in days"
      :key="day.date"
      class="flex items-center justify-center gap-[5px] border-b border-r text-[10px]"
      :class="[lineClass, day.isToday ? 'bg-brand-tint text-brand-text dark:bg-brand-soft' : 'text-muted']"
      :aria-current="day.isToday ? 'date' : undefined"
    >
      <span>{{ day.weekday }}</span>
      <strong
        class="text-[13px]"
        :class="day.isToday ? 'grid h-6 w-6 place-items-center rounded-full bg-brand text-white' : 'text-body'"
      >
        {{ day.dayOfMonth }}
      </strong>
    </div>

    <div class="flex flex-col">
      <span
        v-for="hour in hours"
        :key="hour"
        class="h-[54px] pr-2 pt-2 text-right text-[9px] text-faint"
      >
        {{ hour }}
      </span>
    </div>
    <div v-for="day in days" :key="day.date" class="border-r" :class="lineClass">
      <div
        v-for="hour in hours"
        :key="hour"
        class="relative h-[54px] border-b border-line-soft"
      >
        <div
          v-for="event in eventsBySlot.get(`${day.date}|${hour}`)"
          :key="event.id"
          class="absolute inset-x-1 inset-y-[3px] z-[2] flex flex-col gap-[3px] overflow-hidden rounded border-l-[3px] px-1.5 py-[7px]"
          :class="toneClasses[event.tone]"
        >
          <strong class="truncate text-[9px]">{{ event.patientName }}</strong>
          <span class="text-[8px]">{{ event.hour }} · {{ event.procedure }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
