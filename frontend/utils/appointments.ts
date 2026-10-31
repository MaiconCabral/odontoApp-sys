import type { AgendaEvent } from '~/types/agenda'

export type NewAppointmentForm = {
  patientName: string
  procedure: string
  professionalId: string
  date: string
  hour: string
  roomId: string
}

export type NewAppointmentErrors = Partial<Record<keyof NewAppointmentForm, string>>

export function isRoomTaken(events: AgendaEvent[], date: string, hour: string, roomId: string): boolean {
  return events.some(event => event.date === date && event.hour === hour && event.roomId === roomId)
}

export function validateNewAppointment(
  form: NewAppointmentForm,
  patientNames: string[],
  events: AgendaEvent[],
): NewAppointmentErrors {
  const errors: NewAppointmentErrors = {}

  if (!patientNames.includes(form.patientName.trim())) {
    errors.patientName = 'Selecione um paciente cadastrado.'
  }

  if (!form.date) {
    errors.date = 'Informe a data da consulta.'
  }

  if (isRoomTaken(events, form.date, form.hour, form.roomId)) {
    errors.roomId = 'Esta sala já está ocupada neste horário.'
  }

  const professionalIsBusy = events.some(event =>
    event.date === form.date && event.hour === form.hour && event.professionalId === form.professionalId,
  )

  if (professionalIsBusy) {
    errors.professionalId = 'Este profissional já tem consulta neste horário.'
  }

  return errors
}
