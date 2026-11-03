import { beforeEach, vi } from 'vitest'
import { agendaMock, businessHoursFixture, overviewFixture, patientListFixture, proceduresFixture, publicBookingFixture, roomsFixture } from './fixtures/agenda'
import { clinicFixture, teamFixture } from './fixtures/settings'
import { seedSession } from './utils/session'

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
  '/api/public/booking': publicBookingFixture,
  '/api/public/availability': { slots: ['09:00', '09:30', '14:00'] },
  '/api/clinic': clinicFixture,
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

  vi.stubGlobal('$fetch', vi.fn(async (path: string) => {
    if (path in apiResponses) {
      return structuredClone(apiResponses[path])
    }

    throw new Error(`Unexpected API call in a test: ${path}`)
  }))
})
