import type { NavEntry } from '~/types/workspace'

export const navEntriesMock: NavEntry[] = [
  { id: 'overview', label: 'Visão geral', icon: 'overview', segment: '', roles: ['admin', 'reception'] },
  { id: 'agenda', label: 'Agenda', icon: 'agenda', segment: 'agenda', roles: ['admin', 'reception', 'professional'] },
  { id: 'patients', label: 'Pacientes', icon: 'patients', segment: 'pacientes', roles: ['admin', 'reception'] },
  { id: 'settings', label: 'Configurações', icon: 'settings', segment: 'configuracoes', roles: ['admin'] },
]
