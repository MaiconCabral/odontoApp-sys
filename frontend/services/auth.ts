import type {
  AuthSession,
  ForgotPasswordPayload,
  LoginPayload,
  RegisteredClinic,
  RegisterPayload,
  ResetPasswordPayload,
} from '~/types/auth'

export const authService = {
  // Current session, or null when signed out.
  async getSession(): Promise<AuthSession | null> {
    try {
      return await useApi().api<AuthSession>('/api/session')
    }
    catch (error) {
      if (apiStatus(error) === 401 || apiStatus(error) === 403) {
        return null
      }

      throw error
    }
  },

  // Signs in.
  login: (payload: LoginPayload) =>
    useApi().api<AuthSession>('/api/login', { method: 'POST', body: payload }),

  // Registers a clinic on the central domain.
  register: (payload: RegisterPayload) =>
    useApi().api<RegisteredClinic>('/api/clinics', {
      method: 'POST',
      body: {
        name: payload.name,
        email: payload.email,
        password: payload.password,
        password_confirmation: payload.passwordConfirmation,
        clinic_name: payload.clinicName,
        unit: payload.unit,
      },
    }),

  // Asks for the reset link.
  forgotPassword: (payload: ForgotPasswordPayload) =>
    useApi().api<void>('/api/forgot-password', { method: 'POST', body: payload }),

  // Saves a new password.
  resetPassword: (payload: ResetPasswordPayload) =>
    useApi().api<void>('/api/reset-password', {
      method: 'POST',
      body: {
        token: payload.token,
        email: payload.email,
        password: payload.password,
        password_confirmation: payload.passwordConfirmation,
      },
    }),

  // Ends the session.
  logout: () => useApi().api<void>('/api/logout', { method: 'POST' }),
}
