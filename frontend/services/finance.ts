import type { PaymentForm, PaymentSummary, Report, ReportGroup } from '~/types/finance'

export const financeService = {
  // Payments of an appointment, with what is left to pay.
  payments: (appointmentId: string) => useApi().api<PaymentSummary>(`/api/appointments/${appointmentId}/payments`),

  // Records a payment; the amount was already checked with toCents.
  recordPayment: (appointmentId: string, form: PaymentForm) =>
    useApi().api<PaymentSummary>(`/api/appointments/${appointmentId}/payments`, {
      method: 'POST',
      body: {
        amount_cents: toCents(form.amount),
        method: form.method,
        paid_at: form.paidAt || null,
        notes: form.notes || null,
        allow_over: form.allowOver,
      },
    }),

  // Voids a payment with a reason (admin only).
  voidPayment: (paymentId: string, reason: string) =>
    useApi().api<PaymentSummary>(`/api/payments/${paymentId}/void`, { method: 'POST', body: { reason } }),

  // Report of a period grouped by day, professional or procedure.
  report: (from: string, to: string, group: ReportGroup) =>
    useApi().api<Report>('/api/reports', { query: { from, to, group } }),
}
