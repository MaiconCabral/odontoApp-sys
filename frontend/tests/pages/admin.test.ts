import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import platformAuth from '~/middleware/platform-auth'
import ClinicPage from '~/pages/admin/clinicas/[id].vue'
import ClinicsPage from '~/pages/admin/index.vue'
import LoginPage from '~/pages/admin/login.vue'
import PlansPage from '~/pages/admin/planos.vue'
import { platformClinicFixture } from '../fixtures/platform'

const host = vi.hoisted(() => ({ isClinicHost: false }))

// The panel lives on the central domain; some tests move to a clinic host.
mockNuxtImport('useClinicHost', () => () => ({
  isClinicHost: host.isClinicHost,
  slug: host.isClinicHost ? 'sorriso-perfeito' : null,
  centralUrl: (path: string) => `http://localhost:3000${path}`,
  clinicUrl: (slug: string, path: string) => `http://${slug}.localhost:3000${path}`,
}))

// Control linked to a label.
function fieldByLabel(root: VueWrapper, label: string) {
  const element = root.findAll('label').find(item => item.text() === label)

  return root.find(`[id="${element?.attributes('for')}"]`)
}

describe('global panel', () => {
  beforeEach(() => {
    host.isClinicHost = false
    clearNuxtData()
    useState('platform-admin').value = null
    useState('platform-session-loaded').value = false
  })

  it('lists the clinics with status and usage', async () => {
    const page = await mountSuspended(ClinicsPage)

    await flushPromises()

    expect(page.find('table').text()).toContain('Sorriso Perfeito')
    expect(page.find('table').text()).toContain('Trial · 14 dias')
    expect(page.find('table').text()).toContain('87')
    // Pages sharing the "platform-clinics" key would reuse this handler; the app shows one at a time.
    page.unmount()
  })

  it('filters by status from the first page', async () => {
    const clinics = vi.spyOn(platformService, 'clinics')
    const page = await mountSuspended(ClinicsPage)

    await flushPromises()
    await page.findAll('select').at(1)?.setValue('suspended')
    await flushPromises()

    expect(clinics).toHaveBeenLastCalledWith(expect.objectContaining({ status: 'suspended', page: 1 }))
  })

  it('suspends a clinic and changes its plan', async () => {
    const update = vi.spyOn(platformService, 'updateClinic').mockResolvedValue({ ...platformClinicFixture, status: 'suspended' })
    const page = await mountSuspended(ClinicPage, { route: '/admin/clinicas/c1' })

    await flushPromises()
    await fieldByLabel(page, 'Plano').setValue('3')
    await page.find('input[type="checkbox"]').setValue(true)
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(update).toHaveBeenCalledWith('c1', { planId: '3', trialEndsAt: '2024-11-07', suspended: true })
    expect(page.text()).toContain('Suspensa')
  })

  it('creates a plan without a limit of professionals', async () => {
    const create = vi.spyOn(platformService, 'createPlan').mockResolvedValue(undefined as never)
    const page = await mountSuspended(PlansPage)

    await flushPromises()
    await page.findAll('button').find(item => item.text().includes('Novo plano'))?.trigger('click')
    await fieldByLabel(page, 'Código').setValue('clinica-grande')
    await fieldByLabel(page, 'Nome').setValue('Grande')
    await fieldByLabel(page, 'Preço por mês (R$)').setValue('999,00')
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(create).toHaveBeenCalledWith({ code: 'clinica-grande', name: 'Grande', price: '999,00', maxProfessionals: '' })
  })

  it('shows the message when a plan in use cannot be deleted', async () => {
    vi.spyOn(platformService, 'removePlan').mockRejectedValue({ data: { message: 'Inválido', errors: { plan: ['Há clínicas neste plano.'] } } })
    const page = await mountSuspended(PlansPage)

    await flushPromises()
    await page.find('[aria-label="Excluir Pro"]').trigger('click')
    await page.find('[aria-label="Confirmar exclusão de Pro"]').trigger('click')
    await flushPromises()

    expect(useToast().message.value).toBe('Há clínicas neste plano.')
  })

  it('signs in with the typed credentials', async () => {
    const login = vi.spyOn(platformService, 'login').mockResolvedValue({ id: '1', name: 'Equipe', email: 'equipe@plataforma.app' })
    const page = await mountSuspended(LoginPage, { route: '/admin/login' })

    await fieldByLabel(page, 'E-mail').setValue('equipe@plataforma.app')
    await fieldByLabel(page, 'Senha').setValue('Plataforma123')
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(login).toHaveBeenCalledWith('equipe@plataforma.app', 'Plataforma123')
    expect(usePlatformSession().admin.value?.name).toBe('Equipe')
  })

  it('keeps the redirect after the login inside the panel', () => {
    expect(safePanelRedirect('/admin/planos')).toBe('/admin/planos')
    expect(safePanelRedirect('https://evil.example')).toBe('/admin')
    expect(safePanelRedirect('/administrador-falso')).toBe('/admin')
    expect(safePanelRedirect('//evil.example/admin')).toBe('/admin')
    expect(safePanelRedirect(undefined)).toBe('/admin')
  })

  it('sends visitors without a platform session to the panel login', async () => {
    vi.spyOn(platformService, 'session').mockResolvedValue(null)

    const result = await platformAuth({ fullPath: '/admin/planos' } as never, {} as never)

    expect(result).toMatchObject({ path: '/admin/login', query: { redirect: '/admin/planos' } })
  })

  it('leaves a clinic host before asking for the platform session', async () => {
    host.isClinicHost = true
    const session = vi.spyOn(platformService, 'session')

    await platformAuth({ fullPath: '/admin' } as never, {} as never)

    expect(session).not.toHaveBeenCalled()
  })
})
