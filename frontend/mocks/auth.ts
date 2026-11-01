import type {
  AuthSession,
  ForgotPasswordPayload,
  LoginPayload,
  RegisterPayload,
  ResetPasswordPayload,
} from '~/types/auth'

// Development stand-in for the Laravel API. It offers no real protection and must not reach production.
const SESSION_KEY = 'odonto-mock-session'
const RESET_TOKEN = 'mock-reset-token'
const LATENCY_MS = 300

type MockAccount = {
  password: string
  session: AuthSession
}

export const demoSession: AuthSession = {
  user: {
    name: 'Dr. Carlos Mendes',
    email: 'demo@sorriso.app',
    initials: 'CM',
    role: 'Administrador',
  },
  tenant: {
    slug: 'sorriso-perfeito',
    name: 'Sorriso Perfeito',
    unit: 'Unidade Jardins',
    initials: 'SP',
  },
  plan: {
    name: 'Pro',
    trialDaysLeft: 14,
  },
}

const accounts: MockAccount[] = [{ password: 'Sorriso123', session: demoSession }]

function wait() {
  return new Promise(resolve => setTimeout(resolve, LATENCY_MS))
}

function normalizeEmail(email: string) {
  return email.trim().toLowerCase()
}

function findAccount(email: string) {
  return accounts.find(account => account.session.user.email === normalizeEmail(email))
}

function storeSession(session: AuthSession | null) {
  if (!import.meta.client) {
    return
  }

  if (session) {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(session))
  }
  else {
    sessionStorage.removeItem(SESSION_KEY)
  }
}

function validationError(message: string, errors: Record<string, string[]> = {}) {
  return createError({ statusCode: 422, data: { message, errors } })
}

function uniqueSlug(name: string) {
  const base = name
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '') || 'clinica'

  const isTaken = (slug: string) => accounts.some(account => account.session.tenant.slug === slug)
  let slug = base

  for (let suffix = 2; isTaken(slug); suffix++) {
    slug = `${base}-${suffix}`
  }

  return slug
}

export async function mockGetSession(): Promise<AuthSession | null> {
  if (!import.meta.client) {
    return null
  }

  const stored = sessionStorage.getItem(SESSION_KEY)

  return stored ? JSON.parse(stored) as AuthSession : null
}

export async function mockLogin(payload: LoginPayload): Promise<AuthSession> {
  await wait()

  const account = findAccount(payload.email)

  if (account?.password !== payload.password) {
    throw validationError('E-mail ou senha incorretos.')
  }

  storeSession(account.session)

  return account.session
}

export async function mockRegister(payload: RegisterPayload): Promise<AuthSession> {
  await wait()

  if (findAccount(payload.email)) {
    throw validationError('Verifique os dados informados.', { email: ['Este e-mail já está em uso.'] })
  }

  const clinicName = payload.clinicName.trim()
  const name = payload.name.trim()
  const session: AuthSession = {
    user: {
      name,
      email: normalizeEmail(payload.email),
      initials: patientInitials(name),
      role: 'Administrador',
    },
    tenant: {
      slug: uniqueSlug(clinicName),
      name: clinicName,
      unit: payload.unit.trim() || 'Unidade principal',
      initials: patientInitials(clinicName),
    },
    plan: {
      name: 'Pro',
      trialDaysLeft: 14,
    },
  }

  accounts.push({ password: payload.password, session })
  storeSession(session)

  return session
}

export async function mockForgotPassword(payload: ForgotPasswordPayload): Promise<void> {
  await wait()

  const account = findAccount(payload.email)

  if (account) {
    const query = new URLSearchParams({ token: RESET_TOKEN, email: account.session.user.email })

    console.info(`[mock] Link de redefinição de senha: /redefinir-senha?${query}`)
  }
}

export async function mockResetPassword(payload: ResetPasswordPayload): Promise<void> {
  await wait()

  const account = findAccount(payload.email)

  if (!account || payload.token !== RESET_TOKEN) {
    throw validationError('Este link é inválido ou expirou.', { token: ['Este link é inválido ou expirou.'] })
  }

  account.password = payload.password
}

export async function mockLogout(): Promise<void> {
  storeSession(null)
}
