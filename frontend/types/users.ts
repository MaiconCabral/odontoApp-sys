import type { UserRole } from '~/types/auth'

export type TeamUser = {
  id: string
  name: string
  email: string
  initials: string
  role: UserRole
  roleLabel: string
  specialty: string | null
  attendsPatients: boolean
  active: boolean
}

export type TeamUserForm = {
  name: string
  email: string
  role: UserRole
  specialty: string
  attendsPatients: boolean
  active: boolean
}
