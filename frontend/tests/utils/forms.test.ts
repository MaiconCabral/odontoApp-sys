import { describe, expect, it } from 'vitest'
import { agendaMock } from '~/mocks/agenda'
import { patientsMock } from '~/mocks/patients'
import type { NewAppointmentForm } from '~/utils/appointments'
import { isRoomTaken, validateNewAppointment } from '~/utils/appointments'
import { formatCpf, formatPhone, patientInitials, validateNewPatient } from '~/utils/patients'

const appointment: NewAppointmentForm = {
  patientName: 'Mariana Costa',
  procedure: 'Avaliação',
  professionalId: 'felipe',
  date: '2024-10-24',
  hour: '15:00',
  roomId: 'room-1',
}

describe('validateNewAppointment', () => {
  const names = ['Mariana Costa']

  it('accepts a free slot with a registered patient', () => {
    expect(validateNewAppointment(appointment, names, agendaMock.events)).toEqual({})
  })

  it('requires a registered patient and a date', () => {
    const errors = validateNewAppointment({ ...appointment, patientName: 'Fulano', date: '' }, names, agendaMock.events)

    expect(errors.patientName).toBeDefined()
    expect(errors.date).toBeDefined()
  })

  it('rejects a taken room and a busy professional', () => {
    const errors = validateNewAppointment(
      { ...appointment, hour: '10:00', professionalId: 'carlos' },
      names,
      agendaMock.events,
    )

    expect(errors.roomId).toBeDefined()
    expect(errors.professionalId).toBeDefined()
  })
})

describe('isRoomTaken', () => {
  it('checks date, hour and room together', () => {
    expect(isRoomTaken(agendaMock.events, '2024-10-24', '10:00', 'room-1')).toBe(true)
    expect(isRoomTaken(agendaMock.events, '2024-10-24', '10:00', 'room-2')).toBe(false)
  })
})

describe('validateNewPatient', () => {
  const valid = { name: 'Ana Souza', cpf: '123.456.789-01', phone: '(11) 91234-5678', insurance: 'Amil' }

  it('accepts valid data with or without formatting', () => {
    expect(validateNewPatient(valid, patientsMock.patients)).toEqual({})
    expect(validateNewPatient({ ...valid, cpf: '12345678901', phone: '1132345678' }, patientsMock.patients)).toEqual({})
  })

  it('rejects missing or malformed fields', () => {
    const errors = validateNewPatient({ ...valid, name: ' ', cpf: '123', phone: '99' }, patientsMock.patients)

    expect(Object.keys(errors).sort()).toEqual(['cpf', 'name', 'phone'])
  })

  it('rejects a CPF that is already registered', () => {
    expect(validateNewPatient({ ...valid, cpf: '00000000001' }, patientsMock.patients).cpf).toContain('Já existe')
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
