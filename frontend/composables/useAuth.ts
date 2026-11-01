import type {
  AuthSession,
  ForgotPasswordPayload,
  LoginPayload,
  RegisterPayload,
  ResetPasswordPayload,
} from '~/types/auth'

export function useAuth() {
  const auth = useAuthStore()
  const tenant = useTenantStore()
  const agendaFilters = useAgendaFiltersStore()

  function applySession(session: AuthSession | null) {
    auth.setUser(session?.user ?? null)
    tenant.setTenant(session?.tenant ?? null, session?.plan ?? null)
  }

  async function loadSession() {
    if (auth.isLoaded) {
      return
    }

    try {
      applySession(await authService.getSession())
    }
    catch {
      applySession(null)
    }
  }

  async function login(payload: LoginPayload, redirect?: unknown) {
    const session = await authService.login(payload)

    applySession(session)
    await navigateTo(safeRedirect(redirect) ?? tenantPath(session.tenant.slug))
  }

  async function register(payload: RegisterPayload) {
    const session = await authService.register(payload)

    applySession(session)
    await navigateTo(tenantPath(session.tenant.slug))
  }

  function forgotPassword(payload: ForgotPasswordPayload) {
    return authService.forgotPassword(payload)
  }

  async function resetPassword(payload: ResetPasswordPayload) {
    await authService.resetPassword(payload)
    await navigateTo({ path: '/login', query: { redefinida: '1' } })
  }

  async function logout() {
    const sessionPatients = useSessionPatients()
    const sessionEvents = useSessionAgendaEvents()
    const { hideToast } = useToast()
    const { closeModal } = useActiveModal()

    await authService.logout()

    applySession(null)
    agendaFilters.reset()
    sessionPatients.value = []
    sessionEvents.value = []
    hideToast()
    closeModal()
    await navigateTo('/login')

    // Nothing from this clinic can remain for the next account in the same tab.
    clearNuxtData()
  }

  return { loadSession, login, register, forgotPassword, resetPassword, logout }
}
