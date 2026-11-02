import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { adminSession, professionalSession } from '../fixtures/session'
import { clearSession } from '../utils/session'

const { navigateToMock, hostMock } = vi.hoisted(() => ({
  navigateToMock: vi.fn(),
  hostMock: { isClinicHost: true },
}))

mockNuxtImport('navigateTo', () => navigateToMock)
mockNuxtImport('useClinicHost', () => () => hostMock)

const credentials = { email: 'demo@sorriso.app', password: 'Sorriso123' }

describe('useAuth', () => {
  beforeEach(() => {
    navigateToMock.mockClear()
    hostMock.isClinicHost = true
    clearSession()
    useAuthStore().isLoaded = false

    vi.spyOn(authService, 'login').mockResolvedValue(adminSession)
    vi.spyOn(authService, 'logout').mockResolvedValue()
    vi.spyOn(authService, 'getSession').mockReset().mockResolvedValue(adminSession)
  })

  it('signs in and opens the panel', async () => {
    await useAuth().login(credentials)

    expect(useAuthStore().isAuthenticated).toBe(true)
    expect(useTenantStore().slug).toBe('sorriso-perfeito')
    expect(navigateToMock).toHaveBeenCalledWith('/app')
  })

  it('opens the agenda for a professional', async () => {
    vi.spyOn(authService, 'login').mockResolvedValue(professionalSession)

    await useAuth().login(credentials)

    expect(navigateToMock).toHaveBeenCalledWith('/app/agenda')
  })

  it('returns to the requested panel page after signing in', async () => {
    await useAuth().login(credentials, '/app/pacientes')

    expect(navigateToMock).toHaveBeenCalledWith('/app/pacientes')
  })

  it('ignores a redirect to another site', async () => {
    await useAuth().login(credentials, '//site.com')

    expect(navigateToMock).toHaveBeenCalledWith('/app')
  })

  it('does not create a session when the credentials are refused', async () => {
    vi.spyOn(authService, 'login').mockRejectedValue(new Error('refused'))

    await expect(useAuth().login(credentials)).rejects.toThrow('refused')

    expect(useAuthStore().isAuthenticated).toBe(false)
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('sends a new clinic to the login on its own host', async () => {
    vi.spyOn(authService, 'register').mockResolvedValue({
      slug: 'clinica-sao-jose',
      loginUrl: 'http://clinica-sao-jose.localhost:3000/login?cadastro=1',
    })

    await useAuth().register({
      name: 'Ana Souza',
      email: 'ana@clinica.com',
      password: 'senhaSegura1',
      passwordConfirmation: 'senhaSegura1',
      clinicName: 'Clínica São José',
      unit: '',
    })

    expect(navigateToMock).toHaveBeenCalledWith('http://clinica-sao-jose.localhost:3000/login?cadastro=1', { external: true })
    expect(useAuthStore().isAuthenticated).toBe(false)
  })

  it('loads the session only once', async () => {
    await useAuth().loadSession()
    await useAuth().loadSession()

    expect(authService.getSession).toHaveBeenCalledTimes(1)
    expect(useAuthStore().user?.email).toBe('demo@sorriso.app')
  })

  it('does not call the API on the central domain', async () => {
    hostMock.isClinicHost = false

    await useAuth().loadSession()

    expect(authService.getSession).not.toHaveBeenCalled()
    expect(useAuthStore().isLoaded).toBe(true)
    expect(useAuthStore().isAuthenticated).toBe(false)
  })

  it('treats a failure while loading the session as signed out', async () => {
    vi.spyOn(authService, 'getSession').mockRejectedValue(new Error('offline'))

    await useAuth().loadSession()

    expect(useAuthStore().isAuthenticated).toBe(false)
    expect(useAuthStore().isLoaded).toBe(true)
    expect(useAuthStore().clinicNotFound).toBe(false)
  })

  it('flags a clinic that does not exist', async () => {
    vi.spyOn(authService, 'getSession').mockRejectedValue(Object.assign(new Error('missing'), { statusCode: 404 }))

    await useAuth().loadSession()

    expect(useAuthStore().clinicNotFound).toBe(true)
  })

  it('clears the session and the agenda filters on logout', async () => {
    await useAuth().login(credentials)
    useAgendaFiltersStore().selectedRooms = ['sala-1']

    await useAuth().logout()

    expect(authService.logout).toHaveBeenCalled()
    expect(useAuthStore().isAuthenticated).toBe(false)
    expect(useTenantStore().tenant).toBeNull()
    expect(useAgendaFiltersStore().selectedRooms).toEqual([])
    expect(navigateToMock).toHaveBeenLastCalledWith('/login')
  })

  it('does not leave clinic data for the next account on logout', async () => {
    const { data } = await useAsyncData('clinic-data', () => Promise.resolve('dados da clínica'))

    useAgendaRange().value = { from: '2024-11-01', to: '2024-12-01' }
    usePatientsQuery().value = { search: 'Mariana', page: 2 }
    usePendingRequestsCount().value = 3
    useToast().showToast('Cadastro de Mariana concluído.')
    useActiveModal().openModal('patient', { patient: { id: 'p1' } })

    await useAuth().logout()

    expect(useAgendaRange().value).toBeNull()
    expect(usePatientsQuery().value).toEqual({ search: '', page: 1 })
    expect(usePendingRequestsCount().value).toBe(0)
    expect(useActiveModal().modalPayload.value).toBeNull()
    expect(useToast().message.value).toBeNull()
    expect(useActiveModal().activeModal.value).toBeNull()
    expect(data.value).not.toBe('dados da clínica')
  })

  it('goes back to the login after resetting the password', async () => {
    vi.spyOn(authService, 'resetPassword').mockResolvedValue()

    await useAuth().resetPassword({ token: 'abc', email: 'ana@clinica.com', password: 'novaSenha123', passwordConfirmation: 'novaSenha123' })

    expect(navigateToMock).toHaveBeenCalledWith({ path: '/login', query: { redefinida: '1' } })
  })
})
