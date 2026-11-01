import type { AuthUser, Plan, Tenant } from '~/types/auth'

export type NavIcon = 'overview' | 'agenda' | 'patients' | 'settings'

export type NavEntry = {
  id: string
  label: string
  icon: NavIcon
  segment: string
  count?: number
}

export type NavItem = Omit<NavEntry, 'segment'> & {
  path: string
}

export type Workspace = {
  clinic: Tenant
  user: AuthUser
  plan: Plan
  nav: NavItem[]
}
