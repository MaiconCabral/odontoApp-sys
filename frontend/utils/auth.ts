import type {
  ForgotPasswordPayload,
  LoginPayload,
  RegisterPayload,
} from '~/types/auth'

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

function isValidEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())
}

function validateEmail(email: string): string | undefined {
  return isValidEmail(email) ? undefined : 'Informe um e-mail válido.'
}

function withoutEmpty<Form>(errors: FormErrors<Form>): FormErrors<Form> {
  return Object.fromEntries(Object.entries(errors).filter(([, value]) => value)) as FormErrors<Form>
}

export function validateLogin(form: LoginPayload): FormErrors<LoginPayload> {
  return withoutEmpty<LoginPayload>({
    email: validateEmail(form.email),
    password: form.password ? undefined : 'Informe sua senha.',
  })
}

export function validateNewPassword(form: NewPasswordForm): FormErrors<NewPasswordForm> {
  const isStrong = form.password.length >= 8 && /\p{L}/u.test(form.password) && /\d/.test(form.password)

  return withoutEmpty<NewPasswordForm>({
    password: isStrong ? undefined : 'Use ao menos 8 caracteres, com letras e números.',
    passwordConfirmation: form.password === form.passwordConfirmation ? undefined : 'As senhas não coincidem.',
  })
}

export function validateRegister(form: RegisterPayload): FormErrors<RegisterPayload> {
  return withoutEmpty<RegisterPayload>({
    name: form.name.trim().length >= 2 ? undefined : 'Informe seu nome completo.',
    email: validateEmail(form.email),
    ...validateNewPassword(form),
    clinicName: form.clinicName.trim().length >= 2 ? undefined : 'Informe o nome da clínica.',
  })
}

export function validateForgotPassword(form: ForgotPasswordPayload): FormErrors<ForgotPasswordPayload> {
  return withoutEmpty<ForgotPasswordPayload>({ email: validateEmail(form.email) })
}

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

// Only panel paths are accepted, so a crafted link cannot send the user to another site.
export function safeRedirect(path: unknown): string | null {
  if (typeof path !== 'string' || !path.startsWith('/app/')) {
    return null
  }

  return path.includes('//') || path.includes('\\') || path.includes('..') ? null : path
}

export function tenantPath(slug: string, segment = ''): string {
  return segment ? `/app/${slug}/${segment}` : `/app/${slug}`
}
