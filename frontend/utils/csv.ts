// CSV that Excel in Portuguese opens right: ";" between fields, quotes when needed and a BOM for the accents.
export function toCsv(rows: (string | number | null)[][]): string {
  const cell = (value: string | number | null) => {
    const text = value === null ? '' : String(value)

    return /[";\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text
  }

  return `﻿${rows.map(row => row.map(cell).join(';')).join('\r\n')}`
}

// Number with comma decimals, as Excel in Portuguese reads it.
export function csvNumber(value: number): string {
  return value.toFixed(2).replace('.', ',')
}

// Gives the browser a file to save.
export function downloadFile(name: string, content: string, type = 'text/csv;charset=utf-8') {
  const url = URL.createObjectURL(new Blob([content], { type }))
  const link = document.createElement('a')

  link.href = url
  link.download = name
  link.click()
  URL.revokeObjectURL(url)
}
