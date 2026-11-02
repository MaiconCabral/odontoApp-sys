import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { RouteLocationNormalized } from 'vue-router'
import auth from '~/middleware/auth'
import centralHost from '~/middleware/central-host'
import clinicHost from '~/middleware/clinic-host'
import guest from '~/middleware/guest'
import role from '~/middleware/role'
import { professionalSession, receptionSession } from '../fixtures/session'
import { clearSession, seedSession } from '../utils/session'

const { navigateToMock, hostMock } = vi.hoisted(() => ({
  navigateToMock: vi.fn((target: unknown) => target),
  hostMock: { isClinicHost: true, centralUrl: (path: string) => `http://localhost:3000${path}` },
}))

mockNuxtImport('navigateTo', () => navigateToMock)
mockNuxtImport('useClinicHost', () => () => hostMock)

function routeTo(partial: { fullPath?: string, meta?: Record<string, unknown> }) {
  return partial as unknown as RouteLocationNormalized
}

const from = routeTo({})

describe('route middleware', () => {
  beforeEach(() => {
    navigateToMock.mockClear()
    hostMock.isClinicHost = true
  })

  it('auth sends visitors to the login and keeps the destination', async () => {
    clearSession()

    const result = await auth(routeTo({ fullPath: '/app/agenda' }), from)

    expect(result).toEqual({ path: '/login', query: { redirect: '/app/agenda' } })
  })

  it('auth lets signed-in users through', async () => {
    expect(await auth(routeTo({ fullPath: '/app' }), from)).toBeUndefined()
  })

  it('guest sends signed-in users to their home', async () => {
    expect(await guest(routeTo({}), from)).toBe('/app')

    seedSession(professionalSession)

    expect(await guest(routeTo({}), from)).toBe('/app/agenda')
  })

  it('guest lets visitors through', async () => {
    clearSession()

    expect(await guest(routeTo({}), from)).toBeUndefined()
  })

  it('role keeps a professional out of the pages of other roles', () => {
    seedSession(professionalSession)

    expect(role(routeTo({ meta: { roles: ['admin'] } }), from)).toBe('/app/agenda')
  })

  it('role sends the reception to its home', () => {
    seedSession(receptionSession)

    expect(role(routeTo({ meta: { roles: ['admin'] } }), from)).toBe('/app')
  })

  it('role lets allowed roles and open pages through', () => {
    expect(role(routeTo({ meta: { roles: ['admin'] } }), from)).toBeUndefined()

    seedSession(professionalSession)

    expect(role(routeTo({ meta: {} }), from)).toBeUndefined()
  })

  it('clinic-host sends the central domain to the login', () => {
    hostMock.isClinicHost = false

    expect(clinicHost(routeTo({}), from)).toBe('/login')
  })

  it('clinic-host accepts a clinic host', () => {
    expect(clinicHost(routeTo({}), from)).toBeUndefined()
  })

  it('central-host sends a clinic host to the central domain', () => {
    centralHost(routeTo({ fullPath: '/cadastro' }), from)

    expect(navigateToMock).toHaveBeenCalledWith('http://localhost:3000/cadastro', { external: true })
  })

  it('central-host accepts the central domain', () => {
    hostMock.isClinicHost = false

    expect(centralHost(routeTo({ fullPath: '/cadastro' }), from)).toBeUndefined()
  })
})
