import type { NavEntry } from '~/types/workspace'

export const navEntriesMock: NavEntry[] = [
  { id: 'overview', label: 'Visão geral', icon: 'overview', segment: '' },
  { id: 'agenda', label: 'Agenda', icon: 'agenda', segment: 'agenda', count: 5 },
  { id: 'patients', label: 'Pacientes', icon: 'patients', segment: 'pacientes' },
  { id: 'settings', label: 'Configurações', icon: 'settings', segment: 'configuracoes' },
]
