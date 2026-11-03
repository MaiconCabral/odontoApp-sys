import type { CalendarOptions, EventInput } from '@fullcalendar/core'
import { defineComponent, h, reactive } from 'vue'

// What the calendar was told to show, for the tests to read.
export const calendarState = reactive({ view: '', date: '' })

// happy-dom has no layout, so FullCalendar cannot draw. This stub renders each event as a button
// with the same aria-label and click handler, and exposes getApi() for the view changes.
export const FullCalendarStub = defineComponent({
  name: 'FullCalendarStub',
  props: { options: { type: Object as () => CalendarOptions, required: true } },
  setup(props, { expose }) {
    calendarState.view = String(props.options.initialView)
    calendarState.date = String(props.options.initialDate)

    expose({
      getApi: () => ({
        changeView: (view: string, date: string) => {
          calendarState.view = view
          calendarState.date = date
        },
      }),
    })

    return () => h('div', { 'data-testid': 'calendar', 'data-view': calendarState.view, 'data-date': calendarState.date }, ((props.options.events ?? []) as EventInput[]).map(event =>
      h('button', {
        'type': 'button',
        'key': event.id,
        'data-start': event.start,
        'data-end': event.end,
        'data-display': event.display ?? 'auto',
        'data-editable': String(event.startEditable ?? false),
        'class': event.extendedProps?.kind === 'appointment' ? STATUS_CLASSES[event.extendedProps.event.status as keyof typeof STATUS_CLASSES] : '',
        'aria-label': event.extendedProps?.label,
        'onClick': () => props.options.eventClick?.({ event: { id: event.id, title: event.title, extendedProps: event.extendedProps } } as never),
      }, String(event.title)),
    ))
  },
})
