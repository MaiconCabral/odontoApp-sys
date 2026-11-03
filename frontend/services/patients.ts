import type { AppointmentDetails } from '~/types/agenda'
import type { Patient, PatientForm, PatientList, PatientQuery } from '~/types/patients'

type PatientPage = {
  data: Patient[]
  meta: { total: number, current_page: number, last_page: number }
}

// Form in the API field names.
function toBody(form: PatientForm) {
  return {
    name: form.name,
    cpf: form.cpf || null,
    phone: form.phone,
    email: form.email || null,
    insurance: form.insurance || null,
    birth_date: form.birthDate || null,
    notes: form.notes.trim() || null,
  }
}

export const patientsService = {
  // One page of patients, filtered by the search.
  list: async (query: PatientQuery = { search: '', page: 1 }): Promise<PatientList> => {
    const response = await useApi().api<PatientPage>('/api/patients', {
      query: { search: query.search || undefined, page: query.page },
    })

    return {
      patients: response.data,
      total: response.meta.total,
      page: response.meta.current_page,
      lastPage: response.meta.last_page,
    }
  },

  // One patient, for the patient page.
  get: (id: string) => useApi().api<Patient>(`/api/patients/${id}`),

  // Appointments of the patient, newest first, one page at a time.
  appointments: async (id: string, page = 1) => {
    const response = await useApi().api<{ data: AppointmentDetails[], meta: { current_page: number, last_page: number } }>(
      `/api/patients/${id}/appointments`,
      { query: { page } },
    )

    return { appointments: response.data, page: response.meta.current_page, lastPage: response.meta.last_page }
  },

  // Adds a patient.
  create: (form: PatientForm) => useApi().api<Patient>('/api/patients', { method: 'POST', body: toBody(form) }),

  // Updates a patient.
  update: (id: string, form: PatientForm) =>
    useApi().api<Patient>(`/api/patients/${id}`, { method: 'PUT', body: toBody(form) }),

  // Deletes a patient without appointments.
  remove: (id: string) => useApi().api<void>(`/api/patients/${id}`, { method: 'DELETE' }),
}
