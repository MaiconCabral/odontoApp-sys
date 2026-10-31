import type { Workspace } from '~/types/workspace'

export const workspaceMock: Workspace = {
  clinic: {
    initials: 'SP',
    name: 'Sorriso Perfeito',
    unit: 'Unidade Jardins',
  },
  user: {
    initials: 'CM',
    name: 'Dr. Carlos Mendes',
    role: 'Administrador',
  },
  plan: {
    name: 'Pro',
    trialDaysLeft: 14,
  },
  nav: [
    { id: 'overview', label: 'Visão geral', icon: 'overview', path: '/' },
    { id: 'agenda', label: 'Agenda', icon: 'agenda', path: '/agenda', count: 5 },
    { id: 'patients', label: 'Pacientes', icon: 'patients', path: '/pacientes' },
    { id: 'settings', label: 'Configurações', icon: 'settings', path: '/configuracoes' },
  ],
}
