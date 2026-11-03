import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import BookingPage from '~/pages/agendar.vue'
import { clearSession } from '../utils/session'

// The page lives on the clinic host; the test runs on the central one, so the host is simulated.
mockNuxtImport('useClinicHost', () => () => ({
  isClinicHost: true,
  slug: 'sorriso-perfeito',
  centralUrl: (path: string) => `http://localhost:3000${path}`,
  clinicUrl: (slug: string, path: string) => `http://${slug}.localhost:3000${path}`,
}))

// Control linked to a label.
function fieldByLabel(root: VueWrapper, label: string) {
  const element = root.findAll('label').find(item => item.text() === label)

  return root.find(`[id="${element?.attributes('for')}"]`)
}

// Button by its text.
function button(root: VueWrapper, text: string) {
  return root.findAll('button').find(item => item.text() === text)
}

// Goes through the first two steps.
async function chooseTime(page: VueWrapper) {
  await page.find('input[value="1"]').setValue(true)
  await button(page, 'Escolher horário')?.trigger('click')
  await fieldByLabel(page, 'Data').setValue('2099-01-05')
  await flushPromises()
  await button(page, '09:30')?.trigger('click')
  await button(page, 'Continuar')?.trigger('click')
}

// Fills the contact step.
async function fillContact(page: VueWrapper) {
  await fieldByLabel(page, 'Nome completo').setValue('Paciente Novo')
  await fieldByLabel(page, 'Telefone com DDD').setValue('11999990000')
  await page.find('input[type="checkbox"]').setValue(true)
}

describe('public booking from the waiting list link', () => {
  it('opens on the times with the offered one chosen', async () => {
    clearSession()
    const date = addDays(localToday(), 2)
    const page = await mountSuspended(BookingPage, { route: `/agendar?date=${date}&time=09:30&professional_id=ana&procedure_id=1` })

    await flushPromises()

    expect((fieldByLabel(page, 'Data').element as HTMLInputElement).value).toBe(date)
    expect(button(page, '09:30')?.classes()).toContain('bg-brand')
  })

  it('ignores a link with values the clinic does not offer', async () => {
    clearSession()
    const page = await mountSuspended(BookingPage, { route: '/agendar?date=2000-01-01&time=09:30&procedure_id=999' })

    await flushPromises()

    expect(page.text()).toContain('Escolher horário')
  })
})

describe('public booking', () => {
  it('shows the clinic, its procedures and the professionals without a session', async () => {
    clearSession()

    const page = await mountSuspended(BookingPage)

    expect(page.text()).toContain('Agendar em Sorriso Perfeito')
    expect(page.text()).toContain('Restauração estética')
    expect(page.text()).toContain('90 min')
    expect(fieldByLabel(page, 'Profissional').findAll('option').map(option => option.text())).toEqual([
      'Sem preferência',
      'Dr. Carlos Mendes · Ortodontia',
      'Dra. Ana Beatriz',
    ])
    expect(button(page, 'Escolher horário')?.attributes('disabled')).toBeDefined()
  })

  it('lists the free times of the day and sends the request', async () => {
    const request = vi.spyOn(publicBookingService, 'request').mockResolvedValue({ message: 'Recebemos seu pedido.' })
    const page = await mountSuspended(BookingPage)

    await chooseTime(page)

    expect(page.text()).toContain('Avaliação · Seg, 05/01 às 09:30 · Sem preferência')

    await fillContact(page)
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(request).toHaveBeenCalledWith(expect.objectContaining({ procedure: '1', date: '2099-01-05', time: '09:30', name: 'Paciente Novo', consent: true, website: '' }))
    expect(page.text()).toContain('Pedido enviado')
    expect(page.text()).toContain('Recebemos seu pedido.')
  })

  it('validates the contact before sending', async () => {
    const request = vi.spyOn(publicBookingService, 'request')
    const page = await mountSuspended(BookingPage)

    await chooseTime(page)
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(page.text()).toContain('Informe seu nome completo.')
    expect(page.text()).toContain('Autorize o contato da clínica para continuar.')
    expect(request).not.toHaveBeenCalled()
  })

  it('goes back to the times when the chosen one was taken meanwhile', async () => {
    vi.spyOn(publicBookingService, 'request').mockRejectedValue({
      data: { message: 'Inválido', errors: { professionalId: ['Este profissional já tem consulta neste horário.'] } },
    })
    const page = await mountSuspended(BookingPage)

    await chooseTime(page)
    await fillContact(page)
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(page.text()).toContain('Etapa 2 de 3')
    expect(page.text()).toContain('Este profissional já tem consulta neste horário.')
  })
})
