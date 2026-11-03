import type { BookingForm, PublicBookingInfo } from '~/types/schedule'

export const publicBookingService = {
  // Clinic, procedures and professionals a patient chooses from.
  info: () => useApi().api<PublicBookingInfo>('/api/public/booking'),

  // Free start times of a day.
  availability: async (query: { date: string, procedure: string, professionalId: string }) =>
    (await useApi().api<{ slots: string[] }>('/api/public/availability', {
      query: { date: query.date, procedure_id: query.procedure, professional_id: query.professionalId || undefined },
    })).slots,

  // Sends the booking request.
  request: (form: BookingForm) =>
    useApi().api<{ message: string }>('/api/public/booking-requests', {
      method: 'POST',
      body: {
        procedure_id: form.procedure,
        professional_id: form.professionalId || null,
        date: form.date,
        time: form.time,
        name: form.name,
        phone: form.phone,
        email: form.email || null,
        notes: form.notes || null,
        consent: form.consent,
        website: form.website,
      },
    }),
}
