import { agendaMock } from '~/mocks/agenda'

export const agendaService = {
  get: () => Promise.resolve(agendaMock),
}
