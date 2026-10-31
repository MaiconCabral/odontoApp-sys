import { describe, expect, it } from 'vitest'
import { patientsMock } from '~/mocks/patients'
import type { Patient } from '~/types/patients'
import { matchesPatientSearch } from '~/utils/patients'

const camila = patientsMock.patients.find(patient => patient.name === 'Camila Ferreira') as Patient

describe('matchesPatientSearch', () => {
  it('matches everyone when the search is empty', () => {
    expect(matchesPatientSearch(camila, '   ')).toBe(true)
  })

  it('ignores case and accents in the name', () => {
    expect(matchesPatientSearch(camila, 'FERREIRA')).toBe(true)
    expect(matchesPatientSearch(camila, 'cámila')).toBe(true)
  })

  it('matches the phone and the CPF with or without formatting', () => {
    expect(matchesPatientSearch(camila, '(11) 99632')).toBe(true)
    expect(matchesPatientSearch(camila, '996324510')).toBe(true)
    expect(matchesPatientSearch(camila, '000.000.000-03')).toBe(true)
    expect(matchesPatientSearch(camila, '00000000003')).toBe(true)
  })

  it('does not match unrelated searches', () => {
    expect(matchesPatientSearch(camila, 'Lucas')).toBe(false)
    expect(matchesPatientSearch(camila, '12345678')).toBe(false)
  })
})
