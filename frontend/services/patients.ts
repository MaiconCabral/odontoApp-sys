import { patientsMock } from '~/mocks/patients'

export const patientsService = {
  list: () => Promise.resolve(patientsMock),
}
