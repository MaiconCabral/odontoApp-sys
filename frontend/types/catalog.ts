export type ProcedureItem = {
  id: string
  name: string
  durationMinutes: number
  priceCents: number
  active: boolean
}

export type ProcedureForm = {
  name: string
  durationMinutes: number
  price: string
  active: boolean
}

export type RoomItem = {
  id: string
  name: string
  active: boolean
}

export type RoomForm = {
  name: string
  active: boolean
}

export type PatientAppointment = {
  clinic: { name: string, address: string | null, phone: string | null }
  when: string
  procedure: string
  professional: string | null
  status: string
  statusLabel: string
  canConfirm: boolean
  canCancel: boolean
}
