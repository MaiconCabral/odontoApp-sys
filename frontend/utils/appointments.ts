import type {
  AgendaBlock,
  AgendaRange,
  AgendaView,
  AppointmentAction,
  AppointmentForm,
  AppointmentStatus,
} from '~/types/agenda'
import { monthGrid, weekdayLabel, weekDates } from '~/utils/agenda'
import type { FormErrors } from '~/utils/auth'

export const SLOT_MINUTES = 30

export const STATUS_CLASSES: Record<AppointmentStatus, string> = {
  requested: 'border-dashed border-[#dda646] bg-[#fff4dc] text-[#a3782c]',
  scheduled: 'border-brand-accent bg-brand-soft text-brand-text',
  confirmed: 'border-[#43a47d] bg-[#e7f7ef] text-[#2f7d5f]',
  completed: 'border-line bg-surface-soft text-muted',
  no_show: 'border-[#d9705c] bg-[#fdeeea] text-[#b4543f]',
  cancelled: 'border-line bg-surface-soft text-faint line-through',
  rejected: 'border-line bg-surface-soft text-faint line-through',
}

export const ACTION_LABELS: Record<AppointmentAction, string> = {
  accept: 'Aceitar pedido',
  reject: 'Recusar pedido',
  confirm: 'Confirmar presença',
  complete: 'Concluída',
  no_show: 'Faltou',
  cancel: 'Cancelar consulta',
  reschedule: 'Remarcar',
}

export const ACTION_STATUS: Partial<Record<AppointmentAction, AppointmentStatus>> = {
  reject: 'rejected',
  confirm: 'confirmed',
  complete: 'completed',
  no_show: 'no_show',
  cancel: 'cancelled',
}

// Minutes since midnight of an HH:MM time.
export function minutesOf(time: string): number {
  const [hours = 0, minutes = 0] = time.split(':').map(Number)

  return hours * 60 + minutes
}

// HH:MM time of minutes since midnight.
export function timeOf(minutes: number): string {
  return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`
}

// Rows of the day grid between its first hour and a time.
export function gridOffset(hours: string[], time: string): number {
  return (minutesOf(time) - minutesOf(hours[0] ?? '00:00')) / SLOT_MINUTES
}

// Dates the current view shows.
export function visibleRange(date: string, view: AgendaView): AgendaRange {
  if (view === 'day') {
    return { from: date, to: date }
  }

  const dates = view === 'week' ? weekDates(date) : monthGrid(date).filter((cell): cell is string => cell !== null)

  return { from: dates[0] ?? date, to: dates[dates.length - 1] ?? date }
}

// Part of a block inside one day of the grid, or null when it does not touch the day.
export function blockSpanOnDay(block: AgendaBlock, date: string, hours: string[]): { start: string, end: string } | null {
  const [startDate = '', startTime = '00:00'] = block.startsAt.split(' ')
  const [endDate = '', endTime = '23:59'] = block.endsAt.split(' ')
  const gridStart = hours[0] ?? '00:00'
  const gridEnd = timeOf(minutesOf(hours[hours.length - 1] ?? '23:30') + SLOT_MINUTES)

  if (date < startDate || date > endDate) {
    return null
  }

  const start = date === startDate && startTime > gridStart ? startTime : gridStart
  const end = date === endDate && endTime < gridEnd ? endTime : gridEnd

  return start < end ? { start, end } : null
}

// Short date and time, as "Ter, 03/11 às 15:00".
export function formatAppointmentDate(date: string, hour: string): string {
  const [, month, day] = date.split('-')

  return `${weekdayLabel(date)}, ${day}/${month} às ${hour}`
}

// Required fields of the appointment form.
export function validateAppointment(form: AppointmentForm): FormErrors<AppointmentForm> {
  const errors: FormErrors<AppointmentForm> = {}

  if (!form.patientId) {
    errors.patientId = 'Selecione um paciente cadastrado.'
  }

  if (!form.professionalId) {
    errors.professionalId = 'Escolha o profissional.'
  }

  if (!form.roomId) {
    errors.roomId = 'Escolha a sala.'
  }

  if (!form.date) {
    errors.date = 'Informe a data da consulta.'
  }

  if (!form.time) {
    errors.time = 'Escolha um horário livre.'
  }

  return errors
}
