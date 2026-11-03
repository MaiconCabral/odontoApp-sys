import type {
  AgendaBlock,
  AgendaRange,
  AgendaView,
  AppointmentAction,
  AppointmentForm,
  AppointmentStatus,
  PaymentStatus,
} from '~/types/agenda'
import { monthGrid, weekdayLabel, weekDates } from '~/utils/agenda'
import type { FormErrors } from '~/utils/auth'

export const SLOT_MINUTES = 30

export const STATUS_CLASSES: Record<AppointmentStatus, string> = {
  requested: 'border-dashed border-tone-amber-line bg-tone-amber-bg text-tone-amber-text',
  scheduled: 'border-brand-accent bg-brand-soft text-brand-text',
  confirmed: 'border-tone-green-line bg-tone-green-bg text-tone-green-text',
  completed: 'border-line bg-surface-soft text-muted',
  no_show: 'border-tone-red-line bg-tone-red-bg text-tone-red-text',
  cancelled: 'border-line bg-surface-soft text-faint line-through',
  rejected: 'border-line bg-surface-soft text-faint line-through',
}

// Payment mark of an agenda event; nothing for free or unpaid appointments.
export const PAYMENT_LABELS: Record<PaymentStatus, string> = {
  none: '',
  pending: '',
  partial: 'Pago em parte',
  paid: 'Pago',
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

// Dates the current view shows.
export function visibleRange(date: string, view: AgendaView): AgendaRange {
  if (view === 'day') {
    return { from: date, to: date }
  }

  const dates = view === 'week' ? weekDates(date) : monthGrid(date).filter((cell): cell is string => cell !== null)

  return { from: dates[0] ?? date, to: dates[dates.length - 1] ?? date }
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
