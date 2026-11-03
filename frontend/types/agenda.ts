export type AgendaView = 'day' | 'week' | 'month'

export type ProfessionalTone = 'cyan' | 'amber' | 'green'

// "none" is a free appointment.
export type PaymentStatus = 'none' | 'pending' | 'partial' | 'paid'

export type AppointmentStatus = 'requested' | 'scheduled' | 'confirmed' | 'completed' | 'no_show' | 'cancelled' | 'rejected'

export type AppointmentAction = 'accept' | 'reject' | 'confirm' | 'complete' | 'no_show' | 'cancel' | 'reschedule'

export type ProcedureOption = {
  id: string
  label: string
  duration: number
}

export type AgendaProfessional = {
  id: string
  name: string
  tone: ProfessionalTone
  selected: boolean
}

export type AgendaRoom = {
  id: string
  name: string
  active?: boolean
  selected: boolean
}

export type AgendaEvent = {
  id: string
  patientName: string
  date: string
  hour: string
  endHour: string
  duration: number
  procedure: string
  procedureId: string
  status: AppointmentStatus
  statusLabel: string
  source: 'internal' | 'public'
  professionalId: string | null
  roomId: string | null
  paymentStatus?: PaymentStatus
}

export type AgendaBlock = {
  id: string
  professionalId: string | null
  startsAt: string
  endsAt: string
  reason: string
}

export type Agenda = {
  today: string
  from: string
  to: string
  hours: string[]
  procedures: ProcedureOption[]
  freeSlotsToday: number
  professionals: AgendaProfessional[]
  rooms: AgendaRoom[]
  events: AgendaEvent[]
  blocks: AgendaBlock[]
}

export type AgendaRange = {
  from: string
  to: string
}

export type AppointmentHistoryEntry = {
  from: AppointmentStatus | null
  fromLabel: string | null
  to: AppointmentStatus
  toLabel: string
  reason: string | null
  author: string
  at: string
}

export type NotificationEntry = {
  type: string
  typeLabel: string
  channel: string
  status: 'queued' | 'sent' | 'skipped' | 'failed'
  statusLabel: string
  at: string
}

export type AppointmentDetails = {
  id: string
  status: AppointmentStatus
  statusLabel: string
  procedure: string
  procedureLabel: string
  duration: number
  date: string
  hour: string
  endHour: string
  source: 'internal' | 'public'
  notes: string | null
  cancelReason: string | null
  requestedAt: string | null
  patient: { id: string, name: string, phone: string, email: string | null }
  professional: { id: string, name: string } | null
  room: { id: string, name: string } | null
  priceCents?: number
  paidCents?: number
  balanceCents?: number
  paymentStatus?: PaymentStatus
  patientHasEmail?: boolean
  notifications?: NotificationEntry[]
  history?: AppointmentHistoryEntry[]
  actions: AppointmentAction[]
}

export type AppointmentForm = {
  patientId: string
  procedure: string
  professionalId: string
  roomId: string
  date: string
  time: string
  notes: string
}

export type AvailableSlot = {
  time: string
  professionalIds: string[]
}

export type ScheduleBlockForm = {
  professionalId: string
  startsAt: string
  endsAt: string
  reason: string
}
