import { demoSession } from '~/mocks/auth'

export const demoTenantPath = `/app/${demoSession.tenant.slug}`

export function seedSession() {
  useAuthStore().setUser(demoSession.user)
  useTenantStore().setTenant(demoSession.tenant, demoSession.plan)
}

export function clearSession() {
  useAuthStore().setUser(null)
  useTenantStore().setTenant(null, null)
}
