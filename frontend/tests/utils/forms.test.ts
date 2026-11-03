import { describe, expect, it } from 'vitest'
import type { AgendaBlock, AppointmentForm } from '~/types/agenda'
import {
  blockSpanOnDay,
  formatAppointmentDate,
  gridOffset,
  timeOf,
  validateAppointment,
  visibleRange,
} from '~/utils/appointments'
import { localToday, validateBookingContact } from '~/utils/booking'
import { centsToInput, formatMoney, toCents } from '~/utils/money'
import { formatCpf, formatPhone, patientInitials, validatePatient } from '~/utils/patients'
import { agendaMock } from '../fixtures/agenda'

const appointment: AppointmentForm = {
  patientId: 'patient-1',
  procedure: 'cleaning',
  professionalId: 'carlos',
  roomId: 'room-1',
  date: '2024-10-24',
  time: '15:00',
  notes: '',
}

describe('validateAppointment', () => {
  it('accepts a complete form', () => {
    expect(validateAppointment(appointment)).toEqual({})
  })

  it('requires patient, time and the other choices', () => {
    const errors = validateAppointment({ ...appointment, patientId: '', time: '', roomId: '' })

    expect(Object.keys(errors).sort()).toEqual(['patientId', 'roomId', 'time'])
  })
})

describe('agenda geometry', () => {
  it('counts 30-minute rows from the first hour', () => {
    expect(gridOffset(agendaMock.hours, '08:00')).toBe(0)
    expect(gridOffset(agendaMock.hours, '10:30')).toBe(5)
    expect(timeOf(18 * 60)).toBe('18:00')
  })

  it('cuts a block to the visible part of each day', () => {
    const block: AgendaBlock = { id: 'b', professionalId: null, startsAt: '2024-10-24 14:00', endsAt: '2024-10-25 10:00', reason: 'Curso' }

    expect(blockSpanOnDay(block, '2024-10-23', agendaMock.hours)).toBeNull()
    expect(blockSpanOnDay(block, '2024-10-24', agendaMock.hours)).toEqual({ start: '14:00', end: '18:00' })
    expect(blockSpanOnDay(block, '2024-10-25', agendaMock.hours)).toEqual({ start: '08:00', end: '10:00' })
  })

  it('knows the dates each view shows', () => {
    expect(visibleRange('2024-10-24', 'day')).toEqual({ from: '2024-10-24', to: '2024-10-24' })
    expect(visibleRange('2024-10-24', 'week')).toEqual({ from: '2024-10-21', to: '2024-10-27' })
    expect(visibleRange('2024-10-24', 'month')).toEqual({ from: '2024-10-01', to: '2024-10-31' })
  })

  it('formats the date of an appointment', () => {
    expect(formatAppointmentDate('2024-10-24', '10:00')).toBe('Qui, 24/10 às 10:00')
  })
})

describe('validatePatient', () => {
  const valid = { name: 'Ana Souza', cpf: '', phone: '(11) 91234-5678', email: '', insurance: 'Amil', birthDate: '', notes: '' }

  it('accepts a patient without CPF and e-mail', () => {
    expect(validatePatient(valid)).toEqual({})
    expect(validatePatient({ ...valid, cpf: '12345678901', email: 'ana@email.com' })).toEqual({})
  })

  it('rejects missing or malformed fields', () => {
    const errors = validatePatient({ ...valid, name: ' ', cpf: '123', phone: '99', email: 'ana@' })

    expect(Object.keys(errors).sort()).toEqual(['cpf', 'email', 'name', 'phone'])
  })
})

describe('validateBookingContact', () => {
  const contact = { procedure: 'evaluation', professionalId: '', date: '2024-10-25', time: '09:00', name: 'Ana Souza', phone: '11912345678', email: '', notes: '', consent: true, website: '' }

  it('requires the name, a phone with area code and the consent', () => {
    expect(validateBookingContact(contact)).toEqual({})
    expect(Object.keys(validateBookingContact({ ...contact, name: 'A', phone: '1234', consent: false })).sort()).toEqual(['consent', 'name', 'phone'])
  })

  it('reads today in the browser time zone', () => {
    expect(localToday()).toMatch(/^\d{4}-\d{2}-\d{2}$/)
  })
})

describe('formatting', () => {
  it('formats CPF and phone', () => {
    expect(formatCpf('12345678901')).toBe('123.456.789-01')
    expect(formatPhone('11912345678')).toBe('(11) 91234-5678')
    expect(formatPhone('1132345678')).toBe('(11) 3234-5678')
  })

  it('builds the initials from the first and last names', () => {
    expect(patientInitials('Ana Maria Souza')).toBe('AS')
    expect(patientInitials('Ana')).toBe('A')
  })
})

describe('money', () => {
  it('reads prices typed in the Brazilian format', () => {
    expect(toCents('450')).toBe(45000)
    expect(toCents('450,5')).toBe(45050)
    expect(toCents('R$ 1.250,00')).toBe(125000)
    expect(toCents('abc')).toBeNull()
    expect(toCents('1,234')).toBeNull()
  })

  it('formats cents back for the screen and the form', () => {
    expect(formatMoney(45000)).toMatch(/R\$\s450,00/)
    expect(centsToInput(125050)).toBe('1250,50')
  })
})
