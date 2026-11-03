export type ClinicalNote = {
  id: string
  body: string
  author: string
  appointment: { id: string, date: string, procedureLabel: string } | null
  amendsId: string | null
  createdAt: string
}

export type ClinicalNoteForm = {
  body: string
  appointmentId: string
  amendsId: string
}

export type ToothCondition = 'healthy' | 'caries' | 'restoration' | 'root_canal' | 'crown' | 'implant' | 'extraction_needed' | 'missing'

// Mesial, distal, occlusal (or incisal), vestibular and lingual (or palatal).
export type ToothSurface = 'M' | 'D' | 'O' | 'V' | 'L'

export type ToothRecord = {
  id: string
  tooth: number
  surface: ToothSurface | null
  condition: ToothCondition
  conditionLabel: string
  notes: string | null
  author: string
  createdAt: string
}

export type Odontogram = {
  current: ToothRecord[]
  history: ToothRecord[]
}

export type ToothRecordForm = {
  tooth: number
  surface: ToothSurface | null
  condition: ToothCondition
  notes: string
}

export type PatientAttachment = {
  id: string
  name: string
  mime: string
  sizeBytes: number
  uploader: string
  createdAt: string
  canDelete: boolean
}
