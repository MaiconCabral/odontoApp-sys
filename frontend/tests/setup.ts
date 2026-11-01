import { beforeEach } from 'vitest'
import { seedSession } from './utils/session'

beforeEach(() => {
  seedSession()
  useAgendaFiltersStore().reset()
})
