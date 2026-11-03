import type { ClinicalNote, ClinicalNoteForm, Odontogram, PatientAttachment, ToothRecord, ToothRecordForm } from '~/types/clinical'

export const clinicalService = {
  // Notes of the medical record, newest first.
  notes: async (patientId: string) =>
    (await useApi().api<{ data: ClinicalNote[] }>(`/api/patients/${patientId}/notes`)).data,

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

  // Files of the patient, newest first.
  attachments: async (patientId: string) =>
    (await useApi().api<{ data: PatientAttachment[] }>(`/api/patients/${patientId}/attachments`)).data,

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
