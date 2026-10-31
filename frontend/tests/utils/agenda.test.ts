import { describe, expect, it } from 'vitest'
import { addMonths, formatRange, monthGrid, shiftDate, weekDates, weekdayLabel } from '~/utils/agenda'

describe('weekDates', () => {
  it('starts the week on Monday', () => {
    expect(weekDates('2024-10-24')[0]).toBe('2024-10-21')
    expect(weekDates('2024-10-24')[6]).toBe('2024-10-27')
  })

  it('keeps Sunday in the week that started on the previous Monday', () => {
    expect(weekDates('2024-10-27')[0]).toBe('2024-10-21')
  })
})

describe('weekdayLabel', () => {
  it('returns the Portuguese label', () => {
    expect(weekdayLabel('2024-10-24')).toBe('Qui')
    expect(weekdayLabel('2024-10-27')).toBe('Dom')
  })
})

describe('addMonths', () => {
  it('clamps to the last day of a shorter month', () => {
    expect(addMonths('2024-01-31', 1)).toBe('2024-02-29')
  })

  it('crosses the year', () => {
    expect(addMonths('2024-12-15', 1)).toBe('2025-01-15')
  })
})

describe('shiftDate', () => {
  it('moves by the size of the view', () => {
    expect(shiftDate('2024-10-24', 'day', 1)).toBe('2024-10-25')
    expect(shiftDate('2024-10-24', 'week', -1)).toBe('2024-10-17')
    expect(shiftDate('2024-10-24', 'month', 1)).toBe('2024-11-24')
  })
})

describe('formatRange', () => {
  it('formats a week inside one month', () => {
    expect(formatRange('2024-10-24', 'week')).toBe('21 — 27 Outubro, 2024')
  })

  it('formats a week across two months', () => {
    expect(formatRange('2024-10-30', 'week')).toBe('28 Outubro — 3 Novembro, 2024')
  })

  it('formats a week across two years', () => {
    expect(formatRange('2024-12-31', 'week')).toBe('30 Dezembro, 2024 — 5 Janeiro, 2025')
  })

  it('formats the day and month views', () => {
    expect(formatRange('2024-10-24', 'day')).toBe('24 Outubro, 2024')
    expect(formatRange('2024-10-24', 'month')).toBe('Outubro, 2024')
  })
})

describe('monthGrid', () => {
  it('fills the days outside the month to complete the weeks', () => {
    const grid = monthGrid('2024-10-24')

    expect(grid.length % 7).toBe(0)
    expect(grid[0]).toBeNull()
    expect(grid[1]).toBe('2024-10-01')
    expect(grid.filter(Boolean)).toHaveLength(31)
  })
})
