import { describe, expect, it } from 'vitest'
import { blockLabel, dropTarget, eventLabel, slotBounds, toCalendarBlocks, toCalendarEvents } from '~/utils/calendar'
import { agendaMock } from '../fixtures/agenda'

describe('calendar mapping', () => {
  it('turns appointments into events with start, end and the label read aloud', () => {
    const [camila] = toCalendarEvents(agendaMock.events.filter(event => event.id === 'event-2'), true)

    expect(camila).toMatchObject({
      id: 'event-2',
      start: '2024-10-24T10:00:00',
      end: '2024-10-24T11:00:00',
      startEditable: true,
      durationEditable: false,
      extendedProps: { kind: 'appointment', label: 'Camila Ferreira, 10:00, Agendado' },
    })
  })

  it('lets only booked appointments move, and only for who manages', () => {
    const editable = (canManage: boolean) => toCalendarEvents(agendaMock.events, canManage).map(event => [event.id, event.startEditable])

    expect(editable(true)).toEqual([['event-1', true], ['event-2', true], ['event-3', true], ['event-4', false]])
    expect(editable(false).every(([, value]) => value === false)).toBe(true)
  })

  it('adds the payment to the label', () => {
    const [event] = agendaMock.events

    expect(eventLabel({ ...event!, paymentStatus: 'paid' })).toBe('Rafael Oliveira, 09:00, Confirmado, Pago')
    expect(eventLabel({ ...event!, paymentStatus: 'pending' })).toBe('Rafael Oliveira, 09:00, Confirmado')
  })

  it('draws blocks as background, with ISO dates and the professional', () => {
    const block = { id: 'b1', professionalId: 'ana', startsAt: '2024-10-24 14:00', endsAt: '2024-10-25 10:00', reason: 'Curso' }
    const [mapped] = toCalendarBlocks([block], agendaMock.professionals)

    expect(mapped).toMatchObject({ start: '2024-10-24T14:00', end: '2024-10-25T10:00', display: 'background' })
    expect(blockLabel(block, agendaMock.professionals)).toBe('Curso · Dra. Ana Beatriz')
  })

  it('ends the grid half an hour after the last row', () => {
    expect(slotBounds(['08:00', '08:30', '17:30'])).toEqual({ slotMinTime: '08:00:00', slotMaxTime: '18:00:00' })
  })

  it('reads a dropped start in local time', () => {
    expect(dropTarget(new Date(2024, 9, 5, 9, 0))).toEqual({ date: '2024-10-05', time: '09:00' })
  })
})
