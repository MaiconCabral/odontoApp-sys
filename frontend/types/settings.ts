export type SettingsTabId = 'clinic' | 'team' | 'procedures' | 'rooms' | 'hours' | 'subscription'

export type SettingsTab = {
  id: SettingsTabId
  label: string
  badge?: string
}

export type ClinicData = {
  name: string
  unit: string
  cnpj: string
  phone: string
  email: string
  address: string
}

export type ClinicPreferences = {
  autoConfirmation: boolean
  waitingList: boolean
  reminder24h: boolean
}

export type ServicePreference = {
  id: keyof ClinicPreferences
  title: string
  description: string
  enabled: boolean
}

export type SubscriptionStatus = 'trial' | 'active' | 'expired'

export type PlanCode = 'free' | 'pro' | 'enterprise'

export type Plan = {
  code: PlanCode
  name: string
  price: string
  features: string[]
}

export type Subscription = {
  status: SubscriptionStatus
  statusLabel: string
  trialDaysLeft: number
  plan: Plan
  plans: Plan[]
  period: string
}

export type Settings = {
  tabs: SettingsTab[]
  clinic: ClinicData
  preferences: ServicePreference[]
  subscription: Subscription
}
