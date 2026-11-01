import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { RouteLocationNormalized } from 'vue-router'
import auth from '~/middleware/auth'
import guest from '~/middleware/guest'
import tenant from '~/middleware/tenant'
import { clearSession, demoTenantPath } from '../utils/session'

const { navigateToMock } = vi.hoisted(() => ({
  navigateToMock: vi.fn((target: unknown) => target),
}))

mockNuxtImport('navigateTo', () => navigateToMock)

function routeTo(partial: { fullPath?: string, params?: Record<string, string> }) {
  return partial as unknown as RouteLocationNormalized
}

const from = routeTo({})

describe('route middleware', () => {
  beforeEach(() => {
    navigateToMock.mockClear()
  })

  it('auth sends visitors to the login and keeps the destination', async () => {
    clearSession()

    const result = await auth(routeTo({ fullPath: `${demoTenantPath}/agenda` }), from)

    expect(result).toEqual({ path: '/login', query: { redirect: `${demoTenantPath}/agenda` } })
  })

  it('auth lets signed-in users through', async () => {
    expect(await auth(routeTo({ fullPath: demoTenantPath }), from)).toBeUndefined()
  })

  it('guest sends signed-in users to their panel', async () => {
    expect(await guest(routeTo({}), from)).toBe(demoTenantPath)
  })

  it('guest lets visitors through', async () => {
    clearSession()

    expect(await guest(routeTo({}), from)).toBeUndefined()
  })

  it('tenant redirects a different clinic to the one in the session', () => {
    expect(tenant(routeTo({ params: { tenant: 'outra-clinica' } }), from)).toBe(demoTenantPath)
  })

  it('tenant accepts the clinic in the session', () => {
    expect(tenant(routeTo({ params: { tenant: 'sorriso-perfeito' } }), from)).toBeUndefined()
  })
})
