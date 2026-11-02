import type {
  AuthSession,
  ForgotPasswordPayload,
  LoginPayload,
  RegisterPayload,
  ResetPasswordPayload,
} from '~/types/auth'

// Session actions of the app.
export function useAuth() {
  const auth = useAuthStore()
  const tenant = useTenantStore()
  const agendaFilters = useAgendaFiltersStore()

  // Puts the session in the stores.
  function applySession(session: AuthSession | null) {
    auth.setUser(session?.user ?? null)
    tenant.setTenant(session?.tenant ?? null, session?.plan ?? null)
  }

  // Loads the session once per page load.
  async function loadSession() {
    if (auth.isLoaded) {
      return
    }

    if (!useClinicHost().isClinicHost) {
      applySession(null)

      return
    }

    try {
      applySession(await authService.getSession())
    }
    catch (error) {
      auth.clinicNotFound = apiStatus(error) === 404
      applySession(null)
    }
  }

  // Signs in and opens the right page.
  async function login(payload: LoginPayload, redirect?: unknown) {
    const session = await authService.login(payload)

    applySession(session)
    await navigateTo(safeRedirect(redirect) ?? homePath(session.user.role))
  }

  // Registers the clinic and opens its login.
  // The session belongs to the clinic host, so the new administrator signs in there.
  async function register(payload: RegisterPayload) {
    const { loginUrl } = await authService.register(payload)

    await navigateTo(loginUrl, { external: true })
  }

  // Asks for the reset link.
  function forgotPassword(payload: ForgotPasswordPayload) {
    return authService.forgotPassword(payload)
  }

  // Saves the new password and returns to the login.
  async function resetPassword(payload: ResetPasswordPayload) {
    await authService.resetPassword(payload)
    await navigateTo({ path: '/login', query: { redefinida: '1' } })
  }

  // Signs out and clears every clinic state.
  async function logout() {
    const { hideToast } = useToast()
    const { closeModal } = useActiveModal()
    const agendaRange = useAgendaRange()
    const patientsQuery = usePatientsQuery()
    const pendingRequests = usePendingRequestsCount()

    await authService.logout()

    applySession(null)
    agendaFilters.reset()
    hideToast()
    closeModal()
    await navigateTo('/login')

    // Nothing from this clinic can remain for the next account in the same tab.
    // The states are reset, not removed: a page still on screen may be reading them.
    clearNuxtData()
    agendaRange.value = null
    patientsQuery.value = { search: '', page: 1 }
    pendingRequests.value = 0
  }

  return { loadSession, login, register, forgotPassword, resetPassword, logout }
}
