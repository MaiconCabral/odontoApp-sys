import {
  mockForgotPassword,
  mockGetSession,
  mockLogin,
  mockLogout,
  mockRegister,
  mockResetPassword,
} from '~/mocks/auth'

// Backed by a mock until the auth API exists. Failures follow the Laravel validation format.
export const authService = {
  getSession: mockGetSession,
  login: mockLogin,
  register: mockRegister,
  forgotPassword: mockForgotPassword,
  resetPassword: mockResetPassword,
  logout: mockLogout,
}
