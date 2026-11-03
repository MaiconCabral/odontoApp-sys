import type { ClinicPreferences, PlanCode, SettingsTab } from '~/types/settings'

// Fixed texts of the settings. The values come from the backend.
export const settingsTabs: SettingsTab[] = [
  { id: 'clinic', label: 'Clínica' },
  { id: 'team', label: 'Equipe' },
  { id: 'procedures', label: 'Procedimentos' },
  { id: 'rooms', label: 'Salas' },
  { id: 'hours', label: 'Horários' },
  { id: 'subscription', label: 'Assinatura' },
]

export const preferenceTexts: Record<keyof ClinicPreferences, { title: string, description: string }> = {
  reminder24h: {
    title: 'Lembrete 24h antes',
    description: 'Enviar ao paciente um e-mail para confirmar ou cancelar a consulta',
  },
  autoConfirmation: {
    title: 'Confirmação automática',
    description: 'Aceitar os pedidos de agendamento sem passar pela recepção',
  },
  waitingList: {
    title: 'Lista de espera',
    description: 'Avisar quando surgir um horário disponível',
  },
}

// What each plan offers beyond the number of professionals.
export const planExtras: Record<PlanCode, string[]> = {
  free: ['Agenda completa'],
  pro: ['Agenda completa', 'Lembretes automáticos'],
  enterprise: ['Agenda completa', 'Lembretes automáticos', 'Suporte prioritário'],
}
