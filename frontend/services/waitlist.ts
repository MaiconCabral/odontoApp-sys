import type { WaitlistEntry, WaitlistForm, WaitlistStatus } from '~/types/waitlist'

// Form in the API field names.
function toBody(form: WaitlistForm) {
  return {
    patient_id: form.patientId,
    procedure_id: form.procedureId,
    professional_id: form.professionalId || null,
    from_date: form.fromDate,
    to_date: form.toDate,
    period: form.period,
    notes: form.notes || null,
    ...(form.status ? { status: form.status } : {}),
  }
}

export const waitlistService = {
  // Entries of the list; without a status, the open ones.
  list: async (query: { status?: WaitlistStatus, patientId?: string } = {}) =>
    (await useApi().api<{ data: WaitlistEntry[] }>('/api/waitlist', {
      query: { status: query.status, patient_id: query.patientId },
    })).data,

  // Adds a patient to the list.
  create: (form: WaitlistForm) => useApi().api<WaitlistEntry>('/api/waitlist', { method: 'POST', body: toBody(form) }),

  // Edits an entry or changes its status.
  update: (id: string, form: WaitlistForm) =>
    useApi().api<WaitlistEntry>(`/api/waitlist/${id}`, { method: 'PUT', body: toBody(form) }),

  // Removes an entry.
  remove: (id: string) => useApi().api<void>(`/api/waitlist/${id}`, { method: 'DELETE' }),
}
