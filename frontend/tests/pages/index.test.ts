import { mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it, vi } from 'vitest'
import OverviewPage from '~/pages/app/index.vue'

describe('Visão geral', () => {
  it('loads the numbers from the API', async () => {
    const page = await mountSuspended(OverviewPage)

    expect(vi.mocked($fetch)).toHaveBeenCalledWith('/api/overview', expect.anything())
    expect(page.text()).toContain('Bom dia, Carlos')

    for (const text of ['Consultas hoje', 'R$ 4.280', '94,6%', '2,4 p.p.', 'Novos pacientes']) {
      expect(page.text()).toContain(text)
    }
  })

  it('shows whether each number went up, down or stayed', async () => {
    const page = await mountSuspended(OverviewPage)

    expect(page.findAll('[aria-label="subiu"]')).toHaveLength(2)
    expect(page.findAll('[aria-label="caiu"]')).toHaveLength(1)
    expect(page.findAll('[aria-label="estável"]')).toHaveLength(1)
  })

  it('describes the weekly chart and the occupancy for assistive technology', async () => {
    const page = await mountSuspended(OverviewPage)

    expect(page.find('[aria-label^="Agendamentos por dia"]').attributes('aria-label')).toContain('Qui: 81')
    expect(page.find('[aria-label="78% da agenda ocupada"]').exists()).toBe(true)
    expect(page.text()).toContain('22%')
  })

  it('lists today with the status recorded by the clinic', async () => {
    const page = await mountSuspended(OverviewPage)

    for (const text of ['Mariana Costa', 'Lucas Martins', 'Concluído', 'Em atendimento', 'Confirmado', 'Faltou']) {
      expect(page.text()).toContain(text)
    }
  })

  it('opens the appointment of a row', async () => {
    const page = await mountSuspended(OverviewPage)

    await page.find('[aria-label="Abrir Camila Ferreira"]').trigger('click')

    expect(useActiveModal().activeModal.value).toBe('appointment-details')
    expect(useActiveModal().modalPayload.value).toEqual({ id: '12' })
  })
})
