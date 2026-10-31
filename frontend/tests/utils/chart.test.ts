import { describe, expect, it } from 'vitest'
import { barHeightPercent } from '~/utils/chart'

describe('barHeightPercent', () => {
  it('rounds the value to a percentage of the scale', () => {
    expect(barHeightPercent(81, 110)).toBe(74)
    expect(barHeightPercent(24, 110)).toBe(22)
  })

  it('keeps the result between 0 and 100', () => {
    expect(barHeightPercent(200, 110)).toBe(100)
    expect(barHeightPercent(-5, 110)).toBe(0)
  })

  it('returns 0 when the scale is not positive', () => {
    expect(barHeightPercent(10, 0)).toBe(0)
  })
})
