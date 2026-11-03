import type { PaymentStatus } from '~/types/agenda'

export type PaymentMethod = 'pix' | 'credit_card' | 'debit_card' | 'cash' | 'insurance' | 'other'

export type Payment = {
  id: string
  amountCents: number
  method: PaymentMethod
  methodLabel: string
  paidAt: string
  notes: string | null
  recordedBy: string
  voided: boolean
  voidReason: string | null
  voidedBy: string | null
  canVoid: boolean
}

// What an appointment cost, what was paid and the payments behind it.
export type PaymentSummary = {
  priceCents: number
  paidCents: number
  balanceCents: number
  paymentStatus: PaymentStatus
  payments: Payment[]
}

export type PaymentForm = {
  // Typed as "150,00"; turned into cents before sending.
  amount: string
  method: PaymentMethod
  paidAt: string
  notes: string
  allowOver: boolean
}

export type ReportGroup = 'day' | 'professional' | 'procedure'

export type ReportRow = {
  key: string
  label: string
  appointments: number
  completed: number
  noShow: number
  absenceRate: number | null
  expectedCents: number
  receivedCents: number
}

export type Report = {
  from: string
  to: string
  group: ReportGroup
  rows: ReportRow[]
  totals: Omit<ReportRow, 'key' | 'label'>
  byMethod: { method: PaymentMethod, methodLabel: string, receivedCents: number }[]
}
