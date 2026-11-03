import type { AuthSession } from '~/types/auth'

export const adminSession: AuthSession = {
  user: {
    id: '1',
    name: 'Dr. Carlos Mendes',
    email: 'demo@sorriso.app',
    initials: 'CM',
    role: 'admin',
    roleLabel: 'Administrador',
  },
  tenant: {
    slug: 'sorriso-perfeito',
    name: 'Sorriso Perfeito',
    unit: 'Unidade Jardins',
    initials: 'SP',
  },
  plan: {
    name: 'Pro',
    trialDaysLeft: 14,
    maxProfessionals: 5,
  },
}

export const professionalSession: AuthSession = {
  ...adminSession,
  user: { ...adminSession.user, id: '2', name: 'Dra. Ana Beatriz', initials: 'AB', role: 'professional', roleLabel: 'Profissional' },
}

export const receptionSession: AuthSession = {
  ...adminSession,
  user: { ...adminSession.user, id: '3', name: 'Paula Nunes', initials: 'PN', role: 'reception', roleLabel: 'Recepção' },
}
