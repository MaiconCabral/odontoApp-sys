import type {
  ForgotPasswordPayload,
  LoginPayload,
  RegisterPayload,
  UserRole,
} from '~/types/auth'
import type { ClinicData } from '~/types/settings'
import type { TeamUserForm } from '~/types/users'

export type FormErrors<Form> = Partial<Record<keyof Form, string>>

export type NewPasswordForm = {
  password: string
  passwordConfirmation: string
}

export type ParsedApiError = {
  message: string
  fields: Record<string, string>
}

const GENERIC_ERROR = 'Não foi possível concluir agora. Tente novamente em instantes.'

// Tells whether the e-mail has a valid format.
function isValidEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())
}

// Error message for an invalid e-mail.
function validateEmail(email: string): string | undefined {
  return isValidEmail(email) ? undefined : 'Informe um e-mail válido.'
}

// Drops the fields without error.
function withoutEmpty<Form>(errors: FormErrors<Form>): FormErrors<Form> {
  return Object.fromEntries(Object.entries(errors).filter(([, value]) => value)) as FormErrors<Form>
}

// Validates the login form.
export function validateLogin(form: LoginPayload): FormErrors<LoginPayload> {
  return withoutEmpty<LoginPayload>({
    email: validateEmail(form.email),
    password: form.password ? undefined : 'Informe sua senha.',
  })
}

// Validates a new password and its confirmation.
export function validateNewPassword(form: NewPasswordForm): FormErrors<NewPasswordForm> {
  const isStrong = form.password.length >= 8 && /\p{L}/u.test(form.password) && /\d/.test(form.password)

  return withoutEmpty<NewPasswordForm>({
    password: isStrong ? undefined : 'Use ao menos 8 caracteres, com letras e números.',
    passwordConfirmation: form.password === form.passwordConfirmation ? undefined : 'As senhas não coincidem.',
  })
}

// Validates the clinic registration.
export function validateRegister(form: RegisterPayload): FormErrors<RegisterPayload> {
  return withoutEmpty<RegisterPayload>({
    name: form.name.trim().length >= 2 ? undefined : 'Informe seu nome completo.',
    email: validateEmail(form.email),
    ...validateNewPassword(form),
    clinicName: form.clinicName.trim().length >= 2 ? undefined : 'Informe o nome da clínica.',
  })
}

// Validates the forgot password form.
export function validateForgotPassword(form: ForgotPasswordPayload): FormErrors<ForgotPasswordPayload> {
  return withoutEmpty<ForgotPasswordPayload>({ email: validateEmail(form.email) })
}

// General message and field errors of an API error.
// Reads the Laravel error format: { message, errors: { field: [messages] } }.
export function parseApiError(error: unknown): ParsedApiError {
  const data = (error as { data?: { message?: unknown, errors?: Record<string, string[]> } } | null)?.data
  const fields: Record<string, string> = {}

  for (const [field, messages] of Object.entries(data?.errors ?? {})) {
    if (messages[0]) {
      fields[field] = messages[0]
    }
  }

  return {
    message: typeof data?.message === 'string' ? data.message : GENERIC_ERROR,
    fields,
  }
}

// Accepts only safe panel paths.
// Only panel paths are accepted, so a crafted link cannot send the user to another site.
export function safeRedirect(path: unknown): string | null {
  if (typeof path !== 'string' || !path.startsWith('/app/')) {
    return null
  }

  return path.includes('//') || path.includes('\\') || path.includes('..') ? null : path
}

// Path of a panel page.
export function panelPath(segment = ''): string {
  return segment ? `/app/${segment}` : '/app'
}

// First page of each role.
export function homePath(role: UserRole): string {
  return role === 'professional' ? panelPath('agenda') : panelPath()
}

// HTTP status of an API error.
export function apiStatus(error: unknown): number | undefined {
  return (error as { statusCode?: number } | null)?.statusCode
}

// Renames API error fields to camelCase.
// The API speaks snake_case and the forms camelCase, so field errors must find their field.
export function camelizeApiErrors<ApiError>(error: ApiError): ApiError {
  const data = (error as { data?: { errors?: Record<string, string[]> } } | null)?.data

  if (data?.errors) {
    data.errors = Object.fromEntries(
      Object.entries(data.errors).map(([field, messages]) => [
        field.replace(/_([a-z])/g, (_, letter: string) => letter.toUpperCase()),
        messages,
      ]),
    )
  }

  return error
}

// Validates the clinic address.
export function validateClinicSlug(form: { slug: string }): FormErrors<{ slug: string }> {
  return withoutEmpty<{ slug: string }>({
    slug: /^[a-z0-9]+(-[a-z0-9]+)*$/.test(form.slug.trim().toLowerCase()) ? undefined : 'Informe o endereço da clínica.',
  })
}

// Validates the clinic data.
export function validateClinic(form: ClinicData): FormErrors<ClinicData> {
  return withoutEmpty<ClinicData>({
    name: form.name.trim().length >= 2 ? undefined : 'Informe o nome da clínica.',
    unit: form.unit.trim() ? undefined : 'Informe a unidade.',
    email: !form.email.trim() || isValidEmail(form.email) ? undefined : 'Informe um e-mail válido.',
  })
}

// Validates the team member form.
export function validateTeamUser(form: TeamUserForm): FormErrors<TeamUserForm> {
  return withoutEmpty<TeamUserForm>({
    name: form.name.trim().length >= 2 ? undefined : 'Informe o nome completo.',
    email: validateEmail(form.email),
  })
}
