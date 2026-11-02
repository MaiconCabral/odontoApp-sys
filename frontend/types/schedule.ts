export type BusinessDay = {
  weekday: number
  label: string
  closed: boolean
  opensAt: string | null
  closesAt: string | null
  breakStartsAt: string | null
  breakEndsAt: string | null
}

export type PublicBookingInfo = {
  clinic: { name: string, unit: string, phone: string | null }
  procedures: { key: string, label: string, duration: number }[]
  professionals: { id: string, name: string, specialty: string | null }[]
  maxDaysAhead: number
}

export type BookingForm = {
  procedure: string
  professionalId: string
  date: string
  time: string
  name: string
  phone: string
  email: string
  notes: string
  consent: boolean
  website: string
}
