export type NavIcon = 'overview' | 'agenda' | 'patients' | 'settings'

export type NavItem = {
  id: string
  label: string
  icon: NavIcon
  path: string
  count?: number
}

export type Workspace = {
  clinic: {
    initials: string
    name: string
    unit: string
  }
  user: {
    initials: string
    name: string
    role: string
  }
  plan: {
    name: string
    trialDaysLeft: number
  }
  nav: NavItem[]
}
