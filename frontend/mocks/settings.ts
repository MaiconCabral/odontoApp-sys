import type { Settings } from '~/types/settings'

export const settingsMock: Settings = {
  tabs: [
    { id: 'clinic', label: 'Clínica' },
    { id: 'professionals', label: 'Profissionais' },
    { id: 'hours', label: 'Horários' },
    { id: 'subscription', label: 'Assinatura', badge: 'Pro' },
  ],
  clinic: {
    name: 'Sorriso Perfeito Odontologia',
    cnpj: '12.345.678/0001-90',
    phone: '(11) 3051-8090',
    email: 'contato@sorrisoperfeito.com',
    address: 'Rua Oscar Freire, 1240 · Jardins, São Paulo - SP',
  },
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
  professionals: [
    { id: 'carlos', name: 'Dr. Carlos Mendes', specialty: 'Ortodontia' },
    { id: 'ana', name: 'Dra. Ana Beatriz', specialty: 'Odontologia estética' },
    { id: 'felipe', name: 'Dr. Felipe Rocha', specialty: 'Cirurgia' },
  ],
  openingHours: [
    { id: 'weekdays', days: 'Segunda a sexta', hours: '08:00 às 18:00' },
    { id: 'saturday', days: 'Sábado', hours: '08:00 às 12:00' },
    { id: 'sunday', days: 'Domingo', hours: 'Fechado' },
  ],
  subscription: {
    status: 'Trial ativo',
    price: 'R$ 249',
    period: '/mês',
    features: ['Agenda completa', 'Até 5 profissionais', 'Lembretes automáticos'],
  },
}
