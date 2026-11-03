import type { StaffNotification } from '~/types/notifications'
import type { ClinicData, ClinicPreferences } from '~/types/settings'
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

export const preferencesFixture: ClinicPreferences = {
  autoConfirmation: false,
  waitingList: true,
  reminder24h: true,
}

// Answer of /api/clinic/subscription.
export const subscriptionFixture = {
  status: 'trial',
  trialDaysLeft: 14,
  plan: { code: 'pro', name: 'Pro', priceCents: 24900, maxProfessionals: 5 },
  plans: [
    { code: 'free', name: 'Free', priceCents: 0, maxProfessionals: 1 },
    { code: 'pro', name: 'Pro', priceCents: 24900, maxProfessionals: 5 },
    { code: 'enterprise', name: 'Enterprise', priceCents: 59900, maxProfessionals: null },
  ],
}

// Answer of /api/notifications.
export const notificationsFixture: { data: StaffNotification[], meta: { unreadCount: number } } = {
  data: [
    {
      id: 'n1',
      type: 'booking_requested',
      title: 'Novo pedido de agendamento',
      body: 'Paciente Novo pediu um horário para terça-feira, 10/11 às 09:00.',
      appointmentId: '42',
      date: '2026-11-10',
      read: false,
      createdAt: '2026-11-09T09:55:00-03:00',
    },
    {
      id: 'n2',
      type: 'confirmed_by_patient',
      title: 'Paciente confirmou a consulta',
      body: 'Camila Ferreira confirmou a consulta de terça-feira, 10/11 às 14:00.',
      appointmentId: '43',
      date: '2026-11-10',
      read: true,
      createdAt: '2026-11-08T10:00:00-03:00',
    },
  ],
  meta: { unreadCount: 1 },
}
