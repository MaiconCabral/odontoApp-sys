import type { Settings } from '~/types/settings'

// What still has no API. The clinic and the team already come from the backend.
export const settingsMock: Omit<Settings, 'clinic'> = {
  tabs: [
    { id: 'clinic', label: 'Clínica' },
    { id: 'team', label: 'Equipe' },
    { id: 'procedures', label: 'Procedimentos' },
    { id: 'rooms', label: 'Salas' },
    { id: 'hours', label: 'Horários' },
    { id: 'subscription', label: 'Assinatura', badge: 'Pro' },
  ],
  preferences: [
    {
      id: 'auto-confirmation',
      title: 'Confirmação automática',
      description: 'Enviar lembrete 24 horas antes da consulta',
      enabled: true,
    },
    {
      id: 'waiting-list',
      title: 'Lista de espera',
      description: 'Avisar quando surgir um horário disponível',
      enabled: true,
    },
  ],
  subscription: {
    status: 'Trial ativo',
    price: 'R$ 249',
    period: '/mês',
    features: ['Agenda completa', 'Até 5 profissionais', 'Lembretes automáticos'],
  },
}
