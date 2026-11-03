import type { PatientAppointment } from '~/types/catalog'

export const patientLinksService = {
  // Appointment of the link the patient received.
  get: (token: string) => useApi().api<PatientAppointment>(`/api/public/appointments/${encodeURIComponent(token)}`),

  // Confirms the attendance.
  confirm: (token: string) =>
    useApi().api<PatientAppointment>(`/api/public/appointments/${encodeURIComponent(token)}/confirm`, { method: 'POST' }),

  // Cancels the appointment.
  cancel: (token: string) =>
    useApi().api<PatientAppointment>(`/api/public/appointments/${encodeURIComponent(token)}/cancel`, { method: 'POST' }),
}
