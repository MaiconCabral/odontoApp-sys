import { planExtras, preferenceTexts, settingsTabs } from '~/mocks/settings'
import type { ClinicPreferences, Plan, PlanCode, ServicePreference, Settings, Subscription, SubscriptionStatus } from '~/types/settings'

type PlanResponse = { code: PlanCode, name: string, priceCents: number, maxProfessionals: number | null }

type SubscriptionResponse = {
  status: SubscriptionStatus
  trialDaysLeft: number
  plan: PlanResponse
  plans: PlanResponse[]
}

// Status badge of the subscription card.
const statusLabels: Record<SubscriptionStatus, string> = {
  trial: 'Trial ativo',
  active: 'Ativa',
  expired: 'Trial expirado',
}

// Limit of professionals as shown to the clinic.
function professionalsLabel(max: number | null): string {
  if (max === null) {
    return 'Profissionais ilimitados'
  }

  return max === 1 ? '1 profissional' : `Até ${max} profissionais`
}

// Plan ready to show: formatted price and the professionals limit second in the list.
function toPlan(plan: PlanResponse): Plan {
  const [first, ...rest] = planExtras[plan.code] ?? []

  return {
    code: plan.code,
    name: plan.name,
    price: formatPlanPrice(plan.priceCents),
    features: [first, professionalsLabel(plan.maxProfessionals), ...rest].filter((feature): feature is string => Boolean(feature)),
  }
}

// Subscription of the API with the status label and the plans ready to show.
function toSubscription(response: SubscriptionResponse): Subscription {
  return {
    status: response.status,
    statusLabel: statusLabels[response.status],
    trialDaysLeft: response.trialDaysLeft,
    plan: toPlan(response.plan),
    plans: response.plans.map(toPlan),
    period: '/mês',
  }
}

// Toggles in the order shown, with their texts.
export function toPreferenceList(preferences: ClinicPreferences): ServicePreference[] {
  return (Object.keys(preferenceTexts) as (keyof ClinicPreferences)[]).map(id => ({
    id,
    ...preferenceTexts[id],
    enabled: preferences[id],
  }))
}

// Preferences in the API field names.
function toPreferencesBody(preferences: ClinicPreferences) {
  return {
    auto_confirmation: preferences.autoConfirmation,
    waiting_list: preferences.waitingList,
    reminder_24h: preferences.reminder24h,
  }
}

export const settingsService = {
  // Clinic data, preferences and subscription, loaded together.
  get: async (): Promise<Settings> => {
    const [clinic, preferences, subscription] = await Promise.all([
      clinicService.get(),
      settingsService.getPreferences(),
      settingsService.getSubscription(),
    ])

    return {
      tabs: settingsTabs.map(tab => (tab.id === 'subscription' ? { ...tab, badge: subscription.plan.name } : tab)),
      clinic,
      preferences: toPreferenceList(preferences),
      subscription,
    }
  },

  // Service preferences of the clinic.
  getPreferences: () => useApi().api<ClinicPreferences>('/api/clinic/preferences'),

  // Saves the service preferences.
  updatePreferences: (preferences: ClinicPreferences) =>
    useApi().api<ClinicPreferences>('/api/clinic/preferences', { method: 'PUT', body: toPreferencesBody(preferences) }),

  // Current plan and the plans the clinic may move to.
  getSubscription: async () => toSubscription(await useApi().api<SubscriptionResponse>('/api/clinic/subscription')),
}
