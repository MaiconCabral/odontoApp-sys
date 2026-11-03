import type { PatientTone } from '~/types/patients'

export type MetricIcon = 'calendar' | 'billing' | 'attendance' | 'patients'

export type MetricTone = 'blue' | 'green' | 'amber' | 'violet'

export type Trend = 'up' | 'down' | 'flat'

export type Metric = {
  id: string
  label: string
  value: string
  change: string
  trend: Trend
  period: string
  icon: MetricIcon
  tone: MetricTone
  // Second line under the value, like the estimate under what was received.
  detail?: string
}

export type WeeklyBar = {
  day: string
  value: number
  highlighted?: boolean
}

export type WeeklyAppointments = {
  total: number
  change: string
  trend: Trend
  comparison: string
  range: string
  scaleMax: number
  axis: number[]
  bars: WeeklyBar[]
}

export type AppointmentStatus = 'completed' | 'in_progress' | 'waiting'

export type UpcomingAppointment = {
  id: string
  time: string
  duration: string
  patientInitials: string
  patientName: string
  patientTone: PatientTone
  procedure: string
  professional: string
  status: AppointmentStatus
  statusLabel: string
}

export type Overview = {
  greeting: string
  metrics: Metric[]
  weeklyAppointments: WeeklyAppointments
  occupancyPercentage: number
  agendaDate: string
  upcomingAppointments: UpcomingAppointment[]
}
