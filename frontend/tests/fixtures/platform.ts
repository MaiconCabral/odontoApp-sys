import type { PlatformClinic, PlatformPlan } from '~/types/platform'

export const platformClinicFixture: PlatformClinic = {
  id: 'c1',
  name: 'Sorriso Perfeito',
  slug: 'sorriso-perfeito',
  email: 'contato@sorrisoperfeito.com',
  plan: { id: '2', code: 'pro', name: 'Pro' },
  status: 'trial',
  trialEndsAt: '2024-11-07',
  trialDaysLeft: 14,
  suspendedAt: null,
  createdAt: '2024-10-24T10:00:00-03:00',
  usage: { activeUsers: 4, professionals: 3, appointmentsLast30Days: 87 },
}

export const platformPlansFixture: PlatformPlan[] = [
  { id: '1', code: 'free', name: 'Free', priceCents: 0, maxProfessionals: 1, clinics: 0 },
  { id: '2', code: 'pro', name: 'Pro', priceCents: 24900, maxProfessionals: 5, clinics: 1 },
  { id: '3', code: 'enterprise', name: 'Enterprise', priceCents: 59900, maxProfessionals: null, clinics: 0 },
]
