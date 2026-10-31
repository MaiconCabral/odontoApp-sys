export type SettingsTabId = 'clinic' | 'professionals' | 'hours' | 'subscription'

export type SettingsTab = {
  id: SettingsTabId
  label: string
  badge?: string
}

export type ClinicData = {
  name: string
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

export type SettingsProfessional = {
  id: string
  name: string
  specialty: string
}

export type OpeningHours = {
  id: string
  days: string
  hours: string
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
  professionals: SettingsProfessional[]
  openingHours: OpeningHours[]
  subscription: Subscription
}
