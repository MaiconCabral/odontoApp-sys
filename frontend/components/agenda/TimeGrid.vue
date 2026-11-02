<script setup lang="ts">
import type { AgendaBlock, AgendaDay, AgendaEvent, AgendaProfessional } from '~/types/agenda'

const props = defineProps<{
  days: AgendaDay[]
  hours: string[]
  events: AgendaEvent[]
  blocks: AgendaBlock[]
  professionals?: AgendaProfessional[]
  canManageBlocks?: boolean
}>()

const emit = defineEmits<{
  select: [id: string]
  selectBlock: [block: AgendaBlock]
}>()

const ROW_HEIGHT = 30

const eventsByDay = computed(() => {
  const days = new Map<string, AgendaEvent[]>()

  for (const event of props.events) {
    days.set(event.date, [...(days.get(event.date) ?? []), event])
  }

  return days
})

// Position of an event, side by side with the ones that overlap it.
function eventStyle(event: AgendaEvent, dayEvents: AgendaEvent[]) {
  const end = minutesOf(event.hour) + event.duration
  const overlapping = dayEvents
    .filter(other => minutesOf(other.hour) < end && minutesOf(other.hour) + other.duration > minutesOf(event.hour))
    .sort((a, b) => a.hour.localeCompare(b.hour) || a.id.localeCompare(b.id))
  const lane = overlapping.indexOf(event)
  const width = 100 / overlapping.length

  return {
    top: `${gridOffset(props.hours, event.hour) * ROW_HEIGHT + 2}px`,
    height: `${Math.max(1, event.duration / SLOT_MINUTES) * ROW_HEIGHT - 4}px`,
    left: `calc(${lane * width}% + 3px)`,
    width: `calc(${width}% - 6px)`,
  }
}

// Holidays and days off that touch a day.
function blocksOn(date: string) {
  return props.blocks
    .map(block => ({ block, span: blockSpanOnDay(block, date, props.hours) }))
    .filter((item): item is { block: AgendaBlock, span: { start: string, end: string } } => item.span !== null)
}

// Position of a block inside the day.
function blockStyle(span: { start: string, end: string }) {
  return {
    top: `${gridOffset(props.hours, span.start) * ROW_HEIGHT}px`,
    height: `${(minutesOf(span.end) - minutesOf(span.start)) / SLOT_MINUTES * ROW_HEIGHT}px`,
  }
}

// Reason of a block, with the professional when it is a day off.
function blockLabel(block: AgendaBlock) {
  const name = props.professionals?.find(professional => professional.id === block.professionalId)?.name

  return name ? `${block.reason} · ${name}` : block.reason
}

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
        class="pr-2 pt-1 text-right text-[9px] text-faint"
        :style="{ height: `${ROW_HEIGHT}px` }"
      >
        {{ hour.endsWith(':00') ? hour : '' }}
      </span>
    </div>

    <div v-for="day in days" :key="day.date" class="relative border-r" :class="lineClass">
      <div
        v-for="hour in hours"
        :key="hour"
        class="border-b"
        :class="hour.endsWith(':00') ? 'border-line-soft' : 'border-dashed border-line-soft/60'"
        :style="{ height: `${ROW_HEIGHT}px` }"
      />

      <component
        :is="canManageBlocks ? 'button' : 'div'"
        v-for="{ block, span } in blocksOn(day.date)"
        :key="`block-${block.id}`"
        :type="canManageBlocks ? 'button' : undefined"
        class="absolute inset-x-0 z-[1] overflow-hidden bg-[repeating-linear-gradient(135deg,transparent,transparent_6px,rgba(148,163,184,.18)_6px,rgba(148,163,184,.18)_12px)] px-1.5 py-1 text-left text-[8px] text-muted"
        :style="blockStyle(span)"
        :aria-label="`Bloqueio: ${blockLabel(block)}`"
        @click="canManageBlocks && emit('selectBlock', block)"
      >
        {{ blockLabel(block) }}
      </component>

      <button
        v-for="event in eventsByDay.get(day.date) ?? []"
        :key="event.id"
        type="button"
        class="absolute z-[2] flex flex-col gap-[2px] overflow-hidden rounded border-l-[3px] px-1.5 py-1 text-left transition duration-200 hover:brightness-95"
        :class="STATUS_CLASSES[event.status]"
        :style="eventStyle(event, eventsByDay.get(day.date) ?? [])"
        :aria-label="`${event.patientName}, ${event.hour}, ${event.statusLabel}`"
        @click="emit('select', event.id)"
      >
        <strong class="truncate text-[9px]">{{ event.patientName }}</strong>
        <span class="truncate text-[8px]">{{ event.hour }} · {{ event.procedure }}</span>
        <span v-if="event.status === 'requested'" class="text-[8px] font-bold">{{ event.statusLabel }}</span>
      </button>
    </div>
  </div>
</template>
