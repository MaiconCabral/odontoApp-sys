import type { Overview } from '~/types/overview'

export const overviewService = {
  // Numbers of the panel home page.
  get: () => useApi().api<Overview>('/api/overview'),
}
