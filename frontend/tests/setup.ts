import { beforeEach, vi } from 'vitest'
import { agendaMock, businessHoursFixture, overviewFixture, patientListFixture, proceduresFixture, publicBookingFixture, roomsFixture } from './fixtures/agenda'
import { attachmentsFixture, notesFixture, odontogramFixture, patientAppointmentsFixture, paymentsFixture, reportFixture, waitlistFixture } from './fixtures/clinical'
import { platformClinicFixture, platformPlansFixture } from './fixtures/platform'
import { clinicFixture, notificationsFixture, preferencesFixture, subscriptionFixture, teamFixture } from './fixtures/settings'
import { seedSession } from './utils/session'

// Charts: vue3-apexcharts is replaced by tests/stubs/apexcharts.ts through an alias in vitest.config.ts.

// FullCalendar needs a real layout; the stub keeps the events, labels and handlers.
vi.mock('@fullcalendar/vue3', async () => ({ default: (await import('./stubs/fullcalendar')).FullCalendarStub }))

// Answers at the network level, so the real services run in the page tests.
const apiResponses: Record<string, unknown> = {
  '/api/agenda': agendaMock,
  '/api/agenda/options': { today: agendaMock.today, procedures: agendaMock.procedures, professionals: agendaMock.professionals, rooms: agendaMock.rooms },
  '/api/appointment-requests': { data: [] },
  '/api/availability': { slots: [{ time: '15:00', professionalIds: ['carlos'] }, { time: '15:30', professionalIds: ['carlos'] }] },
  '/api/patients': {
    data: patientListFixture.patients,
    meta: { total: patientListFixture.total, current_page: 1, last_page: patientListFixture.lastPage },
  },
  '/api/business-hours': { data: businessHoursFixture },
  '/api/patients/patient-1': patientListFixture.patients[0],
  '/api/patients/patient-1/appointments': patientAppointmentsFixture,
  '/api/patients/patient-1/notes': notesFixture,
  '/api/patients/patient-1/odontogram': odontogramFixture,
  '/api/patients/patient-1/attachments': attachmentsFixture,
  '/api/waitlist': waitlistFixture,
  '/api/appointments/event-2/payments': paymentsFixture,
  '/api/appointments/event-4/payments': { ...paymentsFixture, paidCents: 0, balanceCents: 20000, paymentStatus: 'pending', payments: [] },
  '/api/reports': reportFixture,
  '/api/platform/session': { admin: { id: '1', name: 'Equipe', email: 'equipe@plataforma.app' } },
  '/api/platform/clinics': { data: [platformClinicFixture], meta: { total: 1, current_page: 1, last_page: 1 } },
  '/api/platform/clinics/c1': platformClinicFixture,
  '/api/platform/plans': { data: platformPlansFixture },
  '/api/public/booking': publicBookingFixture,
  '/api/public/availability': { slots: ['09:00', '09:30', '14:00'] },
  '/api/clinic': clinicFixture,
  '/api/clinic/preferences': preferencesFixture,
  '/api/clinic/subscription': subscriptionFixture,
  '/api/notifications': notificationsFixture,
  '/api/overview': overviewFixture,
  '/api/procedures': { data: proceduresFixture },
  '/api/rooms': { data: roomsFixture },
  '/api/users': { data: teamFixture },
}

beforeEach(() => {
  // Spies of one test must not answer for the next one.
  vi.restoreAllMocks()
  // Loaded data is kept between screens now, so each test starts with none.
  clearNuxtData()
  seedSession()
  useAgendaFiltersStore().reset()
  useAgendaRange().value = null
  usePatientsQuery().value = { search: '', page: 1 }
  useNotifications().reset()

  vi.stubGlobal('$fetch', vi.fn(async (path: string) => {
    if (path in apiResponses) {
      return structuredClone(apiResponses[path])
    }

    throw new Error(`Unexpected API call in a test: ${path}`)
  }))
})
