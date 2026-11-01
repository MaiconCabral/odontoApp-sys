import { mountSuspended } from '@nuxt/test-utils/runtime'
import type { VueWrapper } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AgendaPage from '~/pages/app/[tenant]/agenda.vue'

function viewButton(page: VueWrapper, label: string) {
  const button = page.findAll('[aria-label="Visualização da agenda"] button').find(item => item.text() === label)

  if (!button) {
    throw new Error(`View button not found: ${label}`)
  }

  return button
}

describe('Agenda', () => {
  it('renders the week with its events and marks today', async () => {
    const page = await mountSuspended(AgendaPage)

    expect(page.find('h1').text()).toBe('Agenda de consultas')
    expect(page.text()).toContain('21 — 27 Outubro, 2024')

    for (const patient of ['Rafael Oliveira', 'Camila Ferreira', 'Juliana Almeida']) {
      expect(page.text()).toContain(patient)
    }

    expect(page.find('[aria-current="date"]').text()).toContain('24')
    expect(viewButton(page, 'Semana').attributes('aria-pressed')).toBe('true')
  })

  it('switches between the day and month views', async () => {
    const page = await mountSuspended(AgendaPage)

    await viewButton(page, 'Dia').trigger('click')
    expect(page.text()).toContain('24 Outubro, 2024')
    expect(page.text()).toContain('Camila Ferreira')
    expect(page.text()).not.toContain('Rafael Oliveira')

    await viewButton(page, 'Mês').trigger('click')
    expect(page.find('[data-testid="month-grid"]').exists()).toBe(true)
    expect(page.find('[data-testid="time-grid"]').exists()).toBe(false)
    expect(page.findAll('[data-testid="month-grid"] i')).toHaveLength(3)
  })

  it('hides the events of the next week until returning to today', async () => {
    const page = await mountSuspended(AgendaPage)

    await page.find('[aria-label="Próximo período"]').trigger('click')
    expect(page.text()).not.toContain('Camila Ferreira')

    const today = page.findAll('button').find(button => button.text() === 'Hoje')
    await today?.trigger('click')
    expect(page.text()).toContain('Camila Ferreira')
  })

  it('filters by professional and toggles everyone at once', async () => {
    const page = await mountSuspended(AgendaPage)
    const [all, carlos] = page.findAll('aside input[type="checkbox"]')

    expect((all?.element as HTMLInputElement).indeterminate).toBe(true)

    await carlos?.setValue(false)
    expect(page.text()).not.toContain('Camila Ferreira')
    expect(page.text()).toContain('Rafael Oliveira')

    await all?.trigger('change')
    expect((all?.element as HTMLInputElement).checked).toBe(true)
    expect(page.text()).toContain('Camila Ferreira')

    await all?.trigger('change')
    expect(page.text()).not.toContain('Rafael Oliveira')
  })
})
