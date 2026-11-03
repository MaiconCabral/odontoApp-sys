import type { Agenda, AgendaEvent, AppointmentDetails } from '~/types/agenda'
import type { Patient, PatientList } from '~/types/patients'
import type { BusinessDay, PublicBookingInfo } from '~/types/schedule'
import type { ProcedureItem, RoomItem } from '~/types/catalog'
import type { Overview } from '~/types/overview'

const HOURS = Array.from({ length: 20 }, (_, index) => {
  const minutes = 8 * 60 + index * 30

  return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`
})

// Event with the defaults of a one-hour scheduled cleaning.
function event(overrides: Partial<AgendaEvent>): AgendaEvent {
  return {
    id: 'event',
    patientName: 'Paciente',
    date: '2024-10-24',
    hour: '09:00',
    endHour: '10:00',
    duration: 60,
    procedure: 'Limpeza e prevenção',
    procedureId: '3',
    status: 'scheduled',
    statusLabel: 'Agendado',
    source: 'internal',
    professionalId: 'carlos',
    roomId: 'room-1',
    ...overrides,
  }
}

export const agendaMock: Agenda = {
  today: '2024-10-24',
  from: '2024-10-21',
  to: '2024-12-01',
  hours: HOURS,
  procedures: [
    { id: '1', label: 'Avaliação', duration: 30 },
    { id: '3', label: 'Limpeza e prevenção', duration: 60 },
    { id: '4', label: 'Restauração estética', duration: 90 },
  ],
  freeSlotsToday: 12,
  professionals: [
    { id: 'carlos', name: 'Dr. Carlos Mendes', tone: 'cyan', selected: true },
    { id: 'ana', name: 'Dra. Ana Beatriz', tone: 'amber', selected: true },
    { id: 'felipe', name: 'Dr. Felipe Rocha', tone: 'green', selected: false },
  ],
  rooms: [
    { id: 'room-1', name: 'Sala 01 · Consultório', selected: true },
    { id: 'room-2', name: 'Sala 02 · Cirurgia', selected: true },
    { id: 'room-3', name: 'Sala Antiga', active: false, selected: true },
  ],
  events: [
    event({ id: 'event-1', patientName: 'Rafael Oliveira', date: '2024-10-22', professionalId: 'ana', status: 'confirmed', statusLabel: 'Confirmado' }),
    event({ id: 'event-2', patientName: 'Camila Ferreira', hour: '10:00', endHour: '11:00' }),
    event({ id: 'event-3', patientName: 'Juliana Almeida', date: '2024-10-25', hour: '14:00', endHour: '15:00', roomId: 'room-2' }),
    event({ id: 'event-4', patientName: 'Pedido Online', date: '2024-10-25', hour: '15:30', endHour: '16:00', duration: 30, procedure: 'Avaliação', procedureId: '1', status: 'requested', statusLabel: 'Solicitado', source: 'public', roomId: null }),
  ],
  blocks: [
    { id: 'block-1', professionalId: null, startsAt: '2024-10-26 00:00', endsAt: '2024-10-26 23:59', reason: 'Feriado municipal' },
  ],
}

export const appointmentDetails: AppointmentDetails = {
  id: 'event-2',
  status: 'scheduled',
  statusLabel: 'Agendado',
  procedure: '3',
  procedureLabel: 'Limpeza e prevenção',
  duration: 60,
  date: '2024-10-24',
  hour: '10:00',
  endHour: '11:00',
  source: 'internal',
  notes: null,
  cancelReason: null,
  requestedAt: '2024-10-20T10:00:00-03:00',
  patient: { id: 'patient-1', name: 'Camila Ferreira', phone: '(11) 99632-4510', email: null },
  professional: { id: 'carlos', name: 'Dr. Carlos Mendes' },
  room: { id: 'room-1', name: 'Sala 01 · Consultório' },
  priceCents: 20000,
  patientHasEmail: true,
  notifications: [
    { type: 'scheduled', typeLabel: 'Consulta agendada', channel: 'mail', status: 'sent', statusLabel: 'Enviado', at: '2024-10-20T10:01:00-03:00' },
  ],
  history: [
    { from: null, fromLabel: null, to: 'scheduled', toLabel: 'Agendado', reason: null, author: 'Paula Nunes', at: '2024-10-20T10:00:00-03:00' },
  ],
  actions: ['confirm', 'cancel', 'reschedule'],
}

export const requestDetails: AppointmentDetails = {
  ...appointmentDetails,
  id: 'event-4',
  status: 'requested',
  statusLabel: 'Solicitado',
  source: 'public',
  room: null,
  patient: { id: 'patient-9', name: 'Pedido Online', phone: '(11) 98888-0000', email: null },
  patientHasEmail: false,
  notifications: [
    { type: 'requested', typeLabel: 'Pedido recebido', channel: 'mail', status: 'skipped', statusLabel: 'Não enviado: paciente sem e-mail', at: '2024-10-20T10:00:00-03:00' },
  ],
  actions: ['accept', 'reject', 'cancel'],
}

export const patientFixture: Patient = {
  id: 'patient-1',
  name: 'Camila Ferreira',
  initials: 'CF',
  tone: 'blue',
  cpf: '000.000.000-03',
  phone: '(11) 99632-4510',
  email: null,
  birthDate: null,
  insurance: 'Bradesco Saúde',
  isPrivate: false,
  notes: null,
  lastAppointment: '06/10/2024',
}

export const patientListFixture: PatientList = {
  patients: [
    patientFixture,
    { ...patientFixture, id: 'patient-2', name: 'Rafael Oliveira', initials: 'RO', cpf: null, phone: '(11) 99127-7844', insurance: 'Particular', isPrivate: true, lastAppointment: 'Sem consultas' },
  ],
  total: 42,
  page: 1,
  lastPage: 3,
}

export const businessHoursFixture: BusinessDay[] = [
  ...[1, 2, 3, 4, 5].map(weekday => ({
    weekday,
    label: ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta'][weekday - 1] ?? '',
    closed: false,
    opensAt: '08:00',
    closesAt: '18:00',
    breakStartsAt: '12:00',
    breakEndsAt: '13:00',
  })),
  { weekday: 6, label: 'Sábado', closed: false, opensAt: '08:00', closesAt: '12:00', breakStartsAt: null, breakEndsAt: null },
  { weekday: 7, label: 'Domingo', closed: true, opensAt: null, closesAt: null, breakStartsAt: null, breakEndsAt: null },
]

export const publicBookingFixture: PublicBookingInfo = {
  clinic: { name: 'Sorriso Perfeito', unit: 'Unidade Jardins', phone: '(11) 3051-8090' },
  procedures: agendaMock.procedures,
  professionals: [
    { id: 'carlos', name: 'Dr. Carlos Mendes', specialty: 'Ortodontia' },
    { id: 'ana', name: 'Dra. Ana Beatriz', specialty: null },
  ],
  maxDaysAhead: 60,
}

export const proceduresFixture: ProcedureItem[] = [
  { id: '1', name: 'Avaliação', durationMinutes: 30, priceCents: 15000, active: true },
  { id: '6', name: 'Clareamento', durationMinutes: 90, priceCents: 45000, active: true },
  { id: '7', name: 'Antigo', durationMinutes: 60, priceCents: 0, active: false },
]

export const roomsFixture: RoomItem[] = [
  { id: 'room-1', name: 'Sala 01 · Consultório', active: true },
  { id: 'room-3', name: 'Sala Antiga', active: false },
]

export const overviewFixture: Overview = {
  greeting: 'Bom dia, Carlos',
  metrics: [
    { id: 'appointments', label: 'Consultas hoje', value: '18', change: '12,5%', trend: 'up', period: 'vs. ontem', icon: 'calendar', tone: 'blue' },
    { id: 'revenue', label: 'Faturamento estimado', value: 'R$ 4.280', change: '8,2%', trend: 'down', period: 'vs. ontem', icon: 'billing', tone: 'green' },
    { id: 'attendance', label: 'Taxa de comparecimento', value: '94,6%', change: '2,4 p.p.', trend: 'up', period: 'este mês', icon: 'attendance', tone: 'amber' },
    { id: 'new-patients', label: 'Novos pacientes', value: '32', change: '—', trend: 'flat', period: 'este mês', icon: 'patients', tone: 'violet' },
  ],
  weeklyAppointments: {
    total: 86,
    change: '14,8%',
    trend: 'up',
    comparison: 'vs. semana anterior',
    range: 'Esta semana',
    scaleMax: 110,
    axis: [80, 60, 40, 20, 0],
    bars: [
      { day: 'Seg', value: 46 },
      { day: 'Ter', value: 64 },
      { day: 'Qua', value: 53 },
      { day: 'Qui', value: 81, highlighted: true },
      { day: 'Sex', value: 74 },
      { day: 'Sáb', value: 37 },
      { day: 'Dom', value: 24 },
    ],
  },
  occupancyPercentage: 78,
  agendaDate: '24 de outubro de 2024',
  upcomingAppointments: [
    { id: '11', time: '08:30', duration: '30 min', patientInitials: 'MC', patientName: 'Mariana Costa', patientTone: 'blue', procedure: 'Avaliação', professional: 'Dr. Carlos', status: 'completed', statusLabel: 'Concluído' },
    { id: '12', time: '10:00', duration: '60 min', patientInitials: 'CF', patientName: 'Camila Ferreira', patientTone: 'blue', procedure: 'Limpeza e prevenção', professional: 'Dr. Carlos', status: 'in_progress', statusLabel: 'Em atendimento' },
    { id: '13', time: '11:30', duration: '30 min', patientInitials: 'LM', patientName: 'Lucas Martins', patientTone: 'green', procedure: 'Consulta de retorno', professional: 'Dra. Ana', status: 'waiting', statusLabel: 'Confirmado' },
    { id: '14', time: '09:00', duration: '60 min', patientInitials: 'RO', patientName: 'Rafael Oliveira', patientTone: 'amber', procedure: 'Limpeza e prevenção', professional: 'Dra. Ana', status: 'completed', statusLabel: 'Faltou' },
  ],
}