export type UserRole = 'admin' | 'professional' | 'reception'

export type AuthUser = {
  id: string
  name: string
  email: string
  initials: string
  role: UserRole
  roleLabel: string
}

export type Tenant = {
  slug: string
  name: string
  unit: string
  initials: string
}

export type Plan = {
  name: string
  trialDaysLeft: number
  // Active professionals the plan allows; null means no limit.
  maxProfessionals: number | null
}

export type AuthSession = {
  user: AuthUser
  tenant: Tenant
  plan: Plan
}

export type LoginPayload = {
  email: string
  password: string
}

export type RegisterPayload = {
  name: string
  email: string
  password: string
  passwordConfirmation: string
  clinicName: string
  unit: string
}

export type RegisteredClinic = {
  slug: string
  loginUrl: string
}

export type ForgotPasswordPayload = {
  email: string
}

export type ResetPasswordPayload = {
  token: string
  email: string
  password: string
  passwordConfirmation: string
}
