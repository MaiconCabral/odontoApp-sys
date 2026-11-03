import { beforeEach, vi } from 'vitest'
import { agendaMock, businessHoursFixture, overviewFixture, patientListFixture, proceduresFixture, publicBookingFixture, roomsFixture } from './fixtures/agenda'
import { attachmentsFixture, notesFixture, odontogramFixture, patientAppointmentsFixture, paymentsFixture, reportFixture, waitlistFixture } from './fixtures/clinical'
import { platformClinicFixture, platformPlansFixture } from './fixtures/platform'
import { clinicFixture, notificationsFixture, preferencesFixture, subscriptionFixture, teamFixture } from './fixtures/settings'
import { seedSession } from './utils/session'

// happy-dom cannot draw charts. The stub keeps the props, so tests read what would be drawn.
vi.mock('vue3-apexcharts', async () => {
  const { defineComponent, h } = await import('vue')

  return {
    default: defineComponent({
      name: 'ApexChartStub',
      props: ['type', 'series', 'options', 'height'],
      setup: props => () => h('div', { 'data-testid': 'apex-chart', 'data-type': props.type }),
    }),
  }
})

// Answers at the network level, so the real services run in the page tests.
const apiResponses: Record<string, unknown> = {
  '/api/agenda': agendaMock,
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
