<script setup lang="ts">
import type { CalendarOptions, EventClickArg, EventDropArg, EventMountArg } from '@fullcalendar/core'
import ptBrLocale from '@fullcalendar/core/locales/pt-br'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import timeGridPlugin from '@fullcalendar/timegrid'
import FullCalendar from '@fullcalendar/vue3'
import type { AgendaBlock, AgendaEvent, AgendaProfessional, AgendaView } from '~/types/agenda'

const props = defineProps<{
  view: AgendaView
  date: string
  today: string
  hours: string[]
  events: AgendaEvent[]
  blocks: AgendaBlock[]
  professionals: AgendaProfessional[]
  canManage?: boolean
}>()

const emit = defineEmits<{
  select: [id: string]
  selectBlock: [block: AgendaBlock]
  openDay: [date: string]
  // The event was dropped on a new time; revert() puts it back when the move is not saved.
  move: [move: { event: AgendaEvent, date: string, time: string, revert: () => void }]
}>()

const calendar = ref<InstanceType<typeof FullCalendar> | null>(null)

// Opens an appointment, or a block for who manages blocks.
function onEventClick(info: EventClickArg) {
  const extra = info.event.extendedProps

  if (extra.kind === 'block') {
    if (props.canManage) {
      emit('selectBlock', extra.block as AgendaBlock)
    }

    return
  }

  emit('select', info.event.id)
}

// Hands the new time to the board, which asks before saving.
function onEventDrop(info: EventDropArg) {
  if (!info.event.start) {
    info.revert()

    return
  }

  emit('move', { event: info.event.extendedProps.event as AgendaEvent, ...dropTarget(info.event.start), revert: info.revert })
}

// FullCalendar draws plain links; the name for screen readers is set here.
function onEventMount(info: EventMountArg) {
  info.el.setAttribute('aria-label', String(info.event.extendedProps.label ?? info.event.title))
}

const options = computed<CalendarOptions>(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: CALENDAR_VIEWS[props.view],
  initialDate: props.date,
  now: props.today,
  locale: ptBrLocale,
  headerToolbar: false,
  firstDay: 1,
  allDaySlot: false,
  slotDuration: '00:30:00',
  ...slotBounds(props.hours),
  height: 'auto',
  expandRows: true,
  nowIndicator: true,
  dayMaxEvents: 3,
  editable: Boolean(props.canManage),
  eventDurationEditable: false,
  events: [...toCalendarEvents(props.events, Boolean(props.canManage)), ...toCalendarBlocks(props.blocks, props.professionals)],
  eventClick: onEventClick,
  eventDrop: onEventDrop,
  eventDidMount: onEventMount,
  // A day of the month opens in the day view.
  dateClick: info => props.view === 'month' && emit('openDay', info.dateStr.slice(0, 10)),
}))

// The toolbar outside drives the calendar, so its own header stays off.
watch(() => [props.view, props.date] as const, ([view, date]) => {
  const api = calendar.value?.getApi()

  api?.changeView(CALENDAR_VIEWS[view], date)
})
</script>

<template>
  <div class="agenda-calendar min-w-[720px] p-2" :data-view="view">
    <FullCalendar ref="calendar" :options="options">
      <template #eventContent="arg">
        <div
          v-if="arg.event.extendedProps.kind === 'appointment'"
          class="flex h-full flex-col gap-[2px] overflow-hidden rounded border-l-[3px] px-1.5 py-1 text-left"
          :class="STATUS_CLASSES[(arg.event.extendedProps.event as AgendaEvent).status]"
        >
          <strong class="truncate text-[9px]">{{ arg.event.title }}</strong>
          <span class="truncate text-[8px]">{{ (arg.event.extendedProps.event as AgendaEvent).hour }} · {{ (arg.event.extendedProps.event as AgendaEvent).procedure }}</span>
          <span v-if="(arg.event.extendedProps.event as AgendaEvent).status === 'requested'" class="text-[8px] font-bold">
            {{ (arg.event.extendedProps.event as AgendaEvent).statusLabel }}
          </span>
          <span v-if="PAYMENT_LABELS[(arg.event.extendedProps.event as AgendaEvent).paymentStatus ?? 'none']" class="text-[8px] font-bold" aria-hidden="true">
            {{ PAYMENT_LABELS[(arg.event.extendedProps.event as AgendaEvent).paymentStatus ?? 'none'] }}
          </span>
        </div>
        <span v-else class="block p-1 text-[9px] font-semibold text-muted">{{ arg.event.title }}</span>
      </template>
    </FullCalendar>
  </div>
</template>
