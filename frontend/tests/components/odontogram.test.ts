import { mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it } from 'vitest'
import Odontogram from '~/components/patients/Odontogram.vue'
import type { ToothRecord } from '~/types/clinical'
import * as odontogram from '~/utils/odontogram'

const record = (tooth: number, surface: ToothRecord['surface'], condition: ToothRecord['condition']): ToothRecord => ({
  id: `${tooth}${surface}${condition}`, tooth, surface, condition, conditionLabel: '', notes: null, author: 'Ana', createdAt: '',
})

const mountChart = (records: ToothRecord[], selected: number | null = null, deciduous = false) =>
  mountSuspended(Odontogram, { props: { records, deciduous, selected } })

describe('PatientsOdontogram', () => {
  it('draws the 32 permanent teeth, or the 20 deciduous ones', async () => {
    expect((await mountChart([])).findAll('button')).toHaveLength(32)
    expect((await mountChart([], null, true)).findAll('button')).toHaveLength(20)
  })

  it('paints each surface and names the tooth for screen readers', async () => {
    const chart = await mountChart([record(16, 'O', 'caries'), record(16, 'M', 'restoration'), record(36, null, 'missing')])
    const tooth = chart.find('[aria-label="Dente 16, cárie oclusal, restauração mesial"]')

    expect(tooth.exists()).toBe(true)
    expect(tooth.find('rect').classes()).toContain(odontogram.SURFACE_FILL.caries!.fill)
    // On tooth 16 (patient's right, upper), mesial is the right side.
    expect(tooth.findAll('polygon')[3]!.classes()).toContain(odontogram.SURFACE_FILL.restoration!.fill)
    expect(chart.find('[aria-label="Dente 36, ausente"]').text()).toContain(odontogram.TOOTH_MARK.missing!.letter)
    expect(chart.find('[aria-label="Dente 11, sem registros"] rect').classes()).toContain('fill-surface')
  })

  it('sends the tooth and the clicked surface', async () => {
    const chart = await mountChart([])
    const tooth = chart.find('[aria-label="Dente 46, sem registros"]')

    // The top polygon of a lower tooth is the lingual side; the center is the occlusal.
    await tooth.findAll('polygon')[0]!.trigger('click')
    await tooth.find('rect').trigger('click')
    await tooth.trigger('click')

    expect(chart.emitted('select')).toEqual([[46, 'L'], [46, 'O'], [46, null]])
  })

  it('marks the selected tooth and repaints when the records change', async () => {
    const chart = await mountChart([], 21)

    expect(chart.find('[aria-label="Dente 21, sem registros"]').attributes('aria-pressed')).toBe('true')

    await chart.setProps({ records: [record(21, 'V', 'caries')] })

    expect(chart.find('[aria-label="Dente 21, cárie vestibular"]').exists()).toBe(true)
  })
})
