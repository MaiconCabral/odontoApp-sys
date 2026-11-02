import type { AuthSession } from '~/types/auth'
import { adminSession } from '../fixtures/session'

// Signs in a test session.
export function seedSession(session: AuthSession = adminSession) {
  useAuthStore().setUser(session.user)
  useTenantStore().setTenant(session.tenant, session.plan)
}

// Signs out the test session.
export function clearSession() {
  useAuthStore().setUser(null)
  useAuthStore().clinicNotFound = false
  useTenantStore().setTenant(null, null)
}
