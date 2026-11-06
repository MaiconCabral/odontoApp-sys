import type { ClinicalNote, ClinicalNoteForm, Odontogram, PatientAttachment, ToothRecord, ToothRecordForm } from '~/types/clinical'

// Page as Laravel sends it.
type PageResponse<T> = { data: T[], meta: { current_page: number, last_page: number } }

// Page as the screens use it.
export type Paged<T> = { items: T[], page: number, lastPage: number }

// Turns a Laravel page into the screen shape.
function toPaged<T>(response: PageResponse<T>): Paged<T> {
  return { items: response.data, page: response.meta.current_page, lastPage: response.meta.last_page }
}

export const clinicalService = {
  // One page of the medical record notes, newest first.
  notes: async (patientId: string, page = 1): Promise<Paged<ClinicalNote>> =>
    toPaged(await useApi().api<PageResponse<ClinicalNote>>(`/api/patients/${patientId}/notes`, { query: page > 1 ? { page } : undefined })),

  // Appends a note; a correction points to the note it amends.
  addNote: (patientId: string, form: ClinicalNoteForm) =>
    useApi().api<ClinicalNote>(`/api/patients/${patientId}/notes`, {
      method: 'POST',
      body: { body: form.body, appointment_id: form.appointmentId || null, amends_id: form.amendsId || null },
    }),

  // Current state of each tooth and the latest changes.
  odontogram: (patientId: string) => useApi().api<Odontogram>(`/api/patients/${patientId}/odontogram`),

  // Records one change of a tooth.
  recordTooth: (patientId: string, form: ToothRecordForm) =>
    useApi().api<ToothRecord>(`/api/patients/${patientId}/odontogram`, {
      method: 'POST',
      body: { tooth: form.tooth, surface: form.surface, condition: form.condition, notes: form.notes || null },
    }),

  // One page of the patient files, newest first.
  attachments: async (patientId: string, page = 1): Promise<Paged<PatientAttachment>> =>
    toPaged(await useApi().api<PageResponse<PatientAttachment>>(`/api/patients/${patientId}/attachments`, { query: page > 1 ? { page } : undefined })),

  // Sends one file as multipart.
  upload: (patientId: string, file: File) => {
    const body = new FormData()

    body.append('file', file)

    return useApi().api<PatientAttachment>(`/api/patients/${patientId}/attachments`, { method: 'POST', body })
  },

  // Removes a file (admin only).
  removeAttachment: (id: string) => useApi().api<void>(`/api/attachments/${id}`, { method: 'DELETE' }),

  // Address the browser opens to download a file with the session.
  downloadUrl: (id: string) => useApi().url(`/api/attachments/${id}/download`),
}
