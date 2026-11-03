import type { NavEntry } from '~/types/workspace'

export const navEntriesMock: NavEntry[] = [
  { id: 'overview', label: 'Visão geral', icon: 'overview', segment: '', roles: ['admin', 'reception'] },
  { id: 'agenda', label: 'Agenda', icon: 'agenda', segment: 'agenda', roles: ['admin', 'reception', 'professional'] },
  { id: 'patients', label: 'Pacientes', icon: 'patients', segment: 'pacientes', roles: ['admin', 'reception'] },
  { id: 'waitlist', label: 'Lista de espera', icon: 'waitlist', segment: 'lista-espera', roles: ['admin', 'reception'] },
  { id: 'reports', label: 'Relatórios', icon: 'reports', segment: 'relatorios', roles: ['admin'] },
  { id: 'settings', label: 'Configurações', icon: 'settings', segment: 'configuracoes', roles: ['admin'] },
]
