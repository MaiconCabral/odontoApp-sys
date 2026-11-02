import { beforeEach, vi } from 'vitest'
import { agendaMock } from './fixtures/agenda'
import { clinicFixture, teamFixture } from './fixtures/settings'
import { seedSession } from './utils/session'

// Answers at the network level, so the real services run in the page tests.
const apiResponses: Record<string, unknown> = {
  '/api/agenda': agendaMock,
  '/api/clinic': clinicFixture,
  '/api/users': { data: teamFixture },
}

beforeEach(() => {
  seedSession()
  useAgendaFiltersStore().reset()

  vi.stubGlobal('$fetch', vi.fn(async (path: string) => {
    if (path in apiResponses) {
      return apiResponses[path]
    }

    throw new Error(`Unexpected API call in a test: ${path}`)
  }))
})
