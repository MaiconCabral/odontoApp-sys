import type { AppointmentDetails, AppointmentForm, AppointmentStatus, AvailableSlot } from '~/types/agenda'

// Form in the API field names.
function toBody(form: AppointmentForm) {
  return {
    patient_id: form.patientId,
    professional_id: form.professionalId,
    room_id: form.roomId,
    procedure_id: form.procedure,
    date: form.date,
    time: form.time,
    notes: form.notes || null,
  }
}

export const appointmentsService = {
  // Appointment with history and the actions the user may take.
  get: (id: string) => useApi().api<AppointmentDetails>(`/api/appointments/${id}`),

  // Books an appointment.
  create: (form: AppointmentForm) =>
    useApi().api<AppointmentDetails>('/api/appointments', { method: 'POST', body: toBody(form) }),

  // Moves or edits an appointment.
  update: (id: string, form: AppointmentForm) =>
    useApi().api<AppointmentDetails>(`/api/appointments/${id}`, { method: 'PUT', body: toBody(form) }),

  // Changes the status, with a reason when cancelling or rejecting.
  changeStatus: (id: string, status: AppointmentStatus, reason?: string) =>
    useApi().api<AppointmentDetails>(`/api/appointments/${id}/status`, { method: 'POST', body: { status, reason: reason || null } }),

  // Accepts a patient request in a room.
  accept: (id: string, roomId: string) =>
    useApi().api<AppointmentDetails>(`/api/appointments/${id}/accept`, { method: 'POST', body: { room_id: roomId } }),

  // Patient requests waiting for an answer.
  requests: async () => (await useApi().api<{ data: AppointmentDetails[] }>('/api/appointment-requests')).data,

  // Free start times of a day.
  availability: async (query: { date: string, procedure: string, professionalId: string, roomId?: string, ignore?: string }) =>
    (await useApi().api<{ slots: AvailableSlot[] }>('/api/availability', {
      query: {
        date: query.date,
        procedure_id: query.procedure,
        professional_id: query.professionalId,
        room_id: query.roomId || undefined,
        ignore: query.ignore || undefined,
      },
    })).slots,
}
