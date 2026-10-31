import { mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it } from 'vitest'
import OverviewPage from '~/pages/index.vue'

describe('Visão geral', () => {
  it('renders every metric', async () => {
    const page = await mountSuspended(OverviewPage)

    for (const text of ['Consultas hoje', 'R$ 4.280', '94,6%', 'Novos pacientes']) {
      expect(page.text()).toContain(text)
    }
  })

  it('describes the weekly chart and the occupancy for assistive technology', async () => {
    const page = await mountSuspended(OverviewPage)

    expect(page.find('[aria-label^="Agendamentos por dia"]').attributes('aria-label')).toContain('Qui: 81')
    expect(page.find('[aria-label="78% da agenda ocupada"]').exists()).toBe(true)
    expect(page.text()).toContain('22%')
  })

  it('lists the upcoming patients with their status labels', async () => {
    const page = await mountSuspended(OverviewPage)

    for (const text of ['Mariana Costa', 'Lucas Martins', 'Concluído', 'Em atendimento', 'Aguardando']) {
      expect(page.text()).toContain(text)
    }
  })
})
