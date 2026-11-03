import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import PatientPage from '~/pages/consulta.vue'
import type { PatientAppointment } from '~/types/catalog'
import { clearSession } from '../utils/session'

// The page lives on the clinic host; the test runs on the central one, so the host is simulated.
mockNuxtImport('useClinicHost', () => () => ({
  isClinicHost: true,
  slug: 'sorriso-perfeito',
  centralUrl: (path: string) => `http://localhost:3000${path}`,
  clinicUrl: (slug: string, path: string) => `http://${slug}.localhost:3000${path}`,
}))

const scheduled: PatientAppointment = {
  clinic: { name: 'Sorriso Perfeito', address: 'Rua Oscar Freire, 1240', phone: '(11) 3051-8090' },
  when: 'terça-feira, 10/11 às 09:00',
  procedure: 'Limpeza e prevenção',
  professional: 'Dra. Ana Beatriz',
  status: 'scheduled',
  statusLabel: 'Agendado',
  canConfirm: true,
  canCancel: true,
}

// Button by its text.
function button(root: VueWrapper, text: string) {
  return root.findAll('button').find(item => item.text() === text)
}

describe('patient link page', () => {
  it('shows the appointment of the link without a session', async () => {
    clearSession()
    const get = vi.spyOn(patientLinksService, 'get').mockResolvedValue(scheduled)

    const page = await mountSuspended(PatientPage, { route: '/consulta?token=7-abc' })

    expect(get).toHaveBeenCalledWith('7-abc')
    expect(page.text()).toContain('terça-feira, 10/11 às 09:00')
    expect(page.text()).toContain('Dra. Ana Beatriz')
    expect(page.text()).toContain('Rua Oscar Freire, 1240')
  })

  it('confirms the attendance', async () => {
    vi.spyOn(patientLinksService, 'get').mockResolvedValue(scheduled)
    const confirm = vi.spyOn(patientLinksService, 'confirm').mockResolvedValue({ ...scheduled, status: 'confirmed', statusLabel: 'Confirmado', canConfirm: false })

    const page = await mountSuspended(PatientPage, { route: '/consulta?token=7-abc' })
    await button(page, 'Confirmo presença')?.trigger('click')
    await flushPromises()

    expect(confirm).toHaveBeenCalledWith('7-abc')
    expect(page.text()).toContain('Presença confirmada')
    expect(button(page, 'Confirmo presença')).toBeUndefined()
  })

  it('asks a second click before cancelling', async () => {
    vi.spyOn(patientLinksService, 'get').mockResolvedValue(scheduled)
    const cancel = vi.spyOn(patientLinksService, 'cancel').mockResolvedValue({ ...scheduled, status: 'cancelled', statusLabel: 'Cancelado', canConfirm: false, canCancel: false })

    const page = await mountSuspended(PatientPage, { route: '/consulta?token=7-abc' })
    await button(page, 'Preciso cancelar')?.trigger('click')

    expect(cancel).not.toHaveBeenCalled()

    await button(page, 'Sim, cancelar a consulta')?.trigger('click')
    await flushPromises()

    expect(page.text()).toContain('Consulta cancelada. O horário foi liberado.')
  })

  it('explains an invalid or expired link', async () => {
    vi.spyOn(patientLinksService, 'get').mockRejectedValue(Object.assign(new Error('missing'), { statusCode: 404 }))

    const page = await mountSuspended(PatientPage, { route: '/consulta?token=velho' })

    expect(page.text()).toContain('Link inválido ou expirado')
  })

  it('explains a link without token', async () => {
    const page = await mountSuspended(PatientPage, { route: '/consulta' })

    expect(page.text()).toContain('Link inválido ou expirado')
  })
})
