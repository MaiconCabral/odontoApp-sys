import type { AuthUser, Plan, Tenant, UserRole } from '~/types/auth'

export type NavIcon = 'overview' | 'agenda' | 'patients' | 'waitlist' | 'settings'

export type NavEntry = {
  id: string
  label: string
  icon: NavIcon
  segment: string
  roles: UserRole[]
  count?: number
}

export type NavItem = Omit<NavEntry, 'segment' | 'roles'> & {
  path: string
}

export type Workspace = {
  clinic: Tenant
  user: AuthUser
  plan: Plan
  nav: NavItem[]
}
