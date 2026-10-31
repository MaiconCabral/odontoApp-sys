export type AgendaView = 'day' | 'week' | 'month'

export type AgendaEventTone = 'blue' | 'amber' | 'violet'

export type ProfessionalTone = 'cyan' | 'amber' | 'green'

export type AgendaProfessional = {
  id: string
  name: string
  tone: ProfessionalTone
  selected: boolean
}

export type AgendaRoom = {
  id: string
  name: string
  selected: boolean
}

export type AgendaEvent = {
  id: string
  patientName: string
  date: string
  hour: string
  procedure: string
  tone: AgendaEventTone
  professionalId: string
  roomId: string
}

export type Agenda = {
  today: string
  hours: string[]
  procedures: string[]
  freeSlotsToday: number
  professionals: AgendaProfessional[]
  rooms: AgendaRoom[]
  events: AgendaEvent[]
}

export type AgendaDay = {
  date: string
  weekday: string
  dayOfMonth: number
  isToday: boolean
}
