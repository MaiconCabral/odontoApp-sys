import type { EventInput } from '@fullcalendar/core'
import type { AgendaBlock, AgendaEvent, AgendaProfessional, AgendaView } from '~/types/agenda'

// FullCalendar view of each view of the toolbar.
export const CALENDAR_VIEWS: Record<AgendaView, string> = {
  day: 'timeGridDay',
  week: 'timeGridWeek',
  month: 'dayGridMonth',
}

// Only booked appointments move; requests wait for the reception, and the past is history.
const MOVABLE = new Set(['scheduled', 'confirmed'])

// Name read by screen readers for an event, like "Camila Ferreira, 10:00, Agendado, Pago".
export function eventLabel(event: AgendaEvent): string {
  return [event.patientName, event.hour, event.statusLabel, PAYMENT_LABELS[event.paymentStatus ?? 'none']].filter(Boolean).join(', ')
}

// Reason of a block, with the professional when it is a day off.
export function blockLabel(block: AgendaBlock, professionals: AgendaProfessional[] = []): string {
  const name = professionals.find(professional => professional.id === block.professionalId)?.name

  return name ? `${block.reason} · ${name}` : block.reason
}

// Appointments as calendar events. Colors come from STATUS_CLASSES in the event content.
export function toCalendarEvents(events: AgendaEvent[], canManage: boolean): EventInput[] {
  return events.map(event => ({
    id: event.id,
    title: event.patientName,
    start: `${event.date}T${event.hour}:00`,
    end: `${event.date}T${event.endHour}:00`,
    classNames: ['agenda-event'],
    startEditable: canManage && MOVABLE.has(event.status),
    durationEditable: false,
    extendedProps: { kind: 'appointment', event, label: eventLabel(event) },
  }))
}

// Holidays and days off as shaded background, clickable by who manages blocks.
export function toCalendarBlocks(blocks: AgendaBlock[], professionals: AgendaProfessional[]): EventInput[] {
  return blocks.map(block => ({
    id: `block-${block.id}`,
    title: blockLabel(block, professionals),
    // The API sends "2024-10-24 14:00"; FullCalendar reads ISO with a "T".
    start: block.startsAt.replace(' ', 'T'),
    end: block.endsAt.replace(' ', 'T'),
    display: 'background',
    classNames: ['agenda-block'],
    extendedProps: { kind: 'block', block, label: `Bloqueio: ${block.reason}` },
  }))
}

// First and last time of the grid, from the hours the API sends (30-minute rows).
export function slotBounds(hours: string[]): { slotMinTime: string, slotMaxTime: string } {
  const first = hours[0] ?? '08:00'
  const last = hours.at(-1) ?? '18:00'

  return { slotMinTime: `${first}:00`, slotMaxTime: `${timeOf(minutesOf(last) + SLOT_MINUTES)}:00` }
}

// Date and time of a dropped event in the local time of the clinic.
export function dropTarget(start: Date): { date: string, time: string } {
  const pad = (value: number) => String(value).padStart(2, '0')

  return {
    date: `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`,
    time: `${pad(start.getHours())}:${pad(start.getMinutes())}`,
  }
}
