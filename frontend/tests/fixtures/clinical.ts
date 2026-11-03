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

export const notesFixture: { data: ClinicalNote[] } = {
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

export const attachmentsFixture: { data: PatientAttachment[] } = {
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
