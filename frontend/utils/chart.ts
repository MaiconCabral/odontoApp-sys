export function barHeightPercent(value: number, scaleMax: number): number {
  if (scaleMax <= 0) {
    return 0
  }

  const percent = Math.round((value / scaleMax) * 100)

  return Math.min(Math.max(percent, 0), 100)
}
