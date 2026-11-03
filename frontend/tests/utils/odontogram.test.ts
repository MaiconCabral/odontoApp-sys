import { describe, expect, it } from 'vitest'
import type { ToothRecord } from '~/types/clinical'
import { surfaceSides, toothLabel, toothState } from '~/utils/odontogram'

const record = (tooth: number, surface: ToothRecord['surface'], condition: ToothRecord['condition']): ToothRecord => ({
  id: `${tooth}${surface}`, tooth, surface, condition, conditionLabel: '', notes: null, author: 'Ana', createdAt: '',
})

describe('odontogram', () => {
  it('puts vestibular outside the mouth and mesial toward the middle', () => {
    expect(surfaceSides(16)).toEqual({ top: 'V', bottom: 'L', left: 'D', right: 'M' })
    expect(surfaceSides(26)).toEqual({ top: 'V', bottom: 'L', left: 'M', right: 'D' })
    expect(surfaceSides(46)).toEqual({ top: 'L', bottom: 'V', left: 'D', right: 'M' })
    expect(surfaceSides(75)).toEqual({ top: 'L', bottom: 'V', left: 'M', right: 'D' })
  })

  it('splits the state of a tooth into whole and surfaces', () => {
    const state = toothState([record(16, 'O', 'caries'), record(16, null, 'crown'), record(17, 'M', 'restoration')], 16)

    expect(state.whole?.condition).toBe('crown')
    expect(Object.keys(state.surfaces)).toEqual(['O'])
  })

  it('describes the tooth for screen readers, leaving healthy surfaces out', () => {
    const records = [record(16, 'O', 'caries'), record(16, 'M', 'restoration'), record(16, 'D', 'healthy')]

    expect(toothLabel(records, 16)).toBe('Dente 16, cárie oclusal, restauração mesial')
    expect(toothLabel(records, 11)).toBe('Dente 11, sem registros')
  })
})
