export type PlatformAdmin = {
  id: string
  name: string
  email: string
}

export type PlatformClinicStatus = 'trial' | 'active' | 'expired' | 'suspended'

export type PlatformClinic = {
  id: string
  name: string
  slug: string
  email: string | null
  plan: { id: string, code: string | null, name: string | null }
  status: PlatformClinicStatus
  trialEndsAt: string | null
  trialDaysLeft: number
  suspendedAt: string | null
  createdAt: string | null
  usage: { activeUsers: number, professionals: number, appointmentsLast30Days: number } | null
}

export type PlatformClinicPage = {
  clinics: PlatformClinic[]
  total: number
  page: number
  lastPage: number
}

export type PlatformClinicQuery = {
  search: string
  planId: string
  status: PlatformClinicStatus | ''
  page: number
}

export type PlatformAccountForm = {
  planId: string
  trialEndsAt: string
  suspended: boolean
}

export type PlatformPlan = {
  id: string
  code: string
  name: string
  priceCents: number
  maxProfessionals: number | null
  clinics: number
}

export type PlatformPlanForm = {
  code: string
  name: string
  // Typed as "249,00".
  price: string
  // Empty means no limit.
  maxProfessionals: string
}
