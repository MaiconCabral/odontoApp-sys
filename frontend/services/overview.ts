import { overviewMock } from '~/mocks/overview'

export const overviewService = {
  get: () => Promise.resolve(overviewMock),
}
