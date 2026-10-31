import type { AgendaView } from '~/types/agenda'

export const WEEKDAY_LABELS = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom']

const MONTH_LABELS = [
  'Janeiro',
  'Fevereiro',
  'Março',
  'Abril',
  'Maio',
  'Junho',
  'Julho',
  'Agosto',
  'Setembro',
  'Outubro',
  'Novembro',
  'Dezembro',
]

// Dates are plain YYYY-MM-DD strings handled in UTC, so server and client always agree.
function toDate(iso: string): Date {
  return new Date(`${iso}T00:00:00Z`)
}

function toIso(date: Date): string {
  return date.toISOString().slice(0, 10)
}

function weekdayIndex(iso: string): number {
  return (toDate(iso).getUTCDay() + 6) % 7
}

function daysInMonth(year: number, month: number): number {
  return new Date(Date.UTC(year, month + 1, 0)).getUTCDate()
}

function monthYearLabel(date: Date): string {
  return `${MONTH_LABELS[date.getUTCMonth()]}, ${date.getUTCFullYear()}`
}

export function addDays(iso: string, amount: number): string {
  const date = toDate(iso)
  date.setUTCDate(date.getUTCDate() + amount)

  return toIso(date)
}

export function addMonths(iso: string, amount: number): string {
  const date = toDate(iso)
  const target = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + amount, 1))
  const lastDay = daysInMonth(target.getUTCFullYear(), target.getUTCMonth())

  target.setUTCDate(Math.min(date.getUTCDate(), lastDay))

  return toIso(target)
}

export function weekDates(iso: string): string[] {
  const monday = addDays(iso, -weekdayIndex(iso))

  return WEEKDAY_LABELS.map((_, index) => addDays(monday, index))
}

export function weekdayLabel(iso: string): string {
  return WEEKDAY_LABELS[weekdayIndex(iso)] ?? ''
}

export function dayOfMonth(iso: string): number {
  return toDate(iso).getUTCDate()
}

export function shiftDate(iso: string, view: AgendaView, direction: 1 | -1): string {
  if (view === 'month') {
    return addMonths(iso, direction)
  }

  return addDays(iso, view === 'week' ? direction * 7 : direction)
}

export function formatRange(iso: string, view: AgendaView): string {
  const date = toDate(iso)

  if (view === 'month') {
    return monthYearLabel(date)
  }

  if (view === 'day') {
    return `${date.getUTCDate()} ${monthYearLabel(date)}`
  }

  const dates = weekDates(iso)
  const start = toDate(dates[0] ?? iso)
  const end = toDate(dates[6] ?? iso)

  if (start.getUTCFullYear() !== end.getUTCFullYear()) {
    return `${start.getUTCDate()} ${monthYearLabel(start)} — ${end.getUTCDate()} ${monthYearLabel(end)}`
  }

  if (start.getUTCMonth() !== end.getUTCMonth()) {
    return `${start.getUTCDate()} ${MONTH_LABELS[start.getUTCMonth()]} — ${end.getUTCDate()} ${monthYearLabel(end)}`
  }

  return `${start.getUTCDate()} — ${end.getUTCDate()} ${monthYearLabel(end)}`
}

// Weeks start on Monday; null cells fill the days outside the month.
export function monthGrid(iso: string): (string | null)[] {
  const date = toDate(iso)
  const year = date.getUTCFullYear()
  const month = date.getUTCMonth()
  const total = daysInMonth(year, month)
  const first = toIso(new Date(Date.UTC(year, month, 1)))
  const days = Array.from({ length: total }, (_, index) => addDays(first, index))
  const leading = weekdayIndex(first)
  const trailing = (7 - ((leading + total) % 7)) % 7

  return [
    ...Array.from({ length: leading }, () => null),
    ...days,
    ...Array.from({ length: trailing }, () => null),
  ]
}

export function formatShortDate(iso: string): string {
  const [year, month, day] = iso.split('-')

  return `${day}/${month}/${year}`
}
