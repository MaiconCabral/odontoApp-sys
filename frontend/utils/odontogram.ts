import type { ToothCondition, ToothRecord, ToothSurface } from '~/types/clinical'

export const CONDITION_LABELS: Record<ToothCondition, string> = {
  healthy: 'Saudável',
  caries: 'Cárie',
  restoration: 'Restauração',
  root_canal: 'Endodontia',
  crown: 'Coroa',
  implant: 'Implante',
  extraction_needed: 'Extração indicada',
  missing: 'Ausente',
}

export const SURFACE_LABELS: Record<ToothSurface, string> = {
  M: 'mesial',
  D: 'distal',
  O: 'oclusal',
  V: 'vestibular',
  L: 'lingual',
}

// Conditions of the whole tooth, recorded without a surface (same rule as the API).
export const WHOLE_TOOTH: ToothCondition[] = ['root_canal', 'crown', 'implant', 'extraction_needed', 'missing']

// Fill of a surface by condition and the matching swatch of the legend. Tones follow the theme.
export const SURFACE_FILL: Partial<Record<ToothCondition, { fill: string, swatch: string }>> = {
  caries: { fill: 'fill-tone-red-solid', swatch: 'bg-tone-red-solid' },
  restoration: { fill: 'fill-brand', swatch: 'bg-brand' },
  healthy: { fill: 'fill-surface', swatch: 'bg-surface' },
}

// Mark of a whole-tooth condition, drawn under the tooth.
export const TOOTH_MARK: Partial<Record<ToothCondition, { letter: string, class: string }>> = {
  root_canal: { letter: 'E', class: 'text-tone-violet-text' },
  crown: { letter: 'C', class: 'text-tone-amber-text' },
  implant: { letter: 'I', class: 'text-tone-green-text' },
  extraction_needed: { letter: 'X', class: 'text-tone-red-text' },
  missing: { letter: '—', class: 'text-faint' },
}

// Rows of the chart as seen facing the patient: their right side is on the left.
export const PERMANENT_ROWS = [
  [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28],
  [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38],
]

export const DECIDUOUS_ROWS = [
  [55, 54, 53, 52, 51, 61, 62, 63, 64, 65],
  [85, 84, 83, 82, 81, 71, 72, 73, 74, 75],
]

// Upper teeth are in quadrants 1, 2, 5 and 6.
export function isUpper(tooth: number): boolean {
  return [1, 2, 5, 6].includes(Math.floor(tooth / 10))
}

/**
 * Surfaces on each side of the drawing. Vestibular faces out of the mouth
 * (up on upper teeth, down on lower ones); mesial faces the middle of the arch.
 */
export function surfaceSides(tooth: number): { top: ToothSurface, bottom: ToothSurface, left: ToothSurface, right: ToothSurface } {
  const onPatientRight = [1, 4, 5, 8].includes(Math.floor(tooth / 10))
  const upper = isUpper(tooth)

  return {
    top: upper ? 'V' : 'L',
    bottom: upper ? 'L' : 'V',
    left: onPatientRight ? 'D' : 'M',
    right: onPatientRight ? 'M' : 'D',
  }
}

// Current state of one tooth: the whole-tooth record and the record of each surface.
export function toothState(records: ToothRecord[], tooth: number) {
  const ofTooth = records.filter(record => record.tooth === tooth)
  const whole = ofTooth.find(record => record.surface === null && WHOLE_TOOTH.includes(record.condition)) ?? null
  const surfaces: Partial<Record<ToothSurface, ToothRecord>> = {}

  for (const record of ofTooth) {
    if (record.surface) {
      surfaces[record.surface] = record
    }
  }

  return { whole, surfaces }
}

export type ToothState = ReturnType<typeof toothState>

// State of every tooth with records, grouped in one pass so a chart of 32 teeth does not scan
// the whole history for each surface it paints.
export function toothStates(records: ToothRecord[]): Map<number, ToothState> {
  const byTooth = new Map<number, ToothRecord[]>()

  for (const record of records) {
    const list = byTooth.get(record.tooth)

    if (list) {
      list.push(record)
    }
    else {
      byTooth.set(record.tooth, [record])
    }
  }

  return new Map([...byTooth].map(([tooth, list]) => [tooth, toothState(list, tooth)]))
}

// Name read by screen readers, like "Dente 16, cárie oclusal, restauração mesial".
export function toothLabel(records: ToothRecord[], tooth: number): string {
  return describeTooth(toothState(records, tooth), tooth)
}

// Screen reader name from a state already computed.
export function describeTooth({ whole, surfaces }: ToothState, tooth: number): string {
  const parts = [`Dente ${tooth}`]

  if (whole) {
    parts.push(CONDITION_LABELS[whole.condition].toLowerCase())
  }

  for (const [surface, record] of Object.entries(surfaces)) {
    if (record.condition !== 'healthy') {
      parts.push(`${CONDITION_LABELS[record.condition].toLowerCase()} ${SURFACE_LABELS[surface as ToothSurface]}`)
    }
  }

  return parts.length === 1 ? `Dente ${tooth}, sem registros` : parts.join(', ')
}
