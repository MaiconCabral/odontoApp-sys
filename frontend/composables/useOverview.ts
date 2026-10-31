import { overviewMock } from '~/mocks/overview'

export function useOverview() {
  return useAsyncData('overview', () => Promise.resolve(overviewMock))
}
