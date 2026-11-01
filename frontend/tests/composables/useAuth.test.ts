import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { demoSession } from '~/mocks/auth'
import type { AgendaEvent } from '~/types/agenda'
import type { Patient } from '~/types/patients'
import { clearSession, demoTenantPath } from '../utils/session'

const { navigateToMock } = vi.hoisted(() => ({
  navigateToMock: vi.fn(),
}))

mockNuxtImport('navigateTo', () => navigateToMock)

const newClinic = {
  name: 'Ana Souza',
  email: 'ana.useauth@clinica.com',
  password: 'senhaSegura1',
  passwordConfirmation: 'senhaSegura1',
  clinicName: 'Clínica São José',
  unit: '',
}

describe('useAuth', () => {
  beforeEach(() => {
    navigateToMock.mockClear()
    sessionStorage.clear()
    clearSession()
  })

  it('signs in and opens the panel of the clinic', async () => {
    await useAuth().login({ email: ' DEMO@sorriso.app ', password: 'Sorriso123' })

    expect(useAuthStore().isAuthenticated).toBe(true)
    expect(useTenantStore().slug).toBe(demoSession.tenant.slug)
    expect(navigateToMock).toHaveBeenCalledWith(demoTenantPath)
  })

  it('returns to the requested panel page after signing in', async () => {
    await useAuth().login({ email: 'demo@sorriso.app', password: 'Sorriso123' }, `${demoTenantPath}/agenda`)

    expect(navigateToMock).toHaveBeenCalledWith(`${demoTenantPath}/agenda`)
  })

  it('ignores a redirect to another site', async () => {
    await useAuth().login({ email: 'demo@sorriso.app', password: 'Sorriso123' }, '//site.com')

    expect(navigateToMock).toHaveBeenCalledWith(demoTenantPath)
  })

  it('rejects wrong credentials without creating a session', async () => {
    await expect(useAuth().login({ email: 'demo@sorriso.app', password: 'errada' })).rejects.toMatchObject({
      data: { message: 'E-mail ou senha incorretos.' },
    })

    expect(useAuthStore().isAuthenticated).toBe(false)
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('registers a clinic, generates its slug and rejects a duplicated email', async () => {
    await useAuth().register(newClinic)

    expect(useTenantStore().tenant).toMatchObject({ slug: 'clinica-sao-jose', unit: 'Unidade principal', initials: 'CJ' })
    expect(navigateToMock).toHaveBeenCalledWith('/app/clinica-sao-jose')

    await expect(useAuth().register(newClinic)).rejects.toMatchObject({
      data: { errors: { email: ['Este e-mail já está em uso.'] } },
    })
  })

  it('restores the stored session only once', async () => {
    await useAuth().login({ email: 'demo@sorriso.app', password: 'Sorriso123' })
    clearSession()
    useAuthStore().isLoaded = false

    await useAuth().loadSession()
    expect(useAuthStore().user?.email).toBe('demo@sorriso.app')

    sessionStorage.clear()
    await useAuth().loadSession()
    expect(useAuthStore().isAuthenticated).toBe(true)
  })

  it('treats a failure while loading the session as signed out', async () => {
    sessionStorage.setItem('odonto-mock-session', '{broken')
    useAuthStore().isLoaded = false

    await useAuth().loadSession()

    expect(useAuthStore().isAuthenticated).toBe(false)
    expect(useAuthStore().isLoaded).toBe(true)
  })

  it('clears the session and the agenda filters on logout', async () => {
    await useAuth().login({ email: 'demo@sorriso.app', password: 'Sorriso123' })
    useAgendaFiltersStore().selectedRooms = ['sala-1']

    await useAuth().logout()

    expect(useAuthStore().isAuthenticated).toBe(false)
    expect(useTenantStore().tenant).toBeNull()
    expect(useAgendaFiltersStore().selectedRooms).toEqual([])
    expect(sessionStorage.getItem('odonto-mock-session')).toBeNull()
    expect(navigateToMock).toHaveBeenLastCalledWith('/login')
  })

  it('does not leave clinic data for the next account on logout', async () => {
    const { data } = await useAsyncData('clinic-data', () => Promise.resolve('dados da clínica'))

    useSessionPatients().value = [{ id: 'p1' } as Patient]
    useSessionAgendaEvents().value = [{ id: 'e1' } as AgendaEvent]
    useToast().showToast('Cadastro de Mariana concluído.')
    useActiveModal().openModal('patient')

    await useAuth().logout()

    expect(useSessionPatients().value).toEqual([])
    expect(useSessionAgendaEvents().value).toEqual([])
    expect(useToast().message.value).toBeNull()
    expect(useActiveModal().activeModal.value).toBeNull()
    expect(data.value).not.toBe('dados da clínica')
  })

  it('resets the password only with a valid link', async () => {
    const payload = { email: 'ana.useauth@clinica.com', password: 'novaSenha123', passwordConfirmation: 'novaSenha123' }

    await expect(useAuth().resetPassword({ ...payload, token: 'expirado' })).rejects.toMatchObject({
      data: { errors: { token: expect.any(Array) } },
    })

    await useAuth().resetPassword({ ...payload, token: 'mock-reset-token' })

    expect(navigateToMock).toHaveBeenCalledWith({ path: '/login', query: { redefinida: '1' } })
  })
})
