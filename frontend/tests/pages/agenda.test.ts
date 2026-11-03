import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import AgendaPage from '~/pages/app/agenda.vue'
import { professionalSession, receptionSession } from '../fixtures/session'
import { agendaMock, appointmentDetails, requestDetails } from '../fixtures/agenda'
import { seedSession } from '../utils/session'

// Button of the view switch.
function viewButton(page: VueWrapper, label: string) {
  const button = page.findAll('[aria-label="Visualização da agenda"] button').find(item => item.text() === label)

  if (!button) {
    throw new Error(`View button not found: ${label}`)
  }

  return button
}

// Drops an event of the fixture on a new start, the way FullCalendar reports it.
function drop(page: VueWrapper, id: string, start: Date, revert: () => void) {
  const event = agendaMock.events.find(item => item.id === id)

  page.findComponent({ name: 'FullCalendarStub' }).props('options').eventDrop({
    event: { id, start, extendedProps: { kind: 'appointment', event } },
    revert,
  })
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

  it('hands the week to the calendar with its events, statuses and blocks', async () => {
    const page = await mountSuspended(AgendaPage)

    expect(page.find('h1').text()).toBe('Agenda de consultas')
    expect(page.text()).toContain('21 — 27 Outubro, 2024')

    for (const patient of ['Rafael Oliveira', 'Camila Ferreira', 'Juliana Almeida', 'Pedido Online']) {
      expect(page.text()).toContain(patient)
    }

    expect(page.find('[aria-label="Pedido Online, 15:30, Solicitado"]').classes()).toContain('border-dashed')
    expect(page.find('[aria-label="Bloqueio: Feriado municipal"]').attributes('data-display')).toBe('background')
    expect(page.find('[data-testid="calendar"]').attributes()).toMatchObject({ 'data-view': 'timeGridWeek', 'data-date': '2024-10-24' })
    expect(viewButton(page, 'Semana').attributes('aria-pressed')).toBe('true')
  })

  it('places each event by its start and end, and lets only booked ones move', async () => {
    const page = await mountSuspended(AgendaPage)
    const camila = page.find('[aria-label="Camila Ferreira, 10:00, Agendado"]')
    const request = page.find('[aria-label="Pedido Online, 15:30, Solicitado"]')

    expect(camila.attributes()).toMatchObject({ 'data-start': '2024-10-24T10:00:00', 'data-end': '2024-10-24T11:00:00', 'data-editable': 'true' })
    expect(request.attributes('data-editable')).toBe('false')
  })

  it('does not let professionals move appointments', async () => {
    seedSession(professionalSession)
    const page = await mountSuspended(AgendaPage)

    expect(page.find('[aria-label="Camila Ferreira, 10:00, Agendado"]').attributes('data-editable')).toBe('false')
  })

  it('opens an appointment when the event is clicked', async () => {
    const page = await mountSuspended(AgendaPage)

    await page.find('[aria-label="Camila Ferreira, 10:00, Agendado"]').trigger('click')

    expect(useActiveModal().activeModal.value).toBe('appointment-details')
    expect(useActiveModal().modalPayload.value).toEqual({ id: 'event-2' })
  })

  it('switches between the day and month views, and opens a day of the month', async () => {
    const page = await mountSuspended(AgendaPage)
    const calendar = () => page.find('[data-testid="calendar"]')

    await viewButton(page, 'Dia').trigger('click')
    expect(page.text()).toContain('24 Outubro, 2024')
    expect(calendar().attributes('data-view')).toBe('timeGridDay')

    await viewButton(page, 'Mês').trigger('click')
    expect(calendar().attributes('data-view')).toBe('dayGridMonth')

    page.findComponent({ name: 'FullCalendarStub' }).props('options').dateClick({ dateStr: '2024-10-25' })
    await nextTick()

    expect(calendar().attributes()).toMatchObject({ 'data-view': 'timeGridDay', 'data-date': '2024-10-25' })
  })

  it('asks a new period when the view leaves the loaded one', async () => {
    const page = await mountSuspended(AgendaPage)

    await page.find('[aria-label="Próximo período"]').trigger('click')
    expect(useAgendaRange().value).toBeNull()

    // Five more weeks reach 2 December, the first day after the loaded period.
    for (let week = 0; week < 5; week++) {
      await page.find('[aria-label="Próximo período"]').trigger('click')
    }

    expect(useAgendaRange().value).toEqual({ from: '2024-12-02', to: '2025-01-12' })
  })

  it('asks before saving a dragged appointment and saves the new time', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
    const update = vi.spyOn(appointmentsService, 'update').mockResolvedValue(appointmentDetails)
    const page = await mountSuspended(AgendaPage)
    const revert = vi.fn()

    drop(page, 'event-2', new Date(2024, 9, 25, 9, 30), revert)
    await nextTick()

    expect(page.find('dialog').text()).toContain('Camila Ferreira passa para Sex, 25/10 às 09:30')

    await page.find('dialog form').trigger('submit')
    await flushPromises()

    expect(update).toHaveBeenCalledWith('event-2', expect.objectContaining({ date: '2024-10-25', time: '09:30', patientId: 'patient-1', roomId: 'room-1' }))
    expect(revert).not.toHaveBeenCalled()
    expect(useToast().message.value).toBe('Consulta de Camila Ferreira remarcada.')
  })

  it('puts the appointment back when the move is cancelled or refused', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
    vi.spyOn(appointmentsService, 'update').mockRejectedValue({ data: { message: 'Inválido', errors: { professional_id: ['Este profissional já tem consulta neste horário.'] } } })
    const page = await mountSuspended(AgendaPage)
    const cancelled = vi.fn()
    const refused = vi.fn()

    drop(page, 'event-2', new Date(2024, 9, 25, 9, 30), cancelled)
    await nextTick()
    await page.findAll('dialog button').find(item => item.text() === 'Cancelar')?.trigger('click')
    expect(cancelled).toHaveBeenCalled()

    drop(page, 'event-2', new Date(2024, 9, 25, 14, 0), refused)
    await nextTick()
    await page.find('dialog form').trigger('submit')
    await flushPromises()

    expect(refused).toHaveBeenCalled()
    expect(useToast().message.value).toBe('Este profissional já tem consulta neste horário.')
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
