import type { PaymentSummary, Report } from '~/types/finance'
import type { AppointmentDetails } from '~/types/agenda'
import type { ClinicalNote, Odontogram, PatientAttachment } from '~/types/clinical'
import type { WaitlistEntry } from '~/types/waitlist'

// Answer of /api/patients/patient-1/appointments.
export const patientAppointmentsFixture: { data: AppointmentDetails[], meta: { current_page: number, last_page: number } } = {
  data: [
    {
      id: '42',
      status: 'completed',
      statusLabel: 'Concluído',
      procedure: '1',
      procedureLabel: 'Avaliação',
      duration: 30,
      date: '2024-10-06',
      hour: '09:00',
      endHour: '09:30',
      source: 'internal',
      notes: null,
      cancelReason: null,
      requestedAt: null,
      patient: { id: 'patient-1', name: 'Camila Ferreira', phone: '(11) 99632-4510', email: null },
      professional: { id: 'ana', name: 'Dra. Ana Beatriz' },
      room: null,
      actions: [],
    },
  ],
  meta: { current_page: 1, last_page: 1 },
}

export const notesFixture: { data: ClinicalNote[], meta: { current_page: number, last_page: number } } = {
  meta: { current_page: 1, last_page: 1 },
  data: [
    {
      id: 'n1',
      body: 'Sensibilidade no 16.',
      author: 'Dra. Ana Beatriz',
      appointment: { id: '42', date: '2024-10-06', procedureLabel: 'Avaliação' },
      amendsId: null,
      createdAt: '2024-10-06T09:40:00-03:00',
    },
  ],
}

export const odontogramFixture: Odontogram = {
  current: [
    { id: 't1', tooth: 16, surface: 'O', condition: 'caries', conditionLabel: 'Cárie', notes: null, author: 'Dra. Ana Beatriz', createdAt: '2024-10-06T09:40:00-03:00' },
    { id: 't2', tooth: 36, surface: null, condition: 'missing', conditionLabel: 'Ausente', notes: null, author: 'Dra. Ana Beatriz', createdAt: '2024-10-06T09:41:00-03:00' },
  ],
  history: [],
}

export const attachmentsFixture: { data: PatientAttachment[], meta: { current_page: number, last_page: number } } = {
  meta: { current_page: 1, last_page: 1 },
  data: [
    { id: 'a1', name: 'raio-x.pdf', mime: 'application/pdf', sizeBytes: 245760, uploader: 'Dra. Ana Beatriz', createdAt: '2024-10-06T09:45:00-03:00', canDelete: true },
  ],
}

export const waitlistFixture: { data: WaitlistEntry[] } = {
  data: [
    {
      id: 'w1',
      patient: { id: 'patient-1', name: 'Camila Ferreira', phone: '(11) 99632-4510', hasEmail: false },
      procedure: '1',
      procedureLabel: 'Avaliação',
      professional: null,
      fromDate: '2024-10-10',
      toDate: '2024-10-30',
      period: 'morning',
      periodLabel: 'Manhã',
      notes: null,
      status: 'waiting',
      statusLabel: 'Aguardando',
      notifiedAt: null,
      createdAt: '2024-10-06T10:00:00-03:00',
    },
  ],
}

// Answer of /api/appointments/event-2/payments: R$ 200, half paid by Pix.
export const paymentsFixture: PaymentSummary = {
  priceCents: 20000,
  paidCents: 10000,
  balanceCents: 10000,
  paymentStatus: 'partial',
  payments: [
    { id: 'p1', amountCents: 10000, method: 'pix', methodLabel: 'Pix', paidAt: '2024-10-24', notes: null, recordedBy: 'Paula Nunes', voided: false, voidReason: null, voidedBy: null, canVoid: true },
  ],
}

// Answer of /api/reports grouped by professional.
export const reportFixture: Report = {
  from: '2024-10-01',
  to: '2024-10-31',
  group: 'professional',
  rows: [
    { key: 'carlos', label: 'Dr. Carlos Mendes', appointments: 12, completed: 9, noShow: 1, absenceRate: 10, expectedCents: 180000, receivedCents: 150000 },
    { key: 'ana', label: 'Dra. Ana Beatriz; "Estética"', appointments: 5, completed: 4, noShow: 0, absenceRate: 0, expectedCents: 90000, receivedCents: 90000 },
  ],
  totals: { appointments: 17, completed: 13, noShow: 1, absenceRate: 7.1, expectedCents: 270000, receivedCents: 240000 },
  byMethod: [{ method: 'pix', methodLabel: 'Pix', receivedCents: 240000 }],
}
