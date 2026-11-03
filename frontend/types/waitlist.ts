export type WaitlistStatus = 'waiting' | 'notified' | 'booked' | 'removed'

export type WaitlistPeriod = 'morning' | 'afternoon' | 'any'

export type WaitlistEntry = {
  id: string
  patient: { id: string, name: string, phone: string, hasEmail: boolean }
  procedure: string
  procedureLabel: string
  professional: { id: string, name: string } | null
  fromDate: string
  toDate: string
  period: WaitlistPeriod
  periodLabel: string
  notes: string | null
  status: WaitlistStatus
  statusLabel: string
  notifiedAt: string | null
  createdAt: string
}

export type WaitlistForm = {
  patientId: string
  procedureId: string
  professionalId: string
  fromDate: string
  toDate: string
  period: WaitlistPeriod
  notes: string
  // Only sent on edits.
  status?: WaitlistStatus
}
