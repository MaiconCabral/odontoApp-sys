export type SettingsTabId = 'clinic' | 'team' | 'hours' | 'subscription'

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

export type ServicePreference = {
  id: string
  title: string
  description: string
  enabled: boolean
}

export type Subscription = {
  status: string
  price: string
  period: string
  features: string[]
}

export type Settings = {
  tabs: SettingsTab[]
  clinic: ClinicData
  preferences: ServicePreference[]
  subscription: Subscription
}
