import type { ClinicData } from '~/types/settings'
import type { TeamUser } from '~/types/users'

export const clinicFixture: ClinicData = {
  name: 'Sorriso Perfeito Odontologia',
  unit: 'Unidade Jardins',
  cnpj: '12.345.678/0001-90',
  phone: '(11) 3051-8090',
  email: 'contato@sorrisoperfeito.com',
  address: 'Rua Oscar Freire, 1240 · Jardins, São Paulo - SP',
}

export const teamFixture: TeamUser[] = [
  { id: '1', name: 'Dr. Carlos Mendes', email: 'demo@sorriso.app', initials: 'CM', role: 'admin', roleLabel: 'Administrador', specialty: 'Ortodontia', attendsPatients: true, active: true },
  { id: '2', name: 'Dra. Ana Beatriz', email: 'ana@sorriso.app', initials: 'AB', role: 'professional', roleLabel: 'Profissional', specialty: 'Odontologia estética', attendsPatients: true, active: true },
  { id: '3', name: 'Dr. Felipe Rocha', email: 'felipe@sorriso.app', initials: 'FR', role: 'professional', roleLabel: 'Profissional', specialty: 'Cirurgia', attendsPatients: true, active: false },
]
