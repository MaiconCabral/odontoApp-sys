import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import AgendaPage from '~/pages/app/agenda.vue'
import { professionalSession, receptionSession } from '../fixtures/session'
import { requestDetails } from '../fixtures/agenda'
import { seedSession } from '../utils/session'

// Button of the view switch.
function viewButton(page: VueWrapper, label: string) {
  const button = page.findAll('[aria-label="Visualização da agenda"] button').find(item => item.text() === label)

  if (!button) {
    throw new Error(`View button not found: ${label}`)
  }

  return button
}

describe('Agenda', () => {
  it('offers scheduling and blocks to everyone except professionals', async () => {
    const admin = await mountSuspended(AgendaPage)

    expect(admin.text()).toContain('Novo agendamento')
    expect(admin.text()).toContain('Bloquear horário')

    seedSession(receptionSession)
    expect((await mountSuspended(AgendaPage)).text()).toContain('Novo agendamento')

    seedSession(professionalSession)
    const professional = await mountSuspended(AgendaPage)

    expect(professional.text()).not.toContain('Novo agendamento')
    expect(professional.text()).not.toContain('Bloquear horário')
  })

  it('renders the week with its events, statuses and blocks', async () => {
    const page = await mountSuspended(AgendaPage)

    expect(page.find('h1').text()).toBe('Agenda de consultas')
    expect(page.text()).toContain('21 — 27 Outubro, 2024')

    for (const patient of ['Rafael Oliveira', 'Camila Ferreira', 'Juliana Almeida', 'Pedido Online']) {
      expect(page.text()).toContain(patient)
    }

    expect(page.find('[aria-label="Pedido Online, 15:30, Solicitado"]').classes()).toContain('border-dashed')
    expect(page.find('[aria-label="Bloqueio: Feriado municipal"]').exists()).toBe(true)
    expect(page.find('[aria-current="date"]').text()).toContain('24')
    expect(viewButton(page, 'Semana').attributes('aria-pressed')).toBe('true')
  })

  it('sizes the events by duration on 30-minute rows', async () => {
    const page = await mountSuspended(AgendaPage)
    const camila = page.find('[aria-label="Camila Ferreira, 10:00, Agendado"]')
    const request = page.find('[aria-label="Pedido Online, 15:30, Solicitado"]')

    // 10:00 is the fifth row (index 4) of 30 px; one hour takes two rows.
    expect(camila.attributes('style')).toContain('top: 122px')
    expect(camila.attributes('style')).toContain('height: 56px')
    expect(request.attributes('style')).toContain('height: 26px')
  })

  it('opens an appointment when the event is clicked', async () => {
    const page = await mountSuspended(AgendaPage)

    await page.find('[aria-label="Camila Ferreira, 10:00, Agendado"]').trigger('click')

    expect(useActiveModal().activeModal.value).toBe('appointment-details')
    expect(useActiveModal().modalPayload.value).toEqual({ id: 'event-2' })
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
    expect(page.findAll('[data-testid="day-count"]').map(count => count.text())).toEqual(['1 consulta', '1 consulta', '2 consultas'])
  })

  it('asks a new period when the view leaves the loaded one', async () => {
    const page = await mountSuspended(AgendaPage)

    await page.find('[aria-label="Próximo período"]').trigger('click')
    expect(useAgendaRange().value).toBeNull()
    expect(page.text()).not.toContain('Camila Ferreira')

    // Five more weeks reach 2 December, the first day after the loaded period.
    for (let week = 0; week < 5; week++) {
      await page.find('[aria-label="Próximo período"]').trigger('click')
    }

    expect(useAgendaRange().value).toEqual({ from: '2024-12-02', to: '2025-01-12' })
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

  it('lists the requests waiting for the reception', async () => {
    vi.mocked($fetch).mockImplementation((async (path: string) => {
      if (path === '/api/appointment-requests') {
        return { data: [requestDetails] }
      }

      return path === '/api/agenda' ? structuredClone((await import('../fixtures/agenda')).agendaMock) : {}
    }) as never)

    const page = await mountSuspended(AgendaPage)
    await flushPromises()

    expect(page.text()).toContain('1 pedido de agendamento aguardando')

    await page.findAll('button').find(button => button.text() === 'Responder')?.trigger('click')

    expect(useActiveModal().modalPayload.value).toEqual({ id: 'event-4' })
  })
})
